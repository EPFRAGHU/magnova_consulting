<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 portrait; margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9.5px; line-height: 1.25; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }
        .page { width: 7.6in; margin: 0.3in auto 0; }
        .page-break { page-break-after: always; }
        .head { width: 100%; margin-bottom: 3px; }
        .head td { font-size: 10px; }
        .head .title { text-align: center; font-size: 12.5px; font-weight: bold; }
        .head .orig { text-align: right; }
        table.sheet { border: 1px solid #000; }
        table.sheet > tbody > tr > td { border: 1px solid #000; padding: 0; }
        .parties { padding: 0; }
        .block { padding: 4px 6px; font-size: 9px; }
        .block + .block { border-top: 1px solid #000; }
        .block .name { font-weight: bold; font-size: 10px; }
        .block .lbl { font-size: 8.5px; }
        .block table td { padding: 0; font-size: 9px; }
        .supplier-table td { vertical-align: top; }
        .logo { width: 46px; height: auto; }
        table.fields td { border: 1px solid #000; padding: 3px 5px; font-size: 8.5px; height: 30px; width: 50%; }
        table.fields td .v { font-weight: bold; font-size: 9.5px; display: block; margin-top: 2px; }
        table.fields td.full { height: auto; font-size: 8.5px; border-bottom: none; }
        table.fields tr:first-child td { border-top: none; }
        table.fields td:first-child { border-left: none; }
        table.fields td:last-child { border-right: none; }
        table.items th { border: 1px solid #000; padding: 3px 4px; font-size: 8.5px; font-weight: normal; text-align: center; }
        table.items td { padding: 2px 4px; font-size: 9.5px; border-left: 1px solid #000; border-right: 1px solid #000; }
        table.items td.r { text-align: right; }
        table.items td.c { text-align: center; }
        table.items td b { font-size: 10px; }
        table.items tr.total td { border: 1px solid #000; font-weight: bold; font-size: 10px; }
        table.items tr.filler td { height: 2.55in; }
        .words { padding: 4px 6px; font-size: 9.5px; }
        .words .small { font-size: 8.5px; }
        table.tax th, table.tax td { border: 1px solid #000; padding: 2px 4px; font-size: 8.5px; text-align: center; }
        table.tax td.r { text-align: right; }
        table.tax th { font-weight: normal; }
        table.tax tr.total td { font-weight: bold; }
        .tax-words { padding: 4px 6px; font-size: 9px; }
        table.foot > tbody > tr > td { padding: 4px 6px; font-size: 8.5px; }
        .sign-space { height: 60px; }
        .centered { text-align: center; font-size: 8.5px; margin-top: 6px; }
        table.annex th, table.annex td { border: 1px solid #000; padding: 4px 6px; font-size: 10px; }
        table.annex th { background: #eee; }
        table.annex td.r { text-align: right; }
        table.annex td.c { text-align: center; }
        table.annex tr.total td { font-weight: bold; }
    </style>
</head>
<body>
@php
    $d = $data;
    $inter = $d['tax_mode'] === 'inter';
    $money = fn ($v) => number_format((float) $v, 2);
    $days = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $declaration = preg_split('/\r\n|\r|\n/', (string) $d['declaration']);
@endphp

<div class="page page-break">
    <table class="head">
        <tr>
            <td style="width:25%"></td>
            <td class="title" style="width:50%">Tax Invoice</td>
            <td class="orig" style="width:25%">(ORIGINAL FOR RECIPIENT)</td>
        </tr>
    </table>

    <table class="sheet">
        <tbody>
        <tr>
            <td class="parties" style="width:52%">
                <div class="block">
                    <table class="supplier-table">
                        <tr>
                            @if (!empty($logoDataUri))
                                <td style="width:54px"><img class="logo" src="{{ $logoDataUri }}" alt=""></td>
                            @endif
                            <td>
                                <div class="name">{{ strtoupper($d['supplier']['name']) }}</div>
                                @if ($d['supplier']['address'])<div>{{ $d['supplier']['address'] }}</div>@endif
                                <div>GSTIN/UIN: {{ $d['supplier']['gstin'] ?: '—' }}</div>
                                <div>State Name : {{ $d['supplier']['state_name'] }}, Code : {{ $d['supplier']['state_code'] }}</div>
                                @if ($d['supplier']['email'])<div>E-Mail : {{ $d['supplier']['email'] }}</div>@endif
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="block">
                    <div class="lbl">Consignee (Ship to)</div>
                    <div class="name">M/S {{ $d['consignee']['name'] }}</div>
                    @if ($d['consignee']['address'])<div>{{ $d['consignee']['address'] }}</div>@endif
                    <table>
                        <tr><td style="width:62px">GSTIN/UIN</td><td>: {{ $d['consignee']['gstin'] ?: '—' }}</td></tr>
                        <tr><td>State Name</td><td>: {{ $d['consignee']['state_name'] }}, Code : {{ $d['consignee']['state_code'] }}</td></tr>
                    </table>
                </div>
                <div class="block">
                    <div class="lbl">Buyer (Bill to)</div>
                    <div class="name">M/S {{ $d['buyer']['name'] }}</div>
                    @if ($d['buyer']['address'])<div>{{ $d['buyer']['address'] }}</div>@endif
                    <table>
                        <tr><td style="width:62px">GSTIN/UIN</td><td>: {{ $d['buyer']['gstin'] ?: '—' }}</td></tr>
                        <tr><td>State Name</td><td>: {{ $d['buyer']['state_name'] }}, Code : {{ $d['buyer']['state_code'] }}</td></tr>
                    </table>
                </div>
            </td>
            <td class="parties" style="width:48%">
                <table class="fields">
                    <tr><td>Invoice No.<span class="v">{{ $d['invoice_no'] }}</span></td><td>Dated<span class="v">{{ $d['invoice_date'] }}</span></td></tr>
                    <tr><td>Delivery Note<span class="v">&nbsp;</span></td><td>Mode/Terms of Payment<span class="v">&nbsp;</span></td></tr>
                    <tr><td>Reference No. &amp; Date.<span class="v">&nbsp;</span></td><td>Other References<span class="v">&nbsp;</span></td></tr>
                    <tr><td>Buyer's Order No.<span class="v">{{ $d['buyer_order_no'] }}&nbsp;</span></td><td>Dated<span class="v">{{ $d['buyer_order_date'] }}&nbsp;</span></td></tr>
                    <tr><td>Dispatch Doc No.<span class="v">&nbsp;</span></td><td>Delivery Note Date<span class="v">&nbsp;</span></td></tr>
                    <tr><td>Dispatched through<span class="v">&nbsp;</span></td><td>Destination<span class="v">&nbsp;</span></td></tr>
                    <tr><td class="full" colspan="2">Terms of Delivery<br><b>{{ $d['terms_of_delivery'] }}</b><br>&nbsp;</td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <table class="items">
                    <thead>
                        <tr>
                            <th style="width:5%">Sl<br>No.</th>
                            <th style="width:37%">Particulars</th>
                            <th style="width:12%">HSN/SAC</th>
                            <th style="width:12%">Quantity<br>(Mandays)</th>
                            <th style="width:10%">Rate</th>
                            <th style="width:6%">per</th>
                            <th style="width:18%">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($d['lines'] as $line)
                            <tr>
                                <td class="c">{{ $line['sl'] }}</td>
                                <td><b>{{ $line['particulars'] }}</b><br>{{ $line['note'] }}</td>
                                <td class="c">{{ $line['hsn'] }}</td>
                                <td class="r">{{ $days($line['mandays']) }}</td>
                                <td></td>
                                <td></td>
                                <td class="r">{{ $money($line['amount']) }}</td>
                            </tr>
                        @endforeach
                        <tr><td colspan="7" style="height:10px"></td></tr>
                        @if ($inter)
                            <tr><td></td><td class="r"><b>IGST {{ $d['tax_rate'] + 0 }}%</b></td><td></td><td></td><td></td><td></td><td class="r"><b>{{ $money($d['igst']) }}</b></td></tr>
                        @else
                            <tr><td></td><td class="r"><b>CGST {{ $d['half_rate'] + 0 }}%</b></td><td></td><td></td><td></td><td></td><td class="r"><b>{{ $money($d['cgst']) }}</b></td></tr>
                            <tr><td></td><td class="r"><b>SGST {{ $d['half_rate'] + 0 }}%</b></td><td></td><td></td><td></td><td></td><td class="r"><b>{{ $money($d['sgst']) }}</b></td></tr>
                        @endif
                        <tr><td></td><td><b>Round Off</b></td><td></td><td></td><td></td><td></td><td class="r"><b>{{ $d['round_off'] < 0 ? '(-)' : '' }}{{ number_format(abs($d['round_off']), 2) }}</b></td></tr>
                        <tr class="filler"><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        <tr class="total">
                            <td></td><td class="r">Total</td><td></td><td class="r">{{ $days($d['total_mandays']) }}</td><td></td><td></td><td class="r">Rs {{ $money($d['total']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="words">
                <span class="small">Amount Chargeable (in words)</span> <span class="small" style="float:right">E. &amp; O.E</span><br>
                <b>INR {{ $d['total_words'] }} Only</b>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding:0">
                <table class="tax">
                    <thead>
                        @if ($inter)
                            <tr>
                                <th rowspan="2" style="width:22%">HSN/SAC</th><th rowspan="2" style="width:20%">Taxable<br>Value</th>
                                <th colspan="2">IGST</th><th rowspan="2" style="width:20%">Total<br>Tax Amount</th>
                            </tr>
                            <tr><th style="width:10%">Rate</th><th style="width:20%">Amount</th></tr>
                        @else
                            <tr>
                                <th rowspan="2" style="width:16%">HSN/SAC</th><th rowspan="2" style="width:20%">Taxable<br>Value</th>
                                <th colspan="2">CGST</th><th colspan="2">SGST/UTGST</th><th rowspan="2" style="width:18%">Total<br>Tax Amount</th>
                            </tr>
                            <tr><th style="width:8%">Rate</th><th style="width:15%">Amount</th><th style="width:8%">Rate</th><th style="width:15%">Amount</th></tr>
                        @endif
                    </thead>
                    <tbody>
                        <tr>
                            <td style="text-align:left">{{ $d['hsn'] }}</td>
                            <td class="r">{{ $money($d['taxable_value']) }}</td>
                            @if ($inter)
                                <td>{{ $d['tax_rate'] + 0 }}%</td><td class="r">{{ $money($d['igst']) }}</td>
                            @else
                                <td>{{ $d['half_rate'] + 0 }}%</td><td class="r">{{ $money($d['cgst']) }}</td>
                                <td>{{ $d['half_rate'] + 0 }}%</td><td class="r">{{ $money($d['sgst']) }}</td>
                            @endif
                            <td class="r">{{ $money($d['tax_total']) }}</td>
                        </tr>
                        <tr class="total">
                            <td class="r">Total</td>
                            <td class="r">{{ $money($d['taxable_value']) }}</td>
                            @if ($inter)
                                <td></td><td class="r">{{ $money($d['igst']) }}</td>
                            @else
                                <td></td><td class="r">{{ $money($d['cgst']) }}</td><td></td><td class="r">{{ $money($d['sgst']) }}</td>
                            @endif
                            <td class="r">{{ $money($d['tax_total']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="tax-words">Tax Amount (in words) : <b>INR {{ $d['tax_words'] }} Only</b></td>
        </tr>
        <tr>
            <td colspan="2" style="padding:0">
                <table class="foot">
                    <tbody>
                        <tr>
                            <td style="width:50%; border-right:1px solid #000">
                                <div>Company's PAN &nbsp;&nbsp;&nbsp;&nbsp;: <b>{{ $d['supplier']['pan'] ?: '—' }}</b></div>
                                <div style="margin-top:4px"><u>Declaration</u></div>
                                @foreach ($declaration as $line)
                                    @if (trim($line) !== '')<div>{{ $line }}</div>@endif
                                @endforeach
                            </td>
                            <td style="width:50%">
                                <div><u>Company's Bank Details</u></div>
                                <table>
                                    <tr><td style="width:92px; font-size:8.5px; padding:0">A/c Holder's Name</td><td style="padding:0; font-size:8.5px">: <b>{{ strtoupper((string) $d['bank']['holder']) }}</b></td></tr>
                                    <tr><td style="padding:0; font-size:8.5px">Bank Name</td><td style="padding:0; font-size:8.5px">: <b>{{ $d['bank']['bank'] ?: '—' }}</b></td></tr>
                                    <tr><td style="padding:0; font-size:8.5px">A/c No.</td><td style="padding:0; font-size:8.5px">: <b>{{ $d['bank']['account'] ?: '—' }}</b></td></tr>
                                    <tr><td style="padding:0; font-size:8.5px">Branch &amp; IFS Code</td><td style="padding:0; font-size:8.5px">: <b>{{ $d['bank']['branch'] ?: '—' }} &amp; {{ $d['bank']['ifsc'] ?: '—' }}</b></td></tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="border-right:1px solid #000; border-top:1px solid #000">
                                Customer's Seal and Signature
                                <div class="sign-space"></div>
                            </td>
                            <td style="border-top:1px solid #000; text-align:right">
                                for <b>M/s {{ strtoupper($d['supplier']['name']) }}</b>
                                <div class="sign-space"></div>
                                Authorised Signatory
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="centered"><b>SUBJECT TO {{ strtoupper($d['jurisdiction']) }} JURISDICTION</b></div>
    <div class="centered">This is a Computer Generated Invoice</div>
</div>

<div class="page">
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
    <p style="font-size:9px; margin-top:10px">The register of attendance and register of wages for the month are furnished separately.</p>
</div>
</body>
</html>
