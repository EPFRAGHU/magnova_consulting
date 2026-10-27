<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 portrait; margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10.5px; line-height: 1.2; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .sheet-page { width: 8.27in; }
        .page { width: 7.50in; margin: 0.35in; }
        .frame { width: 7.50in; border: 1.4px solid #000; }
        .frame > tbody > tr > td { border-top: 1.4px solid #000; padding: 8px 10px; }
        .frame > tbody > tr:first-child > td { border-top: none; }
        .topline td { font-size: 10.5px; font-weight: bold; }
        .topline .title { text-align: center; font-size: 15px; }
        .topline .right { text-align: right; }
        .company { text-align: center; }
        .company-logo { width: 52px; height: auto; margin-bottom: 2px; }
        .company .name { font-size: 18px; font-weight: bold; }
        .company .service { font-size: 11.5px; margin-top: 3px; }
        .company .meta { font-size: 9.5px; margin-top: 3px; }
        .two-col > tbody > tr > td { width: 50%; padding: 0; }
        .two-col > tbody > tr > td:first-child { border-right: 1.4px solid #000; padding-right: 10px; }
        .two-col > tbody > tr > td:last-child { padding-left: 10px; }
        .kv td { padding: 2px 0; font-size: 9.5px; }
        .kv .label { width: 98px; font-weight: bold; }
        .kv .colon { width: 9px; }
        .section-title { font-weight: bold; font-size: 11px; margin-bottom: 5px; }
        .summary-title { text-align: center; font-weight: bold; font-size: 11.5px; margin-bottom: 8px; }
        table.summary { table-layout: fixed; }
        table.summary th, table.summary td { border: 1px solid #000; padding: 6px 5px; font-size: 10px; }
        table.summary th { text-align: center; background: #eee; font-weight: bold; vertical-align: middle; }
        table.summary td.center { text-align: center; }
        table.summary td.amount { text-align: right; }
        table.charges { table-layout: fixed; margin-top: 10px; }
        table.charges td { padding: 4px 4px; font-size: 10px; }
        table.charges .label { width: 78%; }
        table.charges .amount { text-align: right; width: 22%; }
        table.charges .strong td { font-weight: bold; }
        table.charges .strong.top td { border-top: 1px solid #000; }
        .words-line { font-size: 10.5px; }
        .footer-col { width: 33.33%; padding: 0 8px; font-size: 9.25px; }
        .footer-col:not(:first-child) { border-left: 1.4px solid #000; }
        .footer-title { font-weight: bold; font-size: 10px; margin-bottom: 4px; }
        .sign-line { margin-top: 60px; border-top: 1px solid #000; padding-top: 4px; }
        .head { width: 100%; margin-bottom: 3px; }
        .head td { font-size: 10px; }
        .head .title { text-align: center; font-size: 12.5px; font-weight: bold; }
        table.annex th, table.annex td { border: 1px solid #000; padding: 4px 6px; font-size: 10px; }
        table.annex th { background: #eee; }
        table.annex td.r { text-align: right; }
        table.annex td.c { text-align: center; }
        table.annex tr.total td { font-weight: bold; }
        .annex-page { width: 7.6in; margin: 0 auto; page-break-before: always; padding-top: 0.3in; }
    </style>
</head>
<body>
@php
    $d = $data;
    $inter = $d['tax_mode'] === 'inter';
    $money = fn ($v) => number_format((float) $v, 2);
    $days = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $supplier = $d['supplier'];
    $buyer = $d['buyer'];
    // Old stored invoices have no 'shown' figures: work them out the same way from the saved total.
    $taxableShown = $d['taxable_shown'] ?? round((float) $d['taxable_value']);
    $taxShown = $d['tax_shown'] ?? ((float) $d['total'] - $taxableShown);
    $particulars = $d['summary_particulars'] ?? 'Handling Manpower Contract';
    $refNo = $d['ref_no'] ?? null;
    $coreName = strtoupper($supplier['name']);
    // Stored invoices from before this layout only carry the date as d-M-y.
    $billDate = isset($d['invoice_date_iso']) ? \Illuminate\Support\Carbon::parse($d['invoice_date_iso']) : \Illuminate\Support\Carbon::createFromFormat('d-M-y', $d['invoice_date']);
    $terms = trim((string) $d['declaration']);
@endphp

<div class="sheet-page">
<div class="page">
<table class="frame">
    <tbody>
        <tr>
            <td>
                <table class="topline">
                    <tr>
                        <td style="width:33%">Ref no: {{ $refNo ?: '—' }}</td>
                        <td class="title" style="width:34%">TAX INVOICE</td>
                        <td class="right" style="width:33%">Invoice No: {{ $d['invoice_no'] }}</td>
                    </tr>
                </table>
                <div class="company">
                    @if (!empty($logoDataUri))
                        <img class="company-logo" src="{{ $logoDataUri }}" alt="Magnova">
                    @endif
                    <div class="name">M/s {{ $coreName }}</div>
                    <div class="service">Security and Manpower Service Provider</div>
                    <div class="meta">CIN NO: {{ config('magnova.company_cin') ?: '—' }} &nbsp;&nbsp; GSTIN NO: {{ $supplier['gstin'] ?: '—' }}</div>
                    @if ($supplier['address'])
                        <div class="meta">{{ $supplier['address'] }}</div>
                    @endif
                </div>
                <table class="topline" style="margin-top:6px">
                    <tr>
                        <td style="width:50%">Bill for the month of {{ str_replace(' ', '-', $d['period_label']) }}</td>
                        <td class="right" style="width:50%">Date: {{ $billDate->format('d-m-Y') }}</td>
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
                                <tr><td class="label">Name</td><td class="colon">:</td><td>M/S {{ $buyer['name'] ?: '—' }}</td></tr>
                                <tr><td class="label">Address</td><td class="colon">:</td><td>{!! $buyer['address'] ? nl2br(e($buyer['address'])) : '—' !!}</td></tr>
                                <tr><td class="label">GSTIN</td><td class="colon">:</td><td>{{ $buyer['gstin'] ?: '—' }}</td></tr>
                            </table>
                        </td>
                        <td>
                            <div class="section-title">Details of Parties (Billed From)</div>
                            <table class="kv">
                                <tr><td class="label">Name</td><td class="colon">:</td><td>M/s {{ $supplier['name'] }}</td></tr>
                                <tr><td class="label">Address</td><td class="colon">:</td><td>{!! $supplier['address'] ? nl2br(e($supplier['address'])) : '—' !!}</td></tr>
                                <tr><td class="label">GSTIN</td><td class="colon">:</td><td>{{ $supplier['gstin'] ?: '—' }}</td></tr>
                                <tr><td class="label">PAN No.</td><td class="colon">:</td><td>{{ $supplier['pan'] ?: '—' }}</td></tr>
                                <tr><td class="label">Contact No.</td><td class="colon">:</td><td>{{ $supplier['phone'] ?? '—' }}</td></tr>
                                <tr><td class="label">Billing Period</td><td class="colon">:</td><td>{{ \Illuminate\Support\Carbon::parse($d['period_start'] ?? $d['period_label'])->format('d-m-Y') }} to {{ \Illuminate\Support\Carbon::parse($d['period_start'] ?? $d['period_label'])->endOfMonth()->format('d-m-Y') }}</td></tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="height:2.9in">
                <div class="summary-title">Summary of Duties Performed in the Month of {{ $d['period_label'] }}</div>
                <table class="summary">
                    <thead>
                        <tr>
                            <th style="width:9%">Sl No</th>
                            <th style="width:47%">Particulars (Rank)</th>
                            <th style="width:20%">SAC Code</th>
                            <th style="width:24%">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="center">1</td>
                            <td>{{ $particulars }}</td>
                            <td class="center">{{ $d['hsn'] }}</td>
                            <td class="amount">{{ $money($taxableShown) }}</td>
                        </tr>
                    </tbody>
                </table>
                <table class="charges">
                    <tr class="strong top"><td class="label">SUB-TOTAL (Taxable Value)</td><td class="amount">{{ $money($taxableShown) }}</td></tr>
                    <tr><td class="label">{{ $inter ? 'IGST' : 'GST' }} @ {{ number_format($d['tax_rate'], 2) }}%</td><td class="amount">{{ $money($taxShown) }}</td></tr>
                    <tr class="strong top"><td class="label">GRAND TOTAL</td><td class="amount">{{ $money($d['total']) }}</td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 10px">
                <div class="words-line">Amount in words: <b>{{ $d['total_words'] }} only.</b></div>
            </td>
        </tr>
        <tr>
            <td style="padding:0; vertical-align:bottom">
                <table>
                    <tr>
                        <td class="footer-col">
                            <div class="footer-title">Bank Details</div>
                            <div>Bank: {{ $d['bank']['bank'] ?: '—' }}</div>
                            <div>Account No.: {{ $d['bank']['account'] ?: '—' }}</div>
                            <div>IFSC Code: {{ $d['bank']['ifsc'] ?: '—' }}</div>
                            <div>Branch: {{ $d['bank']['branch'] ?: '—' }}</div>
                        </td>
                        <td class="footer-col">
                            <div class="footer-title">Terms and Conditions</div>
                            <div>{!! nl2br(e($terms)) !!}</div>
                        </td>
                        <td class="footer-col">
                            <div class="footer-title">For M/s {{ $supplier['name'] }}</div>
                            <div class="sign-line">Authorized Signature</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody>
</table>
</div>
</div>

<div class="annex-page">
    <table class="head">
        <tr><td class="title">ANNEXURE TO TAX INVOICE</td></tr>
    </table>
    <p style="font-size:10px; text-align:center; margin:4px 0 10px">
        Invoice No. <b>{{ $d['invoice_no'] }}</b> dated <b>{{ $d['invoice_date'] }}</b><br>
        Man-days statement for {{ $d['period_label'] }} — {{ $d['consignee']['name'] }}
    </p>
    <table class="annex">
        <thead>
            <tr><th style="width:8%">Sl No.</th><th>Category</th><th style="width:16%">No. of persons</th><th style="width:16%">Total mandays</th><th style="width:20%">Amount</th></tr>
        </thead>
        <tbody>
            @foreach ($d['lines'] as $line)
                <tr>
                    <td class="c">{{ $line['sl'] }}</td><td>{{ $line['particulars'] }}</td>
                    <td class="c">{{ $line['persons'] }}</td><td class="r">{{ $days($line['mandays']) }}</td><td class="r">{{ $money($line['amount']) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td></td><td class="r">Total (before tax)</td>
                <td class="c">{{ $d['total_persons'] }}</td><td class="r">{{ $days($d['total_mandays']) }}</td><td class="r">{{ $money($d['taxable_value']) }}</td>
            </tr>
        </tbody>
    </table>
    <p style="font-size:9px; margin-top:10px">The taxable value on the invoice is this total rounded to the whole rupee. The register of attendance and register of wages for the month are furnished separately.</p>
</div>
</body>
</html>
