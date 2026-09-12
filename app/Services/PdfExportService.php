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
    ) {}

    public function payslip(Wage $wage): PdfBuilder
    {
        $wage->loadMissing('employee.client');

        return Pdf::loadView('pdf.payslip', [
            'wage' => $wage,
            'ytd' => $this->ytdFor($wage),
            'netSalaryWords' => $this->words->amountToWords((float) $wage->net_salary),
        ])->setPaper('a4');
    }

    public function bulkPayslips(Collection $wages, int $month, int $year): PdfBuilder
    {
        $wages->loadMissing('employee.client');

        $ytd = $wages->mapWithKeys(fn (Wage $wage) => [$wage->id => $this->ytdFor($wage)]);
        $netSalaryWords = $wages->mapWithKeys(fn (Wage $wage) => [
            $wage->id => $this->words->amountToWords((float) $wage->net_salary),
        ]);

        return Pdf::loadView('pdf.payslip-bulk', [
            'wages' => $wages,
            'month' => $month,
            'year' => $year,
            'ytd' => $ytd,
            'netSalaryWords' => $netSalaryWords,
        ])->setPaper('a4');
    }

    public function invoice(Invoice $invoice): PdfBuilder
    {
        $invoice->loadMissing('client', 'lineItems.employee', 'tenant');

        $itemGroups = $invoice->lineItems
            ->groupBy(fn ($item) => $item->employee->designation ?? 'Manpower Supply')
            ->map(fn ($items, $designation) => [
                'designation' => $designation,
                'qty' => (float) $items->sum('working_days'),
                'rate' => (float) ($items->first()->rate_per_day ?? 0),
                'amount' => (float) $items->sum('wage_amount'),
            ])
            ->values();

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'itemGroups' => $itemGroups,
            'amountInWords' => $this->words->amountToWords((float) $invoice->grand_total),
        ])->setPaper('a4');
    }

    public function attendanceSheet(Client $client, int $month, int $year): PdfBuilder
    {
        $employees = Employee::where('client_id', $client->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

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
        ])->setPaper('a4', 'landscape');
    }

    public function wageRegister(Client $client, int $month, int $year): PdfBuilder
    {
        $wages = Wage::whereHas('employee', fn ($q) => $q->where('client_id', $client->id))
            ->where('month', $month)
            ->where('year', $year)
            ->with('employee')
            ->get()
            ->sortBy(fn (Wage $wage) => $wage->employee->name)
            ->values();

        return Pdf::loadView('pdf.wage-register', [
            'client' => $client,
            'wages' => $wages,
            'month' => $month,
            'year' => $year,
        ])->setPaper('a4', 'landscape');
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
