<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Wages saved before the ESIC update keep the figures they were saved with.
 *
 * Each saved wage from the current-rules months (register_rules_from onwards) that has no snapshot gets one
 * describing the OLD rule that produced it (ESIC nil above Rs 21,000). The saved employee amount is kept as it is;
 * only the employer amount, which was never stored, is re-derived by the old rule. Regenerating an invoice or
 * opening the register therefore shows the same numbers as before. The corrected statutory rules apply the next
 * time the user saves wages for a month.
 */
return new class extends Migration
{
    public function up(): void
    {
        $from = Carbon::parse(config('magnova.register_rules_from'));
        $fromKey = $from->year * 12 + $from->month;
        $ceiling = (float) config('magnova.esic_wage_ceiling');
        $employeeRate = (float) config('magnova.esic_employee_pct');
        $employerRate = (float) config('magnova.esi_employer_pct');

        DB::table('wages')
            ->whereNull('esic_snapshot')
            ->whereRaw('(year * 12 + month) >= ?', [$fromKey])
            ->orderBy('id')
            ->chunkById(200, function ($wages) use ($ceiling, $employeeRate, $employerRate) {
                foreach ($wages as $wage) {
                    $gross = (float) $wage->gross_wages;
                    $covered = $gross <= $ceiling;

                    DB::table('wages')->where('id', $wage->id)->update([
                        'esic_snapshot' => json_encode([
                            'mode' => 'legacy_cutoff',
                            'covered' => $covered,
                            'reason' => 'saved_before_esic_update',
                            'contribution_wages' => $covered ? $gross : 0.0,
                            'employee_amount' => (float) $wage->esic_employee,
                            'employer_amount' => $covered ? round($gross * $employerRate / 100, 0) : 0.0,
                            'employee_share_exempt' => false,
                            'effective_from' => null,
                            'establishment_covered' => true,
                            'coverage_ceiling' => $ceiling,
                            'employee_rate' => $employeeRate,
                            'employer_rate' => $employerRate,
                            'daily_wage_exemption' => null,
                            'include_bonus' => false,
                            'rounding' => 'nearest',
                        ]),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Nothing to undo: the snapshot column is dropped by its own migration.
    }
};
