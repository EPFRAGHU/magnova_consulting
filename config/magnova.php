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

    // HSN/SAC code for manpower supply services shown on the tax invoice.
    'hsn_sac_code' => '998525',
    'state' => 'Odisha',
    'company_cin' => env('MAGNOVA_COMPANY_CIN'),
    'bank_account_type' => env('MAGNOVA_BANK_ACCOUNT_TYPE'),

    // Verified against a real Magnova tax invoice.
    'default_terms_and_conditions' => "E & O.E\n"
        ."1. Total payment due in 30 days.\n"
        ."2. Interest @ 18% p.a. will be charged if payment is delayed beyond terms.\n"
        ."3. Subject to 'Bhubaneswar' jurisdiction only.",
];
