<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EsicSetting;
use App\Models\Wage;
use Illuminate\Support\Carbon;

/**
 * The single place where ESIC coverage and contributions are decided.
 *
 * Statutory rules (ESI Act, 1948):
 *  - Coverage ceiling (default Rs 21,000 a month) decides who BECOMES covered.
 *  - An employee covered during a contribution period (Apr-Sep, Oct-Mar) stays covered to the end of
 *    that period even if wages cross the ceiling; contributions are then on the ACTUAL wages.
 *  - Employee share 0.75%, employer share 3.25% (configurable, effective-dated).
 * The ceiling is never used to cut contribution wages in 'statutory' mode.
 */
class EsicCalculator
{
    /** @var array<string, EsicSetting> */
    private array $settingsCache = [];

    public function flush(): void
    {
        $this->settingsCache = [];
    }

    /** The rules for a wage month: the latest settings row effective on or before its first day, else the defaults. */
    public function settingsFor(?int $tenantId, int $month, int $year): EsicSetting
    {
        $key = ($tenantId ?? 'none').'|'.$year.'-'.$month;

        return $this->settingsCache[$key] ??= $this->loadSettings($tenantId, $month, $year);
    }

    private function loadSettings(?int $tenantId, int $month, int $year): EsicSetting
    {
        $row = $tenantId === null ? null : EsicSetting::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereDate('effective_from', '<=', Carbon::create($year, $month, 1)->toDateString())
            ->orderByDesc('effective_from')
            ->first();

        return $row ?? new EsicSetting(EsicSetting::defaults());
    }

    /**
     * @param  float  $wages  the employee's gross wages for the month
     * @param  float  $bonus  bonus for the month (counts as ESIC wages only if the settings say so)
     * @param  ?Wage  $saved  the finalised wage; when it carries a snapshot for the same wages the snapshot is used
     */
    public function calculate(float $wages, int $month, int $year, ?Employee $employee, float $bonus = 0.0, ?Wage $saved = null): EsicResult
    {
        if ($saved && is_array($saved->esic_snapshot) && abs((float) $saved->gross_wages - $wages) < 0.01) {
            return EsicResult::fromSnapshot($saved->esic_snapshot);
        }

        $settings = $this->settingsFor($employee?->tenant_id, $month, $year);
        $base = $wages + ($settings->include_bonus ? $bonus : 0.0);
        $ceiling = (float) $settings->coverage_ceiling;
        // A unit can be set to ESIC on the ceiling (non-statutory) while every other unit stays statutory.
        $unitOnCeiling = (bool) $employee?->client?->esic_on_ceiling;
        $mode = ($unitOnCeiling || $settings->calculation_mode === 'capped') ? 'capped' : 'statutory';

        [$covered, $reason] = match (true) {
            ! $settings->establishment_covered => [false, 'establishment_not_covered'],
            $employee?->esic_applicable === false => [false, 'employee_not_applicable'],
            $mode === 'capped' => [true, $unitOnCeiling ? 'capped_unit_setting' : 'capped_mode'],
            $base <= $ceiling => [true, 'within_ceiling'],
            $employee && $this->hasCoverageEvidence($employee, $month, $year) => [true, 'continuing_contribution_period'],
            default => [false, 'above_ceiling_not_covered'],
        };

        $contributionWages = ! $covered ? 0.0 : ($mode === 'capped' ? min($base, $ceiling) : $base);
        $dailyWage = $employee && (float) $employee->rate_per_day > 0 ? (float) $employee->rate_per_day : null;
        $exempt = $covered
            && $settings->daily_wage_exemption !== null
            && $dailyWage !== null
            && $dailyWage <= (float) $settings->daily_wage_exemption;

        return new EsicResult(
            covered: $covered,
            contributionWages: $contributionWages,
            employeeAmount: $covered && ! $exempt ? $this->round($contributionWages * $settings->employee_rate / 100, $settings->rounding) : 0.0,
            employerAmount: $covered ? $this->round($contributionWages * $settings->employer_rate / 100, $settings->rounding) : 0.0,
            reason: $reason,
            mode: $mode,
            settings: [
                'effective_from' => $settings->effective_from?->toDateString(),
                'establishment_covered' => (bool) $settings->establishment_covered,
                'coverage_ceiling' => (float) $settings->coverage_ceiling,
                'employee_rate' => (float) $settings->employee_rate,
                'employer_rate' => (float) $settings->employer_rate,
                'daily_wage_exemption' => $settings->daily_wage_exemption,
                'include_bonus' => (bool) $settings->include_bonus,
                'rounding' => $settings->rounding,
                'mode_source' => $unitOnCeiling ? 'unit' : 'establishment',
            ],
            employeeShareExempt: $exempt,
        );
    }

    private function round(float $amount, string $mode): float
    {
        $amount = round($amount, 6);

        return match ($mode) {
            'up' => (float) ceil($amount),
            'down' => (float) floor($amount),
            default => round($amount, 0),
        };
    }

    /** First month of the contribution period that contains the given month. */
    public function periodStart(int $month, int $year): Carbon
    {
        $starts = collect(config('magnova.esic.contribution_period_start_months'))->sort()->values();
        $startMonth = $starts->filter(fn (int $m) => $m <= $month)->last();

        return $startMonth !== null
            ? Carbon::create($year, $startMonth, 1)->startOfDay()
            : Carbon::create($year - 1, $starts->last(), 1)->startOfDay();
    }

    /** True when the employee was already covered earlier in this contribution period. */
    private function hasCoverageEvidence(Employee $employee, int $month, int $year): bool
    {
        $start = $this->periodStart($month, $year);
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();

        if ($employee->esic_covered_from
            && $employee->esic_covered_from->gte($start)
            && $employee->esic_covered_from->lte($monthStart->copy()->endOfMonth())) {
            return true;
        }

        if (! $employee->id) {
            return false;
        }

        $startKey = $start->year * 12 + $start->month;
        $monthKey = $year * 12 + $month;

        return Wage::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereRaw('(year * 12 + month) >= ? and (year * 12 + month) < ?', [$startKey, $monthKey])
            ->get()
            ->contains(function (Wage $earlier) use ($employee) {
                if (is_array($earlier->esic_snapshot)) {
                    return (bool) ($earlier->esic_snapshot['covered'] ?? false);
                }

                // Saved before snapshots existed: covered if the wages were within the ceiling of that month.
                $ceiling = (float) $this->settingsFor($employee->tenant_id, $earlier->month, $earlier->year)->coverage_ceiling;

                return (float) $earlier->gross_wages <= $ceiling;
            });
    }
}
