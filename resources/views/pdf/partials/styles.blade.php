<style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        font-size: 11px;
        color: #1a1a1a;
    }
    table { width: 100%; border-collapse: collapse; }

    /* ---- Payslip ---- */
    .payslip-header-table td { vertical-align: middle; }
    .payslip-header-table .logo-cell { width: 50px; }
    .payslip-header-table .company-name { font-size: 16px; font-weight: bold; color: #1d3557; }
    .payslip-header-table .company-address { font-size: 10px; color: #555; }
    .payslip-band { background: #1d3557; color: #fff; text-align: center; padding: 6px; font-size: 12px; font-weight: bold; margin: 10px 0; }

    .pay-summary { margin-bottom: 10px; }
    .pay-summary td { border: 1px solid #ddd; padding: 4px 6px; font-size: 10px; }
    .pay-summary .label { background: #f2f4f7; font-weight: bold; width: 21%; }
    .pay-summary .c { width: 2%; }
    .pay-summary .value { width: 27%; }

    .earn-deduct th { background: #1d3557; color: #fff; padding: 5px; font-size: 10px; text-align: left; }
    .earn-deduct td { border: 1px solid #ddd; padding: 5px; font-size: 10px; }
    .earn-deduct .amt { text-align: right; }
    .earn-deduct .total-row td { font-weight: bold; background: #f2f4f7; }

    .net-payable { background: #f2f4f7; border: 1px solid #1d3557; text-align: center; padding: 8px; font-size: 12px; font-weight: bold; margin-top: 10px; }
    .footer-note { text-align: center; font-size: 8px; color: #777; margin-top: 10px; }

    .page-break { page-break-after: always; }
</style>
