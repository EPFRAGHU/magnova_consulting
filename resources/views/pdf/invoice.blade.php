<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9px; color: #000; }
        table { border-collapse: collapse; width: 100%; }
        td, th { vertical-align: top; }

        .frame { border: 1.2px solid #000; }
        .frame > tbody > tr > td { border-top: 1.2px solid #000; padding: 6px 8px; }
        .frame > tbody > tr:first-child > td { border-top: none; }

        .strip td { font-size: 9px; font-weight: bold; }
        .strip .center { text-align: center; font-size: 12px; }
        .strip .right { text-align: right; }

        .company-block td { text-align: center; }
        .company-block .name { font-size: 15px; font-weight: bold; }
        .company-block .meta { font-size: 9px; margin-top: 2px; }
        .logo-spacer { width: 50px; }

        .two-col > tbody > tr > td { width: 50%; padding: 0; border-top: none; }
        .two-col > tbody > tr > td:first-child { border-right: 1.2px solid #000; padding-right: 8px; }
        .two-col > tbody > tr > td:last-child { padding-left: 8px; }

        .kv td { padding: 1px 0; font-size: 9px; }
        .kv .k { width: 95px; font-weight: bold; }
        .kv .c { width: 8px; }

        .section-title { font-weight: bold; font-size: 9.5px; margin-bottom: 3px; }

        table.items { table-layout: fixed; }
        table.items th, table.items td { border: 1px solid #000; padding: 3px 4px; font-size: 8.5px; }
        table.items th { background: #eee; text-align: center; font-weight: bold; }
        table.items td.amt, table.items th.amt { text-align: right; }
        table.items td.center { text-align: center; }
        table.items .col-sr { width: 22px; }
        table.items .col-desc { width: auto; }
        table.items .col-hsn { width: 48px; }
        table.items .col-qty { width: 34px; }
        table.items .col-unit { width: 34px; }
        table.items .col-price { width: 56px; }
        table.items .col-tax { width: 34px; }
        table.items .col-amt { width: 62px; }
        table.items tr.total-row td { font-weight: bold; border-top: 1.5px solid #000; }

        .words-line { font-size: 9.5px; margin: 2px 0; }
        .settled-line { font-size: 8.5px; color: #333; }
        .tax-breakdown { font-size: 8.5px; margin-top: 2px; }

        .footer-col { width: 33.33%; padding: 0 6px; font-size: 8.5px; }
        .footer-col:not(:first-child) { border-left: 1.2px solid #000; }
        .footer-title { font-weight: bold; margin-bottom: 3px; }
        .sign-line { margin-top: 28px; border-top: 1px solid #000; padding-top: 3px; }
    </style>
</head>
<body>
@php
    $tenant = $invoice->tenant;
    $client = $invoice->client;
    $monthName = \Carbon\Carbon::create()->month($invoice->month)->format('F');
    $billingPeriodStart = \Carbon\Carbon::create($invoice->year, $invoice->month, 1);
    $billingPeriodEnd = $billingPeriodStart->copy()->endOfMonth();
    $terms = $tenant->terms_and_conditions ?: config('magnova.default_terms_and_conditions');
    $hsnSac = config('magnova.hsn_sac_code');
    $taxPct = (float) config('magnova.cgst_pct') + (float) config('magnova.sgst_pct');
    $totalAmount = 0;
@endphp

<table class="frame">
    <tbody>
        <tr>
            <td>
                <table class="strip">
                    <tr>
                        <td style="width:33%">Page No. 1 of 1</td>
                        <td class="center" style="width:34%">TAX INVOICE</td>
                        <td class="right" style="width:33%">Original Copy</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="company-block">
                    <tr>
                        <td class="logo-spacer">@include('pdf.partials.logo-mark')</td>
                        <td>
                            <div class="name">{{ $tenant->name }}</div>
                            <div class="meta">
                                {{ $tenant->address }}<br>
                                @if($tenant->contact_phone) Mobile: {{ $tenant->contact_phone }} @endif
                                @if($tenant->contact_email) &nbsp;|&nbsp; Email: {{ $tenant->contact_email }} @endif
                                <br>
                                @if($tenant->gstin) GSTIN - {{ $tenant->gstin }} @endif
                                @if($tenant->pan) &nbsp;|&nbsp; PAN - {{ $tenant->pan }} @endif
                            </div>
                        </td>
                        <td class="logo-spacer"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="two-col">
                    <tr>
                        <td>
                            <div class="section-title">Invoice Details</div>
                            <table class="kv">
                                <tr><td class="k">Invoice Number</td><td class="c">:</td><td>{{ $invoice->invoice_no }}</td></tr>
                                <tr><td class="k">Invoice Date</td><td class="c">:</td><td>{{ $invoice->invoice_date->format('d-m-Y') }}</td></tr>
                                <tr><td class="k">Due Date</td><td class="c">:</td><td>{{ $invoice->due_date?->format('d-m-Y') ?? '-' }}</td></tr>
                                <tr><td class="k">Place of Supply</td><td class="c">:</td><td>{{ $invoice->place_of_supply ?? '-' }}</td></tr>
                                <tr><td class="k">Reverse Charge</td><td class="c">:</td><td>{{ $invoice->reverse_charge ? 'Yes' : 'No' }}</td></tr>
                            </table>
                        </td>
                        <td>
                            <div class="section-title">Contract Details</div>
                            <table class="kv">
                                <tr><td class="k">Site / Unit</td><td class="c">:</td><td>{{ $client->unit_label ?? $client->name }}</td></tr>
                                <tr><td class="k">GEMC No.</td><td class="c">:</td><td>{{ $client->gemc_no ?? '-' }}</td></tr>
                                <tr><td class="k">Billing Period</td><td class="c">:</td><td>{{ $billingPeriodStart->format('d-m-Y') }} to {{ $billingPeriodEnd->format('d-m-Y') }}</td></tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="two-col">
                    <tr>
                        <td>
                            <div class="section-title">Billing Details</div>
                            <div><strong>Name:</strong> {{ $client->billingName() }}</div>
                            <div><strong>Address:</strong> {{ $client->billingAddress() }}</div>
                            @if($client->gstin)<div>GSTIN: {{ $client->gstin }}</div>@endif
                        </td>
                        <td>
                            <div class="section-title">Duty Site Details</div>
                            <div><strong>Site:</strong> {{ $client->name }}{{ $client->unit_label ? ' - '.$client->unit_label : '' }}</div>
                            <div><strong>Address:</strong> {{ $client->address }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <table class="items">
                    <thead>
                        <tr>
                            <th class="col-sr">Sr.</th>
                            <th class="col-desc">Item Description</th>
                            <th class="col-hsn">HSN/SAC</th>
                            <th class="col-qty">Qty</th>
                            <th class="col-unit">Unit</th>
                            <th class="col-price">List Price</th>
                            <th class="col-tax">Tax %</th>
                            <th class="col-amt amt">Amount (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $sr = 1; @endphp
                        @foreach ($itemGroups as $group)
                            @php $rowAmount = round($group['amount'] * (1 + $taxPct / 100), 2); $totalAmount += $rowAmount; @endphp
                            <tr>
                                <td class="center">{{ $sr++ }}</td>
                                <td>{{ $group['designation'] }} &ndash; Manpower Supply Service</td>
                                <td class="center">{{ $hsnSac }}</td>
                                <td class="center">{{ number_format($group['qty'], 2) }}</td>
                                <td class="center">Day</td>
                                <td class="amt">{{ number_format($group['rate'], 2) }}</td>
                                <td class="center">{{ number_format($taxPct, 2) }}</td>
                                <td class="amt">{{ number_format($rowAmount, 2) }}</td>
                            </tr>
                        @endforeach
                        @php
                            $charges = [
                                ['EPF Cont. @ '.config('magnova.epf_employer_pct').'% on Wages', $invoice->epf_employer],
                                ['EPF Admin Charges @ '.config('magnova.epf_admin_pct').'%', $invoice->epf_admin],
                                ['EDLI Cont. @ '.config('magnova.edli_cont_pct').'%', $invoice->edli_cont],
                                ['ESI @ '.config('magnova.esi_employer_pct').'%', $invoice->esi_employer],
                                ['Service Charges @ '.config('magnova.service_charge_pct').'%', $invoice->service_charge],
                            ];
                        @endphp
                        @foreach ($charges as [$label, $amount])
                            @php $rowAmount = round($amount * (1 + $taxPct / 100), 2); $totalAmount += $rowAmount; @endphp
                            <tr>
                                <td class="center">{{ $sr++ }}</td>
                                <td>{{ $label }}</td>
                                <td class="center">{{ $hsnSac }}</td>
                                <td class="center">1.00</td>
                                <td class="center">Lump</td>
                                <td class="amt">{{ number_format($amount, 2) }}</td>
                                <td class="center">{{ number_format($taxPct, 2) }}</td>
                                <td class="amt">{{ number_format($rowAmount, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="center">{{ $sr++ }}</td>
                            <td>Discount</td>
                            <td class="center">-</td>
                            <td class="center">-</td>
                            <td class="center">-</td>
                            <td class="amt">-</td>
                            <td class="center">-</td>
                            <td class="amt">0.00</td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="7" class="center">Total</td>
                            <td class="amt">{{ number_format($totalAmount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <div class="words-line">Rs. {{ $amountInWords }} Only</div>
                <div class="settled-line">Settled by - Bank: 0.00 | Invoice Balance: {{ number_format($invoice->grand_total, 2) }}</div>
                <div class="tax-breakdown">
                    Sale @18% = {{ number_format($invoice->taxable_value, 2) }}
                    &nbsp;|&nbsp; CGST = {{ number_format($invoice->cgst, 2) }}, SGST = {{ number_format($invoice->sgst, 2) }}
                    &nbsp;|&nbsp; Total Sale = {{ number_format($invoice->taxable_value, 2) }}, Tax = {{ number_format($invoice->cgst + $invoice->sgst, 2) }}, Cess = 0.00
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:0">
                <table>
                    <tr>
                        <td class="footer-col">
                            <div class="footer-title">Terms and Conditions</div>
                            <div>{!! nl2br(e($terms)) !!}</div>
                        </td>
                        <td class="footer-col">
                            <div class="footer-title">Bank Details</div>
                            <div>Account Number: {{ $tenant->bank_account_no ?? '-' }}</div>
                            <div>Bank: {{ $tenant->bank_name ?? '-' }}</div>
                            <div>IFSC: {{ $tenant->bank_ifsc ?? '-' }}</div>
                            <div>Branch: {{ $tenant->bank_branch ?? '-' }}</div>
                            <div>Name: {{ $tenant->name }}</div>
                        </td>
                        <td class="footer-col">
                            <div class="footer-title">For {{ $tenant->name }}</div>
                            <div class="sign-line">Signature</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody>
</table>
</body>
</html>
