<?php

/*
 * Version history shown on the Version history page (open it from the version line in the sidebar).
 *
 * Newest release first. For every release that goes to main, add an entry at the TOP: the next version number,
 * the date and time it was released (India time, YYYY-MM-DD HH:MM), a short title and what changed in plain words.
 * The sidebar version, its date and the "Latest" tag all come from the first entry, so nothing else needs editing.
 *
 * Numbering: MAJOR.MINOR.PATCH. A new feature raises MINOR, a fix raises PATCH, MAJOR is for a big change of the whole
 * app. Releases before 1.0.0 were rebuilt afterwards from the project's git history, grouped by what shipped together.
 *
 * Change types: new (something added), improved (something changed or made better), fixed (a problem corrected).
 */
return [
    [
        'version' => '1.9.0',
        'released_at' => '2026-11-04 09:15',
        'title' => 'Client name in the top bar',
        'changes' => [
            ['new', 'The top bar shows the name, site and address of the client being worked on in Clients, Guards & Staff, Attendance, Payroll, Invoices and Payslips, so the user always sees whose data they are entering.'],
        ],
    ],
    [
        'version' => '1.8.0',
        'released_at' => '2026-11-04 08:55',
        'title' => 'Same look on every module',
        'changes' => [
            ['improved', 'Edit Client is split into clear cards: Establishment, Billing details, Billing period, Contact, how the client is billed, and Status and payroll rules.'],
            ['improved', 'Page titles use the same bold style in Guards & Staff, Attendance, Payroll, Invoices and Payslips.'],
            ['improved', 'The client and month bar sits in a rounded card, and the Employees, Invoices and Payslips lists have the shaded header and row highlight of the Clients page.'],
            ['improved', 'The employee and invoice forms sit in a rounded card, and Edit in the employee list is a pencil icon.'],
        ],
    ],
    [
        'version' => '1.7.0',
        'released_at' => '2026-11-04 08:35',
        'title' => 'New Clients page',
        'changes' => [
            ['improved', 'Clients page redesigned: a total count, uppercase headings, GSTIN chips, staff count badges, soft coloured status badges and icon buttons for edit and delete.'],
            ['new', 'Export CSV button downloads the clients on screen as a spreadsheet.'],
            ['new', 'Status filter (All, Active, Inactive) and a reset button next to the search.'],
            ['new', 'A copy button next to each client phone number.'],
            ['improved', 'Search finds a client by name, location, GSTIN, contact person or phone.'],
        ],
    ],
    [
        'version' => '1.6.0',
        'released_at' => '2026-11-04 07:30',
        'title' => 'Version history',
        'changes' => [
            ['new', 'Version history page: every release with its version number, date and time, and what changed. Open it from the version line in the left panel.'],
        ],
    ],
    [
        'version' => '1.5.0',
        'released_at' => '2026-11-04 07:25',
        'title' => 'Own tax invoice designs for AAD College and Indian Bank Zonal',
        'changes' => [
            ['new', 'Tax invoice design setting on every client. AAD College prints the AAD Gopalpur layout and rounds the total to the rupee.'],
            ['new', 'Indian Bank Zonal Office prints its own lettered-row layout in exact paise.'],
            ['improved', 'These two clients charge GST on the taxable value including the service charge, and their Register of Wages follows the same rule.'],
            ['fixed', 'The company CIN printed on invoices is corrected to U74999OR2019PTC030507.'],
        ],
    ],
    [
        'version' => '1.4.0',
        'released_at' => '2026-11-04 00:09',
        'title' => 'Load switch: one client at a time',
        'changes' => [
            ['new', 'Load switch on Clients & Sites. The client switched ON is the one shown in Guards & Staff, Attendance, Payroll, Invoices and Payslips.'],
            ['improved', 'Each user keeps their own choice. The first active client is loaded until another is picked.'],
            ['improved', 'The client dropdown on those pages is replaced by the loaded client name with a Change client link.'],
        ],
    ],
    [
        'version' => '1.3.0',
        'released_at' => '2026-11-03 23:38',
        'title' => 'Signature column on the Register of Wages',
        'changes' => [
            ['new', 'A blank Signature column after the Net Payable column, on the Payroll screen and in the Register of Wages PDF.'],
        ],
    ],
    [
        'version' => '1.2.0',
        'released_at' => '2026-11-03 23:24',
        'title' => 'Monthly billing period per client',
        'changes' => [
            ['new', 'Each client can set its own From day and To day, such as the 26th of the month before to the 25th, printed on every invoice.'],
            ['improved', 'A period typed on one invoice still wins for that invoice. Blank days keep the whole calendar month.'],
        ],
    ],
    [
        'version' => '1.1.0',
        'released_at' => '2026-11-03 22:56',
        'title' => 'GEMC number prints as the Ref no',
        'changes' => [
            ['improved', "The tax invoice prints the client's GEMC No. as its Ref no without typing it every month. A Ref no typed on the invoice still wins."],
        ],
    ],
    [
        'version' => '1.0.0',
        'released_at' => '2026-11-02 10:26',
        'title' => 'Version number, footer and legal pages',
        'changes' => [
            ['new', 'Version number and date in the left panel, with the date the project started.'],
            ['new', 'Dashboard footer with the developer credit, All rights reserved, and links to the Terms & Conditions and Privacy Policy pages.'],
            ['new', 'Per-client bonus rules: percentage, rupees per day or fixed per month, with a wage limit and a choice of billing and ESIC treatment.'],
        ],
    ],
    [
        'version' => '0.9.4',
        'released_at' => '2026-11-01 20:01',
        'title' => 'Regular tax invoice layout and typed billing period',
        'changes' => [
            ['improved', 'Regular tax invoice: SAC code only, ranks from high to low, bigger type, one full page, space above the signature, and the company CIN.'],
            ['new', 'The billing period on an invoice can be typed in by the user.'],
            ['fixed', 'The company CIN falls back to its default when the setting is blank.'],
        ],
    ],
    [
        'version' => '0.9.3',
        'released_at' => '2026-10-27 23:27',
        'title' => 'Exact amounts and PF account number',
        'changes' => [
            ['improved', 'No amount is rounded to the rupee any more. Every figure is exact to the paisa on wages, invoices and payslips.'],
            ['fixed', 'Payslips and invoice lists show exactly two decimals.'],
            ['improved', 'Payslips print the full PF A/C number: the establishment code followed by the 7-digit member ID.'],
        ],
    ],
    [
        'version' => '0.9.2',
        'released_at' => '2026-10-27 20:51',
        'title' => 'Contract-labour payslips and bill design',
        'changes' => [
            ['new', 'Payslips for contract-labour units, made from the saved Register of Wages.'],
            ['improved', 'The contract-labour tax invoice follows the one-page bill design, with the rank-wise annexure on page two.'],
        ],
    ],
    [
        'version' => '0.9.1',
        'released_at' => '2026-10-24 00:08',
        'title' => 'Clearer contract-labour attendance grid',
        'changes' => [
            ['improved', 'Attendance days and overtime boxes are bordered squares, so blank days no longer look like empty space.'],
        ],
    ],
    [
        'version' => '0.9.0',
        'released_at' => '2026-10-20 01:23',
        'title' => 'Contract-labour client profile',
        'changes' => [
            ['new', 'Contract-labour client profile with its own attendance grid, saved wage sheet and tax invoice.'],
            ['new', 'Registers of Attendance, Wages, Fines and Deductions as A4 and A3 PDFs.'],
            ['improved', 'Code tidied across the project and automatic checks run on every change.'],
        ],
    ],
    [
        'version' => '0.8.0',
        'released_at' => '2026-10-17 21:32',
        'title' => 'Statutory ESIC rules',
        'changes' => [
            ['new', 'ESIC coverage and contribution follow the statutory rules, with dated settings.'],
            ['new', 'Per-unit ESIC choice and past effective dates.'],
            ['improved', 'Shorter invoice terms and more space for the signature.'],
        ],
    ],
    [
        'version' => '0.7.0',
        'released_at' => '2026-10-17 02:02',
        'title' => 'New workspace look',
        'changes' => [
            ['new', 'Sidebar workspace layout and a new dashboard built from real data.'],
            ['new', 'Public home page and a new sign-in page.'],
            ['improved', 'MAGNOVA is the visible name everywhere.'],
        ],
    ],
    [
        'version' => '0.6.0',
        'released_at' => '2026-10-16 21:02',
        'title' => 'GST options, regenerate prompts and maintenance banner',
        'changes' => [
            ['new', 'Official GST services classification with a SAC code search in Settings.'],
            ['new', 'No GST option per unit: no CGST or SGST on the register, invoice totals and tax invoice.'],
            ['new', 'Scrolling maintenance banner on every page, switched on and off from Settings.'],
            ['improved', 'The app asks to regenerate a unit\'s draft invoices after its bonus, service charge or GST setting changes.'],
            ['improved', 'Regenerating an invoice keeps its number.'],
            ['improved', 'Coloured status chips on the Clients list.'],
            ['fixed', 'White screen caused by the maintenance banner.'],
        ],
    ],
    [
        'version' => '0.5.0',
        'released_at' => '2026-10-16 01:10',
        'title' => 'SAC codes and designations',
        'changes' => [
            ['new', 'SAC code for each designation, shown on the tax invoice.'],
            ['new', 'Designations kept as a managed master list with SAC code and description.'],
            ['improved', 'The app only checks the database certificate when one is configured.'],
        ],
    ],
    [
        'version' => '0.4.0',
        'released_at' => '2026-10-14 23:48',
        'title' => 'Invoice numbering, bonus per unit and wage month',
        'changes' => [
            ['new', 'Tax invoice with a typed number under a fixed financial-year prefix, M/s name, SAC column, CGST and SGST, and bank details from Settings.'],
            ['new', 'Bonus is a setting for each unit and applies to saved months from September 2026.'],
            ['new', 'Service charge can be taken on basic or gross wages per unit.'],
            ['improved', 'Every module opens on the previous calendar month as the EPFO wage month.'],
            ['improved', 'Sessions and cache move to Redis.'],
        ],
    ],
    [
        'version' => '0.3.0',
        'released_at' => '2026-10-13 22:06',
        'title' => 'Skill categories and revised wage rules',
        'changes' => [
            ['new', 'Semi-Skilled and High-Skilled employee categories.'],
            ['improved', 'Employees are ordered High, Skilled, Semi-Skilled, Unskilled in every module.'],
            ['improved', 'Attendance shows the Designation column after the employee.'],
            ['improved', 'Revised September 2026 Register of Wages rules (EPF ceiling 15,000 and 25,000 blend, ESIC and bonus nil above 21,000), also applied to payslips and invoices.'],
        ],
    ],
    [
        'version' => '0.2.0',
        'released_at' => '2026-09-22 20:51',
        'title' => 'OTP login and Register of Wages rewrite',
        'changes' => [
            ['new', 'Sign in with a mobile OTP.'],
            ['new', 'Establishment settings and employee gender.'],
            ['improved', 'The Register of Wages was rewritten.'],
        ],
    ],
    [
        'version' => '0.1.1',
        'released_at' => '2026-09-12 10:43',
        'title' => 'Ready to go live',
        'changes' => [
            ['improved', 'Set up for hosting on Coolify with a cloud database, with HTTPS working behind the proxy.'],
            ['improved', 'Light theme, risky account actions removed and the ECR menu item hidden.'],
        ],
    ],
    [
        'version' => '0.1.0',
        'released_at' => '2026-09-12 07:40',
        'title' => 'Project started',
        'changes' => [
            ['new', 'First version of the Magnova manpower billing and payroll app.'],
        ],
    ],
];
