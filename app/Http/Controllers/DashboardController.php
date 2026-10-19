<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Wage;
use App\Support\WageMonth;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $data = $request->validate([
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);
        $month = (int) ($data['month'] ?? WageMonth::month());
        $year = (int) ($data['year'] ?? WageMonth::year());
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $clients = Client::query()
            ->where('active', true)
            ->withCount(['employees as employees_count' => fn ($q) => $q->where('status', 'active')])
            ->with(['invoices' => fn ($q) => $q->where('month', $month)->where('year', $year)])
            ->orderBy('name')
            ->get()
            ->map(function (Client $client) use ($month, $year, $daysInMonth): array {
                $employeeIds = $client->employees()
                    ->where('status', 'active')
                    ->pluck('id');
                $employeeTotal = $employeeIds->count();
                $attendanceTotal = $employeeTotal * $daysInMonth;
                $attendanceDone = $attendanceTotal === 0 ? 0 : Attendance::query()
                    ->whereIn('employee_id', $employeeIds)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $month)
                    ->count();
                $wagesDone = Wage::query()
                    ->whereIn('employee_id', $employeeIds)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->count();
                $invoice = $client->invoices->first();
                $attendanceStatus = $this->progressStatus($attendanceDone, $attendanceTotal);
                $wagesStatus = $this->progressStatus($wagesDone, $employeeTotal);
                $dependenciesReady = $attendanceStatus === 'processed' && $wagesStatus === 'processed';
                $invoiceStatus = match (true) {
                    $invoice && in_array($invoice->status, ['sent', 'paid'], true) => 'processed',
                    $invoice => 'in_progress',
                    ! $dependenciesReady => 'blocked',
                    default => 'not_started',
                };

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'unit_label' => $client->unit_label,
                    'client_status' => 'processed',
                    'employees_count' => $employeeTotal,
                    'employees_status' => $this->progressStatus($employeeTotal, $employeeTotal),
                    'attendance_status' => $attendanceStatus,
                    'attendance_done' => $attendanceDone,
                    'attendance_total' => $attendanceTotal,
                    'wages_status' => $wagesStatus,
                    'wages_done' => $wagesDone,
                    'wages_total' => $employeeTotal,
                    'invoice_status' => $invoiceStatus,
                    'invoice_count' => $invoice ? 1 : 0,
                    'invoice_status_detail' => $invoice?->status,
                    'payslip_status' => $wagesStatus === 'processed' ? 'processed' : ($wagesStatus === 'not_started' ? 'not_started' : 'in_progress'),
                    'payslip_done' => $wagesDone,
                    'payslip_total' => $employeeTotal,
                ];
            });

        $activeEmployees = Employee::where('status', 'active')->count();
        $today = now('Asia/Kolkata');

        return Inertia::render('dashboard', [
            'stats' => [
                'active_clients' => Client::where('active', true)->count(),
                'employees' => $activeEmployees,
                'invoices_due' => Invoice::where('month', $month)->where('year', $year)->where('status', '!=', 'paid')->count(),
                'total_billed_this_month' => (float) Invoice::where('month', $month)->where('year', $year)->sum('grand_total'),
            ],
            'outstanding' => $this->outstandingInvoices($today),
            'today' => $this->attendanceToday($today, $activeEmployees),
            'contributions' => $this->contributions($month, $year, $activeEmployees),
            'recent' => $this->recentRecords(),
            'clients' => $clients,
            'month' => $month,
            'year' => $year,
        ]);
    }

    /**
     * Invoices that were sent to the client and are not paid yet (drafts are not billed yet).
     *
     * @return array<string, int|float>
     */
    private function outstandingInvoices(CarbonInterface $today): array
    {
        $sent = Invoice::where('status', 'sent');

        return [
            'count' => (clone $sent)->count(),
            'amount' => round((float) (clone $sent)->sum('grand_total'), 2),
            'overdue' => (clone $sent)->whereDate('due_date', '<', $today->toDateString())->count(),
            'drafts' => Invoice::where('status', 'draft')->count(),
        ];
    }

    /**
     * Today's attendance counted exactly as marked in the attendance register.
     * Weekly off and holidays are not absences, and unmarked guards are shown separately.
     *
     * @return array<string, int|string>
     */
    private function attendanceToday(CarbonInterface $today, int $activeEmployees): array
    {
        $counts = Attendance::query()
            ->whereDate('date', $today->toDateString())
            ->whereHas('employee', fn ($q) => $q->where('status', 'active'))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = (int) ($counts['present'] ?? 0);
        $marked = (int) $counts->sum();

        return [
            'date' => $today->toDateString(),
            'expected' => $activeEmployees,
            'present' => $present,
            'absent' => (int) ($counts['absent'] ?? 0),
            'leave' => (int) ($counts['leave'] ?? 0),
            'off' => (int) (($counts['sunday'] ?? 0) + ($counts['off'] ?? 0) + ($counts['holiday'] ?? 0)),
            'not_marked' => max(0, $activeEmployees - $marked),
            'percent' => $activeEmployees > 0 ? (int) round($present / $activeEmployees * 100) : 0,
        ];
    }

    /**
     * Saved wage and invoice totals for the selected period. Nothing here says a return was filed.
     *
     * @return array<string, int|float>
     */
    private function contributions(int $month, int $year, int $activeEmployees): array
    {
        $wages = Wage::query()->where('month', $month)->where('year', $year);
        $invoices = Invoice::query()->where('month', $month)->where('year', $year);

        return [
            'wages_saved' => (clone $wages)->whereHas('employee', fn ($q) => $q->where('status', 'active'))->count(),
            'active_employees' => $activeEmployees,
            'epf_employee' => round((float) (clone $wages)->sum('epf_employee'), 2),
            'esic_employee' => round((float) (clone $wages)->sum('esic_employee'), 2),
            'net_payable' => round((float) (clone $wages)->sum('net_salary'), 2),
            'gst' => round((float) (clone $invoices)->sum('cgst') + (float) (clone $invoices)->sum('sgst'), 2),
        ];
    }

    /**
     * Latest real records (invoices, employees, saved wages), newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentRecords(): array
    {
        $invoices = Invoice::with('client')->latest()->limit(4)->get()->map(fn (Invoice $i) => [
            'type' => 'invoice',
            'title' => "Invoice {$i->invoice_no} ".($i->status === 'paid' ? 'paid' : ($i->status === 'sent' ? 'awaiting payment' : 'drafted')),
            'detail' => $i->client->name ?? '',
            'amount' => (float) $i->grand_total,
            'at' => $i->created_at?->toIso8601String(),
            'url' => route('invoices.show', $i, false),
        ]);

        $employees = Employee::with('client')->latest()->limit(4)->get()->map(fn (Employee $e) => [
            'type' => 'employee',
            'title' => "Employee {$e->name} added",
            'detail' => trim(($e->client->name ?? 'No unit').($e->designation ? ' · '.$e->designation : '')),
            'at' => $e->created_at?->toIso8601String(),
            'url' => route('employees.edit', $e, false),
        ]);

        $wages = Wage::query()
            ->selectRaw('month, year, count(*) as total, max(updated_at) as last_saved, sum(net_salary) as net')
            ->groupBy('month', 'year')
            ->orderByDesc('last_saved')
            ->limit(3)
            ->get()
            ->map(fn (Wage $w) => [
                'type' => 'wages',
                'title' => Carbon::create((int) $w->year, (int) $w->month, 1)->format('F Y').' wages saved',
                'detail' => "{$w->getAttribute('total')} employees · Net payable",
                'amount' => (float) $w->getAttribute('net'),
                'at' => Carbon::parse($w->getAttribute('last_saved'))->toIso8601String(),
                'url' => route('wages.index', ['month' => (int) $w->month, 'year' => (int) $w->year], false),
            ]);

        return $invoices->concat($employees)->concat($wages)
            ->filter(fn (array $r) => (bool) $r['at'])
            ->sortByDesc('at')
            ->take(6)
            ->values()
            ->all();
    }

    private function progressStatus(int $done, int $total): string
    {
        return match (true) {
            $total === 0 => 'not_started',
            $done === 0 => 'not_started',
            $done < $total => 'in_progress',
            default => 'processed',
        };
    }
}
