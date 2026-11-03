<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 portrait; margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11.5px; line-height: 1.25; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .page { width: 7.65in; margin: 0.3in 0.31in; }
        .frame { width: 7.65in; border: 1.4px solid #000; }
        .frame > tbody > tr > td { border-top: 1.4px solid #000; padding: 10px 12px; }
        .frame > tbody > tr:first-child > td { border-top: none; }
        .topline td { font-size: 11.5px; font-weight: bold; }
        .topline .title { text-align: center; font-size: 17px; }
        .topline .right { text-align: right; }
        .company { text-align: center; }
        .company-logo { width: 58px; height: auto; margin-bottom: 3px; }
        .company .name { font-size: 20px; font-weight: bold; }
        .company .service { font-size: 12.5px; margin-top: 4px; }
        .company .meta { font-size: 10.5px; margin-top: 4px; }
        .two-col > tbody > tr > td { width: 50%; padding: 0; border-top: none; }
        .two-col > tbody > tr > td:first-child { border-right: 1.4px solid #000; padding-right: 12px; }
        .two-col > tbody > tr > td:last-child { padding-left: 12px; }
        .kv td { padding: 3px 0; font-size: 10.5px; }
        .kv .label { width: 104px; font-weight: bold; }
        .kv .colon { width: 10px; }
        .section-title { font-weight: bold; font-size: 12px; margin-bottom: 6px; }
        .summary-title { text-align: center; font-weight: bold; font-size: 12.5px; margin-bottom: 8px; }
        table.summary { table-layout: fixed; }
        table.summary th, table.summary td { border: 1px solid #000; padding: 7px 5px; font-size: 10.5px; }
        table.summary th { text-align: center; background: #eee; font-weight: bold; vertical-align: middle; }
        table.summary td.center, table.summary th.center { text-align: center; }
        table.summary td.amount { text-align: right; }
        table.summary .col-sl { width: 6%; }
        table.summary .col-rank { width: 26%; }
        table.summary .col-sac { width: 12%; }
        table.summary .col-rate { width: 16%; }
        table.summary .col-persons { width: 13%; }
        table.summary .col-duties { width: 12%; }
        table.summary .col-amount { width: 15%; }
        table.summary .total td { font-weight: bold; }
        table.charges { table-layout: fixed; margin-top: 10px; }
        table.charges td { padding: 4px 4px; font-size: 10.5px; word-wrap: break-word; }
        table.charges .label { width: 78%; }
        table.charges .amount { text-align: right; width: 22%; }
        table.charges .strong td { font-weight: bold; border-top: 1px solid #000; }
        .words-line { font-size: 11.5px; margin: 4px 0 6px; }
        .tax-line { font-size: 10.5px; margin-top: 5px; }
        .footer-col { width: 33.33%; padding: 0 10px; font-size: 10px; word-wrap: break-word; }
        .footer-col:not(:first-child) { border-left: 1.4px solid #000; }
        .footer-title { font-weight: bold; font-size: 11px; margin-bottom: 5px; }
        /* Many designations: tighter rows so the invoice still fits on one page. */
        .compact table.summary th, .compact table.summary td { padding: 3px 5px; }
        .compact table.charges { margin-top: 5px; }
        .compact table.charges td { padding: 2px 4px; }
        .compact .frame > tbody > tr > td { padding-top: 7px; padding-bottom: 7px; }
        .sign-line { margin-top: 0.95in; border-top: 1px solid #000; padding-top: 4px; }    </style>
</head>
<body class="{{ $dutySummary->count() > 6 ? 'compact' : '' }}">
@php
    $tenant = $invoice->tenant;
    $client = $invoice->client;
    $bonusRule = $bonusRule ?? $client->bonusRule();
    $monthName = \Carbon\Carbon::create()->month($invoice->month)->format('F');
    // Typed on the invoice, else the client's monthly period, else the whole calendar month.
    [$billingPeriodStart, $billingPeriodEnd] = $invoice->billingPeriod();
    $hsnSac = config('magnova.hsn_sac_code');
    $supplierCoreName = trim(preg_replace('~^\s*m/s\.?\s*~i', '', (string) $tenant->name)) ?: 'Magnova Consulting Pvt. Ltd.';
    $supplierName = 'M/s '.$supplierCoreName;
    $receiverName = $client->billingName();
    $receiverAddress = $client->billingAddress();
    $supplierAddress = $tenant->address;
    $epfo = (float) $invoice->epf_employer;
    $edliAndAdmin = (float) $invoice->epf_admin + (float) $invoice->edli_cont;
    $esic = (float) $invoice->esi_employer;
    $taxableSubtotal = (float) $invoice->taxable_value;
@endphp

<div class="page">
<table class="frame">
    <tbody>
        <tr>
            <td>
                <table class="topline">
                    <tr>
                        <td style="width:33%">Ref no: {{ $invoice->printedRefNo() ?: '—' }}</td>
                        <td class="title" style="width:34%">TAX INVOICE</td>
                        <td class="right" style="width:33%">Invoice No: {{ $invoice->invoice_no }}</td>
                    </tr>
                </table>
                <div class="company">
                    @if (!empty($logoDataUri))
                        <img class="company-logo" src="{{ $logoDataUri }}" alt="Magnova">
                    @endif
                    <div class="name">M/s {{ strtoupper($supplierCoreName) }}</div>
                    <div class="service">Security and Manpower Service Provider</div>
                    <div class="meta">CIN NO: {{ config('magnova.company_cin') ?: '—' }} &nbsp;&nbsp; GSTIN NO: {{ $tenant->gstin ?: '—' }}</div>
                    @if ($supplierAddress)
                        <div class="meta">{{ $supplierAddress }}</div>
                    @endif
                </div>
                <table class="topline" style="margin-top:6px">
                    <tr>
                        <td style="width:50%">Bill for the month of {{ $monthName }}-{{ $invoice->year }}</td>
                        <td class="right" style="width:50%">Date: {{ optional($invoice->invoice_date)->format('d-m-Y') ?: '—' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="two-col">
                    <tr>
                        <td>
                            <div class="section-title">Details of Receiver (Billed To)</div>
                            <table class="kv">
                                <tr><td class="label">Name</td><td class="colon">:</td><td>{{ $receiverName ?: '—' }}</td></tr>
                                <tr><td class="label">Address</td><td class="colon">:</td><td>{!! $receiverAddress ? nl2br(e($receiverAddress)) : '—' !!}</td></tr>
                                <tr><td class="label">GSTIN</td><td class="colon">:</td><td>{{ $client->gstin ?: '—' }}</td></tr>
                            </table>
                        </td>
                        <td>
                            <div class="section-title">Details of Parties (Billed From)</div>
                            <table class="kv">
                                <tr><td class="label">Name</td><td class="colon">:</td><td>{{ $supplierName }}</td></tr>
                                <tr><td class="label">Address</td><td class="colon">:</td><td>{!! $supplierAddress ? nl2br(e($supplierAddress)) : '—' !!}</td></tr>
                                <tr><td class="label">GSTIN</td><td class="colon">:</td><td>{{ $tenant->gstin ?: '—' }}</td></tr>
                                <tr><td class="label">PAN No.</td><td class="colon">:</td><td>{{ $tenant->pan ?: '—' }}</td></tr>
                                <tr><td class="label">Contact No.</td><td class="colon">:</td><td>{{ $tenant->contact_phone ?: '—' }}</td></tr>
                                <tr><td class="label">Billing Period</td><td class="colon">:</td><td>{{ $billingPeriodStart->format('d-m-Y') }} to {{ $billingPeriodEnd->format('d-m-Y') }}</td></tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            {{-- Extra height pushes the box down to the bottom of the page; it shrinks a little per extra designation. --}}
            <td style="height:{{ number_format($dutySummary->count() <= 6 ? 5.08 : max(2.5, 5.08 - 0.12 * ($dutySummary->count() - 5)), 2, '.', '') }}in">
                <div class="summary-title">Summary of Duties Performed in the Month of {{ $monthName }} {{ $invoice->year }}</div>
                <table class="summary">
                    <thead>
                        <tr>
                            <th class="col-sl">Sl No</th>
                            <th class="col-rank">Particulars (Rank)</th>
                            <th class="col-sac">SAC Code</th>
                            <th class="col-rate">Rate Per Person Per Day</th>
                            <th class="col-persons">No. of Persons Engaged</th>
                            <th class="col-duties">No. of Duties</th>
                            <th class="col-amount">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dutySummary as $index => $item)
                            <tr>
                                <td class="center">{{ $index + 1 }}</td>
                                <td>{{ $item['rank'] }}</td>
                                <td class="center">{{ $item['sac'] ?? $hsnSac }}</td>
                                <td class="amount">{{ number_format($item['rate'], 2) }}</td>
                                <td class="center">{{ $item['persons'] }}</td>
                                <td class="center">{{ number_format($item['duties'], 2) }}</td>
                                <td class="amount">{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total">
                            <td colspan="6">TOTAL WAGES</td>
                            <td class="amount">{{ number_format($invoice->base_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
                <table class="charges">
                    <tr><td class="label">EPFO @ {{ number_format(config('magnova.epf_employee_pct'), 2) }}% on capped monthly wages</td><td class="amount">{{ number_format($epfo, 2) }}</td></tr>
                    <tr><td class="label">EDLI and EPF Admin Charges @ {{ number_format(config('magnova.epf_admin_pct') + config('magnova.edli_cont_pct'), 2) }}% on capped monthly wages</td><td class="amount">{{ number_format($edliAndAdmin, 2) }}</td></tr>
                    <tr><td class="label">ESIC @ {{ number_format(app(\App\Services\EsicCalculator::class)->settingsFor($invoice->tenant_id, $invoice->month, $invoice->year)->employer_rate, 2) }}% on ESIC wages</td><td class="amount">{{ number_format($esic, 2) }}</td></tr>
                    @if ($bonusRule->enabled && $bonusRule->billed)
                    <tr><td class="label">{{ $bonusRule->invoiceLabel() }}</td><td class="amount">{{ number_format($bonus, 2) }}</td></tr>
                    @endif
                    <tr class="strong"><td class="label">SUB-TOTAL (Taxable Value)</td><td class="amount">{{ number_format($taxableSubtotal, 2) }}</td></tr>
                    <tr><td class="label">Service Charges @ {{ number_format(config('magnova.service_charge_pct'), 2) }}% on {{ $client->service_charge_on_basic ? 'Basic Wages' : 'Gross Wages' }}</td><td class="amount">{{ number_format($invoice->service_charge, 2) }}</td></tr>
                    @php $gstCharged = ((float) $invoice->cgst + (float) $invoice->sgst) > 0; @endphp
                    <tr><td class="label">CGST @ {{ number_format($gstCharged ? config('magnova.cgst_pct') : 0, 2) }}%</td><td class="amount">{{ number_format($invoice->cgst, 2) }}</td></tr>
                    <tr><td class="label">SGST @ {{ number_format($gstCharged ? config('magnova.sgst_pct') : 0, 2) }}%</td><td class="amount">{{ number_format($invoice->sgst, 2) }}</td></tr>
                    <tr class="strong"><td class="label">GRAND TOTAL</td><td class="amount">{{ number_format($invoice->grand_total, 2) }}</td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <div class="words-line">Amount in words: {{ $amountInWords }} Only</div>
                <div class="tax-line">Taxable Value: {{ number_format($taxableSubtotal, 2) }} &nbsp;|&nbsp; CGST: {{ number_format($invoice->cgst, 2) }} &nbsp;|&nbsp; SGST: {{ number_format($invoice->sgst, 2) }} &nbsp;|&nbsp; Invoice Balance: {{ number_format($invoice->grand_total, 2) }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:0">
                <table>
                    <tr>
                        <td class="footer-col">
                            <div class="footer-title">Bank Details</div>
                            <div>Bank: {{ $tenant->bank_name ?: '—' }}</div>
                            <div>Account No.: {{ $tenant->bank_account_no ?: '—' }}</div>
                            @if (config('magnova.bank_account_type'))
                            <div>Account Type: {{ config('magnova.bank_account_type') }}</div>
                            @endif
                            <div>IFSC Code: {{ $tenant->bank_ifsc ?: '—' }}</div>
                            <div>Branch: {{ $tenant->bank_branch ?: '—' }}</div>
                        </td>
                        <td class="footer-col">
                            <div class="footer-title">Terms and Conditions</div>
                            <div>{!! nl2br(e($tenant->terms_and_conditions ?: config('magnova.default_terms_and_conditions'))) !!}</div>
                        </td>
                        <td class="footer-col">
                            <div class="footer-title">For {{ $supplierName }}</div>
                            <div class="sign-line">Authorized Signature</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody>
</table>
</div>
</body>
</html>