<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Wage;
use Illuminate\Support\Carbon;

class WageRegisterCalculator
{
    public function workingDays(Employee $employee, int $month, int $year): float
    {
        return (float) Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->sum(fn (Attendance $attendance) => match ($attendance->status) {
                'present', 'sunday' => 1,
                default => 0,
            });
    }

    public function usesCurrentRules(int $month, int $year): bool
    {
        return Carbon::create($year, $month, 1)->gte(Carbon::parse(config('magnova.register_rules_from')));
    }

    /**
     * EPF/EDLI wage ceiling in force for each stretch of the month.
     *
     * @return array<int, array{ceiling: float, from: int, to: int, days: int}>
     */
    public function ceilingSegments(int $month, int $year): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $changes = collect(config('magnova.epf_wage_ceiling_changes'))
            ->sortBy('from')
            ->values();
        $segments = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->toDateString();
            $ceiling = (float) config('magnova.epf_wage_ceiling');

            foreach ($changes as $change) {
                if ($change['from'] <= $date) {
                    $ceiling = (float) $change['ceiling'];
                }
            }

            $last = count($segments) - 1;

            if ($last >= 0 && $segments[$last]['ceiling'] === $ceiling) {
                $segments[$last]['to'] = $day;
                $segments[$last]['days']++;
            } else {
                $segments[] = ['ceiling' => $ceiling, 'from' => $day, 'to' => $day, 'days' => 1];
            }
        }

        return $segments;
    }

    /** Monthly Wage restricted by the EPF ceiling, averaged over the days of the month. */
    private function epfWageBase(float $monthlyWage, int $month, int $year): float
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        return collect($this->ceilingSegments($month, $year))
            ->sum(fn (array $segment) => min($monthlyWage, $segment['ceiling']) * $segment['days'])
            / $daysInMonth;
    }

    /** Column headings that depend on the ceilings in force for the month. */
    public function headings(int $month, int $year): array
    {
        if (! $this->usesCurrentRules($month, $year)) {
            return [
                'epf' => 'EPF @12% of MIN(F, Rs. 15,000)',
                'edli' => 'EDLI and EPF Admin @1% of MIN(F, Rs. 15,000)',
                'esic' => 'ESIC @3.25% of MIN(F, Rs. 21,000)',
                'bonus' => 'Bonus @8.33% of MIN(F, Rs. 21,000)',
                'epf_individual' => 'EPF individual @12% of MIN(F, Rs. 15,000)',
                'esic_individual' => 'ESIC individual @0.75% of MIN(F, Rs. 21,000)',
            ];
        }

        $segments = $this->ceilingSegments($month, $year);
        $monthName = Carbon::create($year, $month, 1)->format('M');
        $ceiling = count($segments) === 1
            ? 'Rs.'.number_format($segments[0]['ceiling'])
            : collect($segments)
                ->map(fn (array $s) => 'Rs.'.number_format($s['ceiling']).' ('.$s['from'].'-'.$s['to'].' '.$monthName.')')
                ->implode(' / ');
        $esicLimit = 'nil if Monthly Wage above Rs.'.number_format(config('magnova.esic_wage_ceiling')).'/-';

        return [
            'epf' => "EPF @12% of Monthly Wage, ceiling {$ceiling}",
            'edli' => "EDLI and EPF Admin Charges @1% of Monthly Wage, ceiling {$ceiling}",
            'esic' => "ESIC @3.25% of Monthly Wage ({$esicLimit})",
            'bonus' => "Bonus @8.33% of Monthly Wage ({$esicLimit})",
            'epf_individual' => "EPF individual Contribution @12% of Monthly Wage, ceiling {$ceiling}",
            'esic_individual' => "ESIC individual Contribution @0.75% of Monthly Wage ({$esicLimit})",
        ];
    }

    public function row(Employee $employee, ?Wage $wage, int $month, int $year, int $serialNumber): array
    {
        $current = $this->usesCurrentRules($month, $year);
        $precision = $current ? 0 : 2;
        $esicCeiling = (float) config('magnova.esic_wage_ceiling');

        $daysDeployed = $this->workingDays($employee, $month, $year);
        $minimumWage = (float) ($wage?->rate_per_day ?? $employee->rate_per_day);
        $monthlyWage = round($daysDeployed * $minimumWage, 2);
        $epfBase = $this->epfWageBase($monthlyWage, $month, $year);
        $aboveEsicCeiling = $current && $monthlyWage > $esicCeiling;

        $bonus = $current
            ? ($aboveEsicCeiling ? 0.0 : round($monthlyWage * config('magnova.bonus_pct') / 100, 0))
            : round(min($monthlyWage, config('magnova.bonus_cap')) * config('magnova.bonus_pct') / 100, 2);
        $epfEmployer = round($epfBase * config('magnova.epf_employee_pct') / 100, $precision);
        $edliAndEpfAdmin = round(
            $epfBase * (config('magnova.epf_admin_pct') + config('magnova.edli_cont_pct')) / 100,
            $precision,
        );
        $esicEmployer = $current
            ? ($aboveEsicCeiling ? 0.0 : round($monthlyWage * config('magnova.esi_employer_pct') / 100, 0))
            : round(min($monthlyWage, $esicCeiling) * config('magnova.esi_employer_pct') / 100, 2);
        $totalGrossSalary = round($monthlyWage + $epfEmployer + $edliAndEpfAdmin + $esicEmployer + $bonus, $precision);
        $gst = round(
            $totalGrossSalary * (config('magnova.cgst_pct') + config('magnova.sgst_pct')) / 100,
            $precision,
        );
        $agencyFee = round($totalGrossSalary * config('magnova.service_charge_pct') / 100, $precision);
        $totalBillAmount = round($totalGrossSalary + $gst + $agencyFee, $precision);
        $epfEmployee = round($epfBase * config('magnova.epf_employee_pct') / 100, $precision);
        $esicEmployee = $current
            ? ($aboveEsicCeiling ? 0.0 : round($monthlyWage * config('magnova.esic_employee_pct') / 100, 0))
            : round(min($monthlyWage, $esicCeiling) * config('magnova.esic_employee_pct') / 100, 2);
        $otherDeduction = 0.0;
        $netPayableSalary = round(
            $monthlyWage + $bonus - $epfEmployee - $esicEmployee - $otherDeduction,
            $precision,
        );

        return [
            'serial_number' => $serialNumber,
            'employee_id' => $employee->id,
            'name' => $employee->name,
            'employee_code' => $employee->employee_code,
            'nature_of_deployment' => $employee->designation
                ?: $employee->skillLabel().' Security Guard',
            'days_deployed' => $daysDeployed,
            'minimum_wage' => $minimumWage,
            'monthly_wage' => $monthlyWage,
            'epf_employer' => $epfEmployer,
            'edli_and_epf_admin' => $edliAndEpfAdmin,
            'esic_employer' => $esicEmployer,
            'bonus' => $bonus,
            'total_gross_salary' => $totalGrossSalary,
            'gst' => $gst,
            'agency_fee' => $agencyFee,
            'total_bill_amount' => $totalBillAmount,
            'epf_employee' => $epfEmployee,
            'esic_employee' => $esicEmployee,
            'other_deduction' => $otherDeduction,
            'net_payable_salary' => $netPayableSalary,
            'saved' => $wage !== null,
        ];
    }
}
