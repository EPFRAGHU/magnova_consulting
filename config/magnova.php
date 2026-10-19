<?php

return [
    'epf_employee_pct' => 12.00,
    'esic_employee_pct' => 0.75,
    'bonus_pct' => 8.33,
    'bonus_cap' => 21000,
    'epf_employer_pct' => 13.00,
    'epf_admin_pct' => 0.50,
    'edli_cont_pct' => 0.50,
    'esi_employer_pct' => 3.25,
    'service_charge_pct' => 3.85,
    'cgst_pct' => 9.00,
    'sgst_pct' => 9.00,

    // Register of Wages ceilings. EPF/EDLI wage ceiling rose from 15,000 to
    // 25,000 from 17 Sep 2026; months are blended day-by-day around the change.
    'epf_wage_ceiling' => 15000,
    'epf_wage_ceiling_changes' => [
        ['from' => '2026-09-17', 'ceiling' => 25000],
    ],
    // Legacy cap used for months before register_rules_from (ESIC and bonus were computed on
    // MIN(wage, 21,000) then). It is no longer the ESIC rule from register_rules_from onwards.
    'esic_wage_ceiling' => 21000,
    // Bonus is paid only when Monthly Wage is at or below this amount (separate from ESIC).
    'bonus_eligibility_ceiling' => 21000,

    // Defaults for the ESIC rules when an establishment has saved no settings of its own.
    // Real values live in the esic_settings table (Settings > ESIC) and are effective-dated.
    'esic' => [
        'establishment_covered' => true,
        // 'statutory' follows the ESI Act; 'capped' is a non-statutory business mode.
        'calculation_mode' => 'statutory',
        'coverage_ceiling' => 21000,
        'employee_rate' => 0.75,
        'employer_rate' => 3.25,
        // Employees with an average daily wage up to this amount pay no employee share (null = off).
        'daily_wage_exemption' => 176,
        'include_bonus' => false,
        // nearest | up | down, to a whole rupee.
        'rounding' => 'nearest',
        // Contribution periods start in these months (Apr-Sep and Oct-Mar).
        'contribution_period_start_months' => [4, 10],
    ],
    // From this month the register rounds to whole rupees and drops ESIC and
    // bonus entirely above the ceiling; earlier months keep the older logic.
    'register_rules_from' => '2026-09-01',

    // Wage-sheet rules for a contract-labour client (principal employer arrangement), copied from the client's own
    // Wages sheet (Balmer Lawrie, September 2026). Each client can override any of these in clients.payroll_parameters.
    'contract_labour' => [
        'hours_per_day' => 8,
        'ot_multiplier' => 2,
        'bonus_pct' => 8.33,                // of earned wages, no ceiling
        'leave_factor' => 1.25,             // leave wages = rate x 1.25 / day_divisor per paid day
        'day_divisor' => 26,
        'pmjjby_per_year' => 436,
        'pmsby_per_year' => 20,
        'pf_employee_pct' => 12,            // on earned wages within the EPF ceiling of the month
        'pf_employer_pct' => 13,
        'esic_employee_pct' => 0.75,        // on earned wages + overtime, no ceiling (as in the client's sheet)
        'esic_employer_pct' => 3.25,
        'ppe_per_month' => 750,
        'ppe_enabled' => false,             // the sheet multiplies PPE by 0
        'lwf_per_month' => 5,
        'service_charge_pct' => 3.85,       // service charge = total CTC x pct / divisor
        'service_charge_divisor' => 1.18,
        'gst_pct' => 18,
    ],

    // HSN/SAC code for manpower supply services shown on the tax invoice.
    'hsn_sac_code' => '998525',
    'state' => 'Odisha',
    'company_cin' => env('MAGNOVA_COMPANY_CIN'),
    'bank_account_type' => env('MAGNOVA_BANK_ACCOUNT_TYPE'),

    // Shown on the tax invoice unless the establishment has saved its own text. The payment-term and
    // interest points were removed at the owner's request (17 Oct 2026).
    'default_terms_and_conditions' => "E & O.E\n"
        ."1. Subject to 'Bhubaneswar' jurisdiction only.",
];
