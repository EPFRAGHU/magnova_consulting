<?php

namespace App\Services;

/**
 * Wage-sheet arithmetic for a contract-labour client, reproducing that client's own Wages sheet column by column
 * (Wages sheet, Balmer Lawrie, September 2026). It is pure: no database, no clients, no employees. Figures are
 * returned at full precision; rounding for display is the caller's choice. Only PF is rounded to the rupee, as in the sheet.
 *
 * Column map (sheet letter -> key): H earn_wages, J ot_amount, K bonus, L leave_wages, M pmjjby, N pmsby, O gross,
 * P pf_employee, Q esic_employee, R total_deduction, S net_salary, T pf_employer, U esic_employer, V ppe, W lwf,
 * X total_contribution, Y total_ctc, Z service_charge, AA gross_amount, AB gst, AC amount_incl_gst.
 */
class ContractLabourCalculator
{
    /** @param array<string, mixed> $overrides per-client changes to the defaults in config('magnova.contract_labour') */
    public function calculate(
        float $ratePerDay,
        float $paidDays,
        float $otHours,
        int $month,
        int $year,
        array $overrides = [],
        bool $esicApplicable = true,
        bool $pfApplicable = true,
    ): array {
        $p = array_merge(config('magnova.contract_labour'), $overrides);

        $earn = $ratePerDay * $paidDays;                                                      // H
        $ot = ($ratePerDay / $p['hours_per_day']) * $p['ot_multiplier'] * $otHours;           // J
        $bonus = $earn * $p['bonus_pct'] / 100;                                               // K
        $leave = (($ratePerDay * $p['leave_factor']) / $p['day_divisor']) * $paidDays;        // L
        $pmjjby = (($p['pmjjby_per_year'] / 12) / $p['day_divisor']) * $paidDays;             // M
        $pmsby = (($p['pmsby_per_year'] / 12) / $p['day_divisor']) * $paidDays;               // N
        $gross = $earn + $ot + $bonus + $leave + $pmjjby + $pmsby;                            // O

        $epfBase = (new WageRegisterCalculator)->epfWageBase($earn, $month, $year);
        $pfEmployee = $pfApplicable ? round($epfBase * $p['pf_employee_pct'] / 100, 0) : 0.0; // P
        $esicWages = $earn + $ot;
        $esicEmployee = $esicApplicable ? $esicWages * $p['esic_employee_pct'] / 100 : 0.0;   // Q
        $totalDeduction = $pfEmployee + $esicEmployee;                                        // R
        $net = $gross - $totalDeduction;                                                      // S

        $pfEmployer = $pfApplicable ? round($epfBase * $p['pf_employer_pct'] / 100, 0) : 0.0; // T
        $esicEmployer = $esicApplicable ? $esicWages * $p['esic_employer_pct'] / 100 : 0.0;   // U
        $ppe = $p['ppe_enabled'] ? ($p['ppe_per_month'] / $p['day_divisor']) * $paidDays : 0.0; // V
        $lwf = ($p['lwf_per_month'] / $p['day_divisor']) * $paidDays;                         // W
        $contribution = $pfEmployer + $esicEmployer + $ppe + $lwf;                            // X
        $ctc = $gross + $contribution;                                                        // Y

        $serviceCharge = $ctc * ($p['service_charge_pct'] / 100) / $p['service_charge_divisor']; // Z
        $grossAmount = $ctc + $serviceCharge;                                                 // AA
        $gst = $grossAmount * $p['gst_pct'] / 100;                                            // AB

        return [
            'earn_wages' => (float) $earn,
            'ot_amount' => (float) $ot,
            'bonus' => (float) $bonus,
            'leave_wages' => (float) $leave,
            'pmjjby' => (float) $pmjjby,
            'pmsby' => (float) $pmsby,
            'gross' => (float) $gross,
            'pf_employee' => (float) $pfEmployee,
            'esic_employee' => (float) $esicEmployee,
            'total_deduction' => (float) $totalDeduction,
            'net_salary' => (float) $net,
            'pf_employer' => (float) $pfEmployer,
            'esic_employer' => (float) $esicEmployer,
            'ppe' => (float) $ppe,
            'lwf' => (float) $lwf,
            'total_contribution' => (float) $contribution,
            'total_ctc' => (float) $ctc,
            'service_charge' => (float) $serviceCharge,
            'gross_amount' => (float) $grossAmount,
            'gst' => (float) $gst,
            'amount_incl_gst' => (float) ($grossAmount + $gst),
        ];
    }
}
