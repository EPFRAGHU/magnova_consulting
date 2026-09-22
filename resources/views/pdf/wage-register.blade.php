<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8px; color: #000; }
        .header { text-align: center; margin-bottom: 6px; }
        .header .title { font-size: 15px; font-weight: bold; }
        .header .line { font-size: 10px; font-weight: bold; margin-top: 2px; }
        table.register { width: 100%; border-collapse: collapse; margin-top: 8px; table-layout: fixed; }
        table.register th, table.register td { border: 1px solid #000; padding: 3px 2px; text-align: center; font-size: 8px; line-height: 1.15; }
        table.register th { font-weight: bold; background: #f0f0f0; vertical-align: middle; }
        table.register .col-sl { width: 2%; }
        table.register .col-name { width: 10%; text-align: left; }
        table.register .col-deployment { width: 8%; text-align: left; }
        table.register .col-days { width: 4%; }
        table.register .col-minimum { width: 5%; }
        table.register .col-monthly { width: 5%; }
        table.register .col-epf { width: 6%; }
        table.register .col-edli { width: 6%; }
        table.register .col-esic { width: 5%; }
        table.register .col-bonus { width: 5%; }
        table.register .col-gross { width: 6%; }
        table.register .col-gst { width: 4%; }
        table.register .col-agency { width: 5%; }
        table.register .col-bill { width: 6%; }
        table.register .col-epf-individual { width: 6%; }
        table.register .col-esic-individual { width: 6%; }
        table.register .col-other { width: 4%; }
        table.register .col-net { width: 7%; }
        table.register .amt { text-align: right; }
        table.register .totals td { font-weight: bold; background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REGISTER OF WAGES</div>
        <div class="line">NAME OF THE ESTABLISHMENT : M/S MAGNOVA CONSULTING PVT. LTD.</div>
        <div class="line">SALARY SHEET FOR THE MONTH OF {{ strtoupper(\Carbon\Carbon::create()->month($month)->format('F')) }}, {{ $year }}</div>
        <div class="line">UNIT: {{ $client->name }}{{ $client->unit_label ? ' - '.$client->unit_label : '' }}</div>
    </div>

    <table class="register">
        <thead>
            <tr>
                @foreach (range('A', 'R') as $letter)
                    <th>{{ $letter }}</th>
                @endforeach
            </tr>
            <tr>
                <th></th>
                <th colspan="17">Particular</th>
            </tr>
            <tr>
                <th class="col-sl">Sl no.</th>
                <th class="col-name">Name of the Guard</th>
                <th class="col-deployment">Nature of Deployment (Armed/ Unarmed)</th>
                <th class="col-days">Days Deployed in the Month</th>
                <th class="col-minimum">Minimum Wage as per Central Minimum Wage Rate</th>
                <th class="col-monthly">Monthly Wage (D*E)</th>
                <th class="col-epf">EPF @12% of MIN(F, Rs. 15,000)</th>
                <th class="col-edli">EDLI and EPF Admin @1% of MIN(F, Rs. 15,000)</th>
                <th class="col-esic">ESIC @3.25% of MIN(F, Rs. 21,000)</th>
                <th class="col-bonus">Bonus @8.33% of MIN(F, Rs. 21,000)</th>
                <th class="col-gross">Total Gross salary of the Security Guards (F+G+H+I+J)</th>
                <th class="col-gst">GST @ 18 % (K*0.18)</th>
                <th class="col-agency">Agency Fee @3.85% of Total Gross Salary (K*0.0385)</th>
                <th class="col-bill">Total Bill amount in Rs. Including GST (K+L+M)</th>
                <th class="col-epf-individual">EPF individual @12% of MIN(F, Rs. 15,000)</th>
                <th class="col-esic-individual">ESIC individual @0.75% of MIN(F, Rs. 21,000)</th>
                <th class="col-other">Any Other Deduction (Professional Tax etc.)</th>
                <th class="col-net">Net Payable Salary of the Guard (F+J) - (O+P+Q)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total = array_fill_keys([
                    'days_deployed', 'monthly_wage', 'epf_employer', 'edli_and_epf_admin',
                    'esic_employer', 'bonus', 'total_gross_salary', 'gst', 'agency_fee',
                    'total_bill_amount', 'epf_employee', 'esic_employee', 'other_deduction',
                    'net_payable_salary',
                ], 0);
            @endphp
            @foreach ($rows as $row)
                @php
                    foreach ($total as $key => $value) {
                        $total[$key] += $row[$key];
                    }
                @endphp
                <tr>
                    <td>{{ $row['serial_number'] }}</td>
                    <td class="col-name">{{ $row['name'] }}</td>
                    <td class="col-deployment">{{ $row['nature_of_deployment'] }}</td>
                    <td>{{ number_format($row['days_deployed'], 2) }}</td>
                    <td class="amt">{{ number_format($row['minimum_wage'], 2) }}</td>
                    <td class="amt">{{ number_format($row['monthly_wage'], 2) }}</td>
                    <td class="amt">{{ number_format($row['epf_employer'], 2) }}</td>
                    <td class="amt">{{ number_format($row['edli_and_epf_admin'], 2) }}</td>
                    <td class="amt">{{ number_format($row['esic_employer'], 2) }}</td>
                    <td class="amt">{{ number_format($row['bonus'], 2) }}</td>
                    <td class="amt">{{ number_format($row['total_gross_salary'], 2) }}</td>
                    <td class="amt">{{ number_format($row['gst'], 2) }}</td>
                    <td class="amt">{{ number_format($row['agency_fee'], 2) }}</td>
                    <td class="amt">{{ number_format($row['total_bill_amount'], 2) }}</td>
                    <td class="amt">{{ number_format($row['epf_employee'], 2) }}</td>
                    <td class="amt">{{ number_format($row['esic_employee'], 2) }}</td>
                    <td class="amt">{{ number_format($row['other_deduction'], 2) }}</td>
                    <td class="amt">{{ number_format($row['net_payable_salary'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="3">Total</td>
                <td>{{ number_format($total['days_deployed'], 2) }}</td>
                <td></td>
                <td class="amt">{{ number_format($total['monthly_wage'], 2) }}</td>
                <td class="amt">{{ number_format($total['epf_employer'], 2) }}</td>
                <td class="amt">{{ number_format($total['edli_and_epf_admin'], 2) }}</td>
                <td class="amt">{{ number_format($total['esic_employer'], 2) }}</td>
                <td class="amt">{{ number_format($total['bonus'], 2) }}</td>
                <td class="amt">{{ number_format($total['total_gross_salary'], 2) }}</td>
                <td class="amt">{{ number_format($total['gst'], 2) }}</td>
                <td class="amt">{{ number_format($total['agency_fee'], 2) }}</td>
                <td class="amt">{{ number_format($total['total_bill_amount'], 2) }}</td>
                <td class="amt">{{ number_format($total['epf_employee'], 2) }}</td>
                <td class="amt">{{ number_format($total['esic_employee'], 2) }}</td>
                <td class="amt">{{ number_format($total['other_deduction'], 2) }}</td>
                <td class="amt">{{ number_format($total['net_payable_salary'], 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
