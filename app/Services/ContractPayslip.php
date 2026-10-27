<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ContractWage;
use Illuminate\Database\Eloquent\Collection;

/**
 * Payslips of a contract-labour client. They are printed from the saved lines of its Register of Wages
 * (contract_wages), so a payslip always agrees with the wage register and the tax invoice.
 */
class ContractPayslip
{
    /** The figures that appear on a payslip, with their financial-year totals. */
    public const FIGURES = ['earn_wages', 'ot_amount', 'bonus', 'leave_wages', 'pmjjby', 'pmsby', 'gross', 'pf_employee', 'esic_employee', 'total_deduction', 'net_salary'];

    /**
     * The saved wage lines of the unit for a month, in register order (skill, then name).
     *
     * @return Collection<int, ContractWage>
     */
    public function forMonth(Client $client, int $month, int $year): Collection
    {
        return ContractWage::query()
            ->with('employee')
            ->where('client_id', $client->id)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->sortBy(fn (ContractWage $line) => $line->employee->skillSortKey())
            ->values();
    }

    /**
     * Totals from the start of the Indian financial year (April) to the line's own month.
     *
     * @return array<string, float>
     */
    public function ytd(ContractWage $line): array
    {
        $fyStartYear = $line->month >= 4 ? $line->year : $line->year - 1;
        $startKey = $fyStartYear * 12 + 4;
        $endKey = $line->year * 12 + $line->month;

        $totals = array_fill_keys(self::FIGURES, 0.0);

        $rows = ContractWage::query()
            ->where('employee_id', $line->employee_id)
            ->whereRaw('(year * 12 + month) between ? and ?', [$startKey, $endKey])
            ->get();

        foreach ($rows as $row) {
            foreach (self::FIGURES as $key) {
                $totals[$key] += (float) ($row->figures[$key] ?? 0);
            }
        }

        return $totals;
    }
}
