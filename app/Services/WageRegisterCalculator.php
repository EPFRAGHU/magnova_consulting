<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Wage;
use Illuminate\Support\Carbon;

class WageRegisterCalculator
{
    private function esic(): EsicCalculator
    {
        return app(EsicCalculator::class);
    }

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

    /** Agency fee heading; null means the register mixes units that use different bases. */
    private function agencyHeading(?bool $serviceChargeOnBasic): string
    {
        return match ($serviceChargeOnBasic) {
            true => 'Agency Fee @3.85% of Monthly Wage (F*0.0385)',
            false => 'Agency Fee @3.85% of Total Gross Salary (K*0.0385)',
            null => 'Agency Fee @3.85% of Monthly Wage (F) or Total Gross Salary (K), as set for the unit',
        };
    }

    /** GST heading; null means the register mixes units that charge GST with units that do not. */
    private function gstHeading(?bool $gstExempt): string
    {
        return match ($gstExempt) {
            true => 'GST (not charged to this unit)',
            false => 'GST @ 18 % (K*0.18)',
            null => 'GST @ 18 % (K*0.18), nil for units without GST',
        };
    }

    /** Column headings that depend on the ceilings in force for the month and the unit's service charge and GST settings. */
    public function headings(int $month, int $year, ?bool $serviceChargeOnBasic = false, ?bool $gstExempt = false, ?int $tenantId = null, ?bool $esicOnCeiling = false): array
    {
        $agency = $this->agencyHeading($serviceChargeOnBasic);
        $gst = $this->gstHeading($gstExempt);

        if (! $this->usesCurrentRules($month, $year)) {
            return [
                'agency' => $agency,
                'gst' => $gst,
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
        $bonusLimit = 'nil if Monthly Wage above Rs.'.number_format(config('magnova.bonus_eligibility_ceiling')).'/-';
        $esic = $this->esic()->settingsFor($tenantId, $month, $year);
        $esicNote = ($esicOnCeiling || $esic->calculation_mode === 'capped')
            ? 'wages capped at Rs.'.number_format($esic->coverage_ceiling).' - NOT statutory, unit setting'
            : 'covered employees only, on actual wages; see Settings > ESIC';

        return [
            'agency' => $agency,
            'gst' => $gst,
            'epf' => "EPF @12% of Monthly Wage, ceiling {$ceiling}",
            'edli' => "EDLI and EPF Admin Charges @1% of Monthly Wage, ceiling {$ceiling}",
            'esic' => 'ESIC @'.rtrim(rtrim(number_format($esic->employer_rate, 2), '0'), '.')."% of ESIC wages ({$esicNote})",
            'bonus' => "Bonus @8.33% of Monthly Wage ({$bonusLimit})",
            'epf_individual' => "EPF individual Contribution @12% of Monthly Wage, ceiling {$ceiling}",
            'esic_individual' => 'ESIC individual Contribution @'.rtrim(rtrim(number_format($esic->employee_rate, 2), '0'), '.')."% of ESIC wages ({$esicNote})",
        ];
    }

    /** Bonus is a per-unit choice: when the unit has it switched off nobody in that unit gets any. */
    public function bonusFor(float $monthlyWage, int $month, int $year, bool $bonusEnabled): float
    {
        if (! $bonusEnabled) {
            return 0.0;
        }

        if ($this->usesCurrentRules($month, $year)) {
            return $monthlyWage > (float) config('magnova.bonus_eligibility_ceiling')
                ? 0.0
                : round($monthlyWage * config('magnova.bonus_pct') / 100, 0);
        }

        return round(min($monthlyWage, config('magnova.bonus_cap')) * config('magnova.bonus_pct') / 100, 2);
    }

    public function row(Employee $employee, ?Wage $wage, int $month, int $year, int $serialNumber): array
    {
        $daysDeployed = $this->workingDays($employee, $month, $year);
        $minimumWage = (float) ($wage?->rate_per_day ?? $employee->rate_per_day);
        $monthlyWage = round($daysDeployed * $minimumWage, 2);

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
            ...$this->figures(
                $monthlyWage,
                $month,
                $year,
                (bool) $employee->client?->bonus_enabled,
                (bool) $employee->client?->service_charge_on_basic,
                (bool) $employee->client?->gst_exempt,
                $employee,
                $wage,
            ),
            'saved' => $wage !== null,
        ];
    }

    /** Every statutory and billing figure derived from one employee's Monthly Wage. */
    /**
     * $employee and $saved let the ESIC rules see the employee's coverage history and a finalised wage's snapshot;
     * without them the figures are those of a new employee.
     */
    public function figures(float $monthlyWage, int $month, int $year, bool $bonusEnabled, bool $serviceChargeOnBasic = false, bool $gstExempt = false, ?Employee $employee = null, ?Wage $saved = null): array
    {
        $current = $this->usesCurrentRules($month, $year);
        $precision = $current ? 0 : 2;
        $esicCeiling = (float) config('magnova.esic_wage_ceiling');

        $epfBase = $this->epfWageBase($monthlyWage, $month, $year);

        $bonus = $this->bonusFor($monthlyWage, $month, $year, $bonusEnabled);
        $esic = $current ? $this->esic()->calculate($monthlyWage, $month, $year, $employee, $bonus, $saved) : null;
        $epfEmployer = round($epfBase * config('magnova.epf_employee_pct') / 100, $precision);
        $edliAndEpfAdmin = round(
            $epfBase * (config('magnova.epf_admin_pct') + config('magnova.edli_cont_pct')) / 100,
            $precision,
        );
        $esicEmployer = $esic
            ? $esic->employerAmount
            : round(min($monthlyWage, $esicCeiling) * config('magnova.esi_employer_pct') / 100, 2);
        $totalGrossSalary = round($monthlyWage + $epfEmployer + $edliAndEpfAdmin + $esicEmployer + $bonus, $precision);
        $gst = $gstExempt ? 0.0 : round(
            $totalGrossSalary * (config('magnova.cgst_pct') + config('magnova.sgst_pct')) / 100,
            $precision,
        );
        $serviceChargeBase = $serviceChargeOnBasic ? $monthlyWage : $totalGrossSalary;
        $agencyFee = round($serviceChargeBase * config('magnova.service_charge_pct') / 100, $precision);
        $totalBillAmount = round($totalGrossSalary + $gst + $agencyFee, $precision);
        $epfEmployee = round($epfBase * config('magnova.epf_employee_pct') / 100, $precision);
        $esicEmployee = $esic
            ? $esic->employeeAmount
            : round(min($monthlyWage, $esicCeiling) * config('magnova.esic_employee_pct') / 100, 2);
        $otherDeduction = 0.0;
        $netPayableSalary = round(
            $monthlyWage + $bonus - $epfEmployee - $esicEmployee - $otherDeduction,
            $precision,
        );

        return [
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
            // Rules and decision behind the ESIC figures (null for months before the current rules).
            'esic_result' => $esic,
            'other_deduction' => $otherDeduction,
            'net_payable_salary' => $netPayableSalary,
        ];
    }
}
