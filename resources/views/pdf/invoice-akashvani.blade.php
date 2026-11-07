<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 portrait; margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11.5px; line-height: 1.3; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .page { width: 7.65in; margin: 0.3in 0.31in; }
        .head td { vertical-align: middle; }
        .head .logo { width: 1.25in; }
        .head .logo img { width: 1.15in; height: auto; }
        .head .name { font-size: 21px; font-weight: bold; color: #c00000; text-align: center; }
        .head .service { font-size: 15px; font-style: italic; font-family: 'Times', serif; color: #1f3f99; font-weight: bold; text-align: center; margin: 2px 0; }
        .head .ids { font-size: 11.5px; font-weight: bold; text-align: center; }
        .head .regd { font-size: 10.5px; font-weight: bold; color: #c00000; text-align: center; margin-top: 3px; }
        .rule { border-top: 1.4px solid #000; margin-top: 3px; }
        .refline td { font-weight: bold; padding-top: 3px; font-size: 11.5px; }
        .blank { display: inline-block; width: 1.9in; border-bottom: 1px solid #000; height: 11px; }
        .invoice-title { text-align: center; font-weight: bold; text-decoration: underline; margin: 3px 0 4px; font-size: 12px; }
        .two-col td { width: 50%; padding: 0; }
        .two-col td:last-child { padding-left: 0.2in; }
        .kv td { padding: 2px 0; font-size: 11.5px; }
        .kv td:first-child { width: 0.72in; text-decoration: underline; }
        .heading-u { text-decoration: underline; margin-top: 10px; }
        .bank { font-weight: bold; margin-top: 8px; line-height: 1.45; }
        table.summary { margin-top: 3px; table-layout: fixed; border: 1.4px solid #000; }
        table.summary th, table.summary td { border: 1px solid #000; padding: 3px 5px; font-size: 11.5px; }
        table.summary .title td { text-align: center; font-weight: bold; font-size: 12.5px; padding: 2px; }
        table.summary th { text-align: center; font-weight: normal; vertical-align: middle; font-size: 10.5px; }
        table.summary td.center { text-align: center; vertical-align: middle; }
        table.summary td.amount { text-align: right; }
        table.summary .strong td { font-weight: bold; }
        .sign { margin-top: 0.95in; margin-left: 0.2in; font-size: 11.5px; line-height: 1.4; }
        .bar { position: fixed; left: 0.31in; bottom: 0.55in; width: 7.65in; height: 0.27in; background: #c00000; }
    </style>
</head>
<body>
@php
    $tenant = $invoice->tenant;
    $client = $invoice->client;
    $bonusRule = $bonusRule ?? $client->bonusRule();
    [$billingPeriodStart, $billingPeriodEnd] = $invoice->billingPeriod();
    $monthShort = \Carbon\Carbon::create()->month($invoice->month)->format('M');
    $supplierCoreName = trim(preg_replace('~^\s*m/s\.?\s*~i', '', (string) $tenant->name)) ?: 'Magnova Consulting Pvt. Ltd.';
    $supplierAddress = $tenant->address;
    $money = fn ($v) => number_format((float) $v, 2);
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    // Wages plus EPF and ESIC; the service charge comes next, then GST is charged on both together.
    $subTotal = (float) $invoice->taxable_value;
    $taxable = round($subTotal + (float) $invoice->service_charge, 2);
    $sac = $client->sac_code ?: ($dutySummary->first()['sac'] ?? null) ?: config('magnova.hsn_sac_code');
    $gstCharged = ((float) $invoice->cgst + (float) $invoice->sgst) > 0;
    $typedRef = trim((string) $invoice->ref_no);
    $bank = collect([$tenant->bank_name, $tenant->bank_branch ? $tenant->bank_branch.' Branch' : null])->filter()->implode(' , ');
@endphp

<div class="page">
    <table class="head">
        <tr>
            <td class="logo">@if (!empty($logoDataUri))<img src="{{ $logoDataUri }}" alt="Magnova">@endif</td>
            <td>
                <div class="name">{{ strtoupper($supplierCoreName) }}</div>
                <div class="service">Security and Manpower Service Provider</div>
                <div class="ids">CIN NO: {{ config('magnova.company_cin') ?: '—' }}, GSTIN NO: {{ $tenant->gstin ?: '—' }}</div>
                @if ($supplierAddress)
                    <div class="regd">Regd. Office : {{ $supplierAddress }}</div>
                @endif
            </td>
        </tr>
    </table>
    <div class="rule"></div>
    <table class="refline">
        <tr>
            <td>Ref no: @if ($typedRef !== ''){{ $typedRef }}@else<span class="blank"></span>@endif</td>
            <td style="text-align:right">Date:<span class="blank"></span></td>
        </tr>
    </table>
    <div class="rule"></div>
    <div class="invoice-title">TAX INVOICE</div>

    <table class="two-col">
        <tr>
            <td>
                <div>Bill For The month of {{ $monthShort }}-{{ $invoice->year }}</div>
                <div class="heading-u">Details of Receiver (Billed To)</div>
                <table class="kv">
                    <tr><td>Name</td><td>To,<br>{!! nl2br(e($client->billingName())) !!}</td></tr>
                    <tr><td>Address</td><td>{!! $client->billingAddress() ? nl2br(e($client->billingAddress())) : '—' !!}</td></tr>
                </table>
                <table class="kv" style="margin-top:6px">
                    <tr><td style="text-decoration:none;width:0.9in">GST IN</td><td>: {{ $client->gstin ?: '—' }}</td></tr>
                    <tr><td style="text-decoration:none">SAC CODE</td><td>: {{ $sac }}</td></tr>
                </table>
            </td>
            <td>
                <table class="kv" style="margin-top:-2px">
                    <tr><td style="text-decoration:none;width:0.9in">Invoice No</td><td>: {{ $invoice->invoice_no }}</td></tr>
                    <tr><td style="text-decoration:none">Date</td><td>: {{ optional($invoice->invoice_date)->format('d-m-Y') ?: '—' }}</td></tr>
                    <tr><td style="text-decoration:none">Contact No</td><td>: {{ $tenant->contact_phone ?: '—' }}</td></tr>
                </table>
                <div>Details of Parties ( Billed From)</div>
                <table class="kv">
                    <tr><td style="text-decoration:none;width:0.9in">Name</td><td>: {{ strtoupper($tenant->name) }}</td></tr>
                    <tr><td style="text-decoration:none">Address</td><td>: {!! $supplierAddress ? nl2br(e($supplierAddress)) : '—' !!}</td></tr>
                    <tr><td style="text-decoration:none">GSTIN</td><td>: {{ $tenant->gstin ?: '—' }}</td></tr>
                    <tr><td style="text-decoration:none">PAN NO</td><td>: {{ $tenant->pan ?: '—' }}</td></tr>
                </table>
                <div>Billing Period : {{ $billingPeriodStart->format('d-m-Y') }} to {{ $billingPeriodEnd->format('d-m-Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="bank">
        @if ($client->gemc_no)
            <div>{{ $client->gemc_no }}</div>
        @endif
        <div>Bank Name : {{ $bank ?: '—' }}</div>
        <div>Account No: {{ $tenant->bank_account_no ?: '—' }}</div>
        <div>IFSC Code: {{ $tenant->bank_ifsc ?: '—' }}</div>
        @if (config('magnova.bank_account_type'))
            <div>Account Type: {{ config('magnova.bank_account_type') }}</div>
        @endif
    </div>

    <table class="summary">
        <colgroup>
            <col style="width:8%"><col style="width:26%"><col style="width:16%"><col style="width:17%"><col style="width:15%"><col style="width:18%">
        </colgroup>
        <tr class="title"><td colspan="6">Summary of Duties Performed in the Month of {{ $monthShort }} {{ $invoice->year }}</td></tr>
        <tr>
            <th>Sl No</th><th>Particulars (Rank)</th><th>Rate Per Person Per Day</th><th>No Of Person Engaged</th><th>No. Of Duties</th><th>Total Amount</th>
        </tr>
        @foreach ($dutySummary as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td>{{ $item['rank'] }}</td>
                <td class="center">{{ rtrim(rtrim(number_format($item['rate'], 2), '0'), '.') }}</td>
                <td class="center">{{ $item['persons'] }}</td>
                <td class="center">{{ rtrim(rtrim(number_format($item['duties'], 2), '0'), '.') }}</td>
                <td class="amount">{{ $money($item['amount']) }}</td>
            </tr>
        @endforeach
        <tr><td colspan="5">TOTAL WAGES</td><td class="amount">{{ $money($invoice->base_amount) }}</td></tr>
        @foreach ($epfLines as $line)
            <tr><td colspan="5">{{ $line['label'] }}</td><td class="amount">{{ $money($line['amount']) }}</td></tr>
        @endforeach
        @if ((float) $invoice->esi_employer > 0)
            <tr><td colspan="5">ESIC @ {{ $pct(app(\App\Services\EsicCalculator::class)->settingsFor($invoice->tenant_id, $invoice->month, $invoice->year)->employer_rate) }}% ON BASIC WAGES</td><td class="amount">{{ $money($invoice->esi_employer) }}</td></tr>
        @endif
        @if ($bonusRule->enabled && $bonusRule->billed)
            <tr><td colspan="5">{{ $bonusRule->invoiceLabel() }}</td><td class="amount">{{ $money($bonus) }}</td></tr>
        @endif
        <tr><td colspan="5">SUB-TOTAL</td><td class="amount">{{ $money($subTotal) }}</td></tr>
        <tr><td colspan="5">SERVICE CHARGES @ {{ $pct(config('magnova.service_charge_pct')) }}%</td><td class="amount">{{ $money($invoice->service_charge) }}</td></tr>
        <tr><td colspan="5">TOTAL</td><td class="amount">{{ $money($taxable) }}</td></tr>
        <tr><td colspan="5">CGST @ {{ $gstCharged ? $pct(config('magnova.cgst_pct')) : 0 }} % ON TOTAL WAGES &amp; SERVICE CHARGES</td><td class="amount">{{ $money($invoice->cgst) }}</td></tr>
        <tr><td colspan="5">SGST @ {{ $gstCharged ? $pct(config('magnova.sgst_pct')) : 0 }} % ON TOTAL WAGES &amp; SERVICE CHARGES</td><td class="amount">{{ $money($invoice->sgst) }}</td></tr>
        <tr class="strong"><td colspan="5">TOTAL</td><td class="amount">{{ $money($invoice->grand_total) }}</td></tr>    </table>

    <div class="sign">
        <div>{{ config('magnova.invoice_signatory_name') }} ( {{ config('magnova.invoice_signatory_title') }})</div>
        <div>M/s {{ $supplierCoreName }}</div>
        <div>Corp. Office : {{ config('magnova.corporate_office') }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ config('magnova.corporate_email') }}</div>
    </div>
</div>
<div class="bar"></div>
</body>
</html>
