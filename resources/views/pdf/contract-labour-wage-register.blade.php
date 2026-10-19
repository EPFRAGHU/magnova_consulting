<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A3 landscape; margin: 0.3in; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8px; color: #000; }
        h1, h2, h3 { margin: 0; text-align: center; }
        .line { text-align: center; font-weight: bold; font-size: 9.5px; margin-bottom: 2px; }
        .title { text-align: center; font-weight: bold; font-size: 13px; margin: 4px 0 2px; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; margin-top: 6px; }
        th, td { border: 1px solid #000; padding: 2px 2px; font-size: 7.4px; word-wrap: break-word; }
        th { background: #e8e8e8; text-align: center; vertical-align: middle; font-weight: bold; }
        td.r { text-align: right; }
        td.c { text-align: center; }
        tr.total td { font-weight: bold; background: #f3f3f3; }
        .foot { margin-top: 14px; font-size: 9px; }
    </style>
</head>
<body>
@php
    $tenant = \App\Models\Tenant::find($client->tenant_id);
    $contractor = strtoupper(trim(preg_replace('~^\s*m/s\.?\s*~i', '', (string) ($tenant?->name ?? 'Magnova Consulting Pvt. Ltd.'))));
    $contractorAddress = $client->contractorAddress();
    $money = fn ($v) => number_format((float) $v, 2);
    $days = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $f = fn ($row, $key) => $money($row['figures'][$key] ?? 0);
    $t = $sheet['totals'];
@endphp

<div class="line">CONTRACTOR NAME - {{ $contractor }}{{ $contractorAddress ? ', '.strtoupper($contractorAddress) : '' }}</div>
<div class="line">PRINCIPAL EMPLOYER- {{ strtoupper((string) $client->principal_employer_name) }}{{ $client->principal_employer_address ? ', '.strtoupper($client->principal_employer_address) : '' }}</div>
<div class="title">REGISTER OF WAGES</div>
<div class="line">MONTH- {{ $monthLabel }}</div>

<table>
    <colgroup>
        <col style="width:2.2%"><col style="width:8%"><col style="width:5.2%"><col style="width:5.4%"><col style="width:4.6%">
        <col style="width:3%"><col style="width:2.8%"><col style="width:3.6%"><col style="width:2.4%"><col style="width:3.4%"><col style="width:3.2%">
        <col style="width:3.2%"><col style="width:2.4%"><col style="width:2.2%"><col style="width:3.8%">
        <col style="width:3%"><col style="width:3%"><col style="width:3.2%"><col style="width:3.8%">
        <col style="width:3%"><col style="width:3%"><col style="width:2.2%"><col style="width:2.2%"><col style="width:3.4%"><col style="width:3.8%">
        <col style="width:3.2%"><col style="width:3.8%"><col style="width:3.2%"><col style="width:4%">
    </colgroup>
    <thead>
        <tr>
            <th rowspan="2">S.No.</th><th rowspan="2">Name</th><th rowspan="2">Category</th><th rowspan="2">UAN No</th><th rowspan="2">ESIC No</th>
            <th rowspan="2">Wages (Basic+DA) per day</th><th rowspan="2">Present Days</th><th rowspan="2">Earn Wages</th><th rowspan="2">OT</th>
            <th rowspan="2">OT Amount</th><th rowspan="2">Bonus</th><th rowspan="2">Leave Wages</th><th rowspan="2">PMJJBY</th><th rowspan="2">PMSBY</th><th rowspan="2">Gross</th>
            <th colspan="3">Employee Deduction</th><th rowspan="2">Net Salary</th>
            <th colspan="5">Employer Contribution</th><th rowspan="2">Total CTC</th>
            <th rowspan="2">Service Charges</th><th rowspan="2">Gross Amount</th><th rowspan="2">GST</th><th rowspan="2">Amount Including GST</th>
        </tr>
        <tr>
            <th>{{ $headings['pf_employee'] }}</th><th>{{ $headings['esic_employee'] }}</th><th>Total Deduction</th>
            <th>{{ $headings['pf_employer'] }}</th><th>{{ $headings['esic_employer'] }}</th><th>PPE</th><th>LWF</th><th>Total Contribution</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sheet['rows'] as $row)
            <tr>
                <td class="c">{{ $row['serial'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['category'] }}</td>
                <td class="c">{{ $row['uan'] }}</td>
                <td class="c">{{ $row['esic_no'] }}</td>
                <td class="r">{{ $days($row['rate']) }}</td>
                <td class="r">{{ $days($row['paid_days']) }}</td>
                <td class="r">{{ $f($row, 'earn_wages') }}</td>
                <td class="r">{{ $days($row['ot_hours']) }}</td>
                <td class="r">{{ $f($row, 'ot_amount') }}</td>
                <td class="r">{{ $f($row, 'bonus') }}</td>
                <td class="r">{{ $f($row, 'leave_wages') }}</td>
                <td class="r">{{ $f($row, 'pmjjby') }}</td>
                <td class="r">{{ $f($row, 'pmsby') }}</td>
                <td class="r">{{ $f($row, 'gross') }}</td>
                <td class="r">{{ $f($row, 'pf_employee') }}</td>
                <td class="r">{{ $f($row, 'esic_employee') }}</td>
                <td class="r">{{ $f($row, 'total_deduction') }}</td>
                <td class="r">{{ $f($row, 'net_salary') }}</td>
                <td class="r">{{ $f($row, 'pf_employer') }}</td>
                <td class="r">{{ $f($row, 'esic_employer') }}</td>
                <td class="r">{{ $f($row, 'ppe') }}</td>
                <td class="r">{{ $f($row, 'lwf') }}</td>
                <td class="r">{{ $f($row, 'total_contribution') }}</td>
                <td class="r">{{ $f($row, 'total_ctc') }}</td>
                <td class="r">{{ $f($row, 'service_charge') }}</td>
                <td class="r">{{ $f($row, 'gross_amount') }}</td>
                <td class="r">{{ $f($row, 'gst') }}</td>
                <td class="r">{{ $f($row, 'amount_incl_gst') }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td colspan="6">Total</td>
            <td class="r">{{ $days($t['paid_days']) }}</td>
            <td class="r">{{ $money($t['figures']['earn_wages'] ?? 0) }}</td>
            <td class="r">{{ $days($t['ot_hours']) }}</td>
            @foreach (['ot_amount', 'bonus', 'leave_wages', 'pmjjby', 'pmsby', 'gross', 'pf_employee', 'esic_employee', 'total_deduction', 'net_salary', 'pf_employer', 'esic_employer', 'ppe', 'lwf', 'total_contribution', 'total_ctc', 'service_charge', 'gross_amount', 'gst', 'amount_incl_gst'] as $key)
                <td class="r">{{ $money($t['figures'][$key] ?? 0) }}</td>
            @endforeach
        </tr>
    </tbody>
</table>

<div class="foot">Prepared by {{ $contractor }}. Figures are as saved in the wage sheet for the month.</div>
</body>
</html>
