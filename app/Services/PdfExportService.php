<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Wage;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PdfExportService
{
    public function __construct(
        private readonly NumberToWordsService $words,
        private readonly WageRegisterCalculator $wageRegisterCalculator,
    ) {}

    public function payslip(Wage $wage): PdfBuilder
    {
        $wage->loadMissing('employee.client');

        return Pdf::loadView('pdf.payslip', [
            'wage' => $wage,
            'ytd' => $this->ytdFor($wage),
            'netSalaryWords' => $this->words->amountToWords((float) $wage->net_salary),
            'logoDataUri' => $this->logoDataUri(),
        ])->setPaper('a4');
    }

    public function bulkPayslips(Collection $wages, int $month, int $year): PdfBuilder
    {
        $wages->loadMissing('employee.client');
        $wages = $wages->sortBy(fn (Wage $wage) => $wage->employee->skillSortKey())->values();

        $ytd =$wages->mapWithKeys(fn (Wage $wage) => [$wage->id => $this->ytdFor($wage)]);
        $netSalaryWords = $wages->mapWithKeys(fn (Wage $wage) => [
            $wage->id => $this->words->amountToWords((float) $wage->net_salary),
        ]);

        return Pdf::loadView('pdf.payslip-bulk', [
            'wages' => $wages,
            'month' => $month,
            'year' => $year,
            'ytd' => $ytd,
            'netSalaryWords' => $netSalaryWords,
            'logoDataUri' => $this->logoDataUri(),
        ])->setPaper('a4');
    }

    public function invoice(Invoice $invoice): PdfBuilder
    {
        $invoice->loadMissing('client', 'lineItems.employee', 'tenant');

        $dutySummary = $invoice->lineItems
            ->sortBy(fn ($item) => $item->employee->skillSortKey())
            ->groupBy(function ($item) {
                $designation = trim((string) ($item->employee->designation ?? ''));

                if ($designation === '') {
                    $designation = $item->employee->skillLabel().' Security Guard';
                }

                if (strtolower($designation) === 'security guard' && $item->employee->gender) {
                    $designation .= ' '.ucfirst((string) $item->employee->gender);
                }

                return $designation;
            })
            ->map(fn ($items, $designation) => [
                'rank' => $designation,
                'rate' => (float) ($items->first()->rate_per_day ?? 0),
                'persons' => $items->count(),
                'duties' => (float) $items->sum('working_days'),
                'amount' => (float) $items->sum('wage_amount'),
            ])
            ->values();
        $bonus = (float) $invoice->lineItems->sum(
            fn ($item) => $this->wageRegisterCalculator->bonusFor(
                (float) $item->wage_amount,
                (int) $invoice->month,
                (int) $invoice->year,
                (bool) $invoice->client?->bonus_enabled,
            ),
        );

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'dutySummary' => $dutySummary,
            'bonus' => $bonus,
            'amountInWords' => $this->words->amountToWords((float) $invoice->grand_total),
            'logoDataUri' => $this->logoDataUri(),
        ])->setPaper('a4');
    }

    private function logoDataUri(): ?string
    {
        $logoPath = base_path('magnovapng.png');

        if (! is_file($logoPath)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));
    }

    public function attendanceSheet(Client $client, int $month, int $year): PdfBuilder
    {
        $employees = Employee::where('client_id', $client->id)
            ->where('status', 'active')
            ->orderBySkill()
            ->get(['id', 'employee_code', 'name', 'gender', 'designation']);

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $attendance = Attendance::whereIn('employee_id', $employees->pluck('id'))
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($row) => $row->date->day)->map->status);

        return Pdf::loadView('pdf.attendance-sheet', [
            'client' => $client,
            'employees' => $employees,
            'attendance' => $attendance,
            'month' => $month,
            'year' => $year,
            'daysInMonth' => $daysInMonth,
        ])->setPaper('ledger', 'landscape');
    }

    public function wageRegister(Client $client, int $month, int $year): PdfBuilder
    {
        $employees = Employee::query()
            ->with('client')
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->orderBySkill()
            ->get()
            ->values();
        $existingWages = Wage::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('employee_id');
        $rows = $employees->map(
            fn (Employee $employee, int $index) => $this->wageRegisterCalculator->row(
                $employee,
                $existingWages->get($employee->id),
                $month,
                $year,
                $index + 1,
            ),
        );

        return Pdf::loadView('pdf.wage-register', [
            'client' => $client,
            'rows' => $rows,
            'headings' => $this->wageRegisterCalculator->headings($month, $year),
            'month' => $month,
            'year' => $year,
        ])->setPaper('a3', 'landscape');
    }

    /**
     * Cumulative totals for the employee from the start of the Indian
     * financial year (April) through the wage record's own month/year.
     */
    private function ytdFor(Wage $wage): array
    {
        $fyStartYear = $wage->month >= 4 ? $wage->year : $wage->year - 1;
        $startKey = $fyStartYear * 12 + 4;
        $endKey = $wage->year * 12 + $wage->month;

        $rows = Wage::where('employee_id', $wage->employee_id)
            ->whereRaw('(year * 12 + month) between ? and ?', [$startKey, $endKey])
            ->get();

        return [
            'gross_wages' => (float) $rows->sum('gross_wages'),
            'bonus' => (float) $rows->sum('bonus'),
            'epf_employee' => (float) $rows->sum('epf_employee'),
            'esic_employee' => (float) $rows->sum('esic_employee'),
        ];
    }
}
