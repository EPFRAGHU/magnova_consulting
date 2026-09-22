<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Attendance;
use App\Models\Wage;
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
        $month = (int) ($data['month'] ?? now()->format('n'));
        $year = (int) ($data['year'] ?? now()->format('Y'));
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

        return Inertia::render('dashboard', [
            'stats' => [
                'active_clients' => Client::where('active', true)->count(),
                'employees' => Employee::where('status', 'active')->count(),
                'invoices_due' => Invoice::where('month', $month)->where('year', $year)->where('status', '!=', 'paid')->count(),
                'total_billed_this_month' => (float) Invoice::where('month', $month)->where('year', $year)->sum('grand_total'),
            ],
            'clients' => $clients,
            'month' => $month,
            'year' => $year,
        ]);
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
