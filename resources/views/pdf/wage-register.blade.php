<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #000; }
        .header { text-align: center; margin-bottom: 6px; }
        .header .title { font-size: 15px; font-weight: bold; }
        .header .line { font-size: 10px; font-weight: bold; margin-top: 2px; }
        table.register { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.register th, table.register td { border: 1px solid #000; padding: 4px; text-align: center; }
        table.register th { font-size: 9px; font-weight: bold; background: #f0f0f0; }
        table.register td { font-size: 9px; }
        table.register .col-name { text-align: left; }
        table.register .col-designation { text-align: left; }
        table.register .amt { text-align: right; }
        table.register .totals td { font-weight: bold; background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REGISTER OF WAGES</div>
        <div class="line">NAME OF THE ESTABLISHMENT: M/s MAGNOVA CONSULTING PVT. LTD.</div>
        <div class="line">SALARY SHEET FOR THE MONTH OF {{ strtoupper(\Carbon\Carbon::create()->month($month)->format('F')) }} {{ $year }}</div>
        <div class="line">UNIT: {{ $client->name }}{{ $client->unit_label ? ' - '.$client->unit_label : '' }}</div>
    </div>

    <table class="register">
        <thead>
            <tr>
                <th>SL NO.</th>
                <th class="col-name">NAME</th>
                <th class="col-designation">Designation</th>
                <th>Working Days</th>
                <th>Wages Per Day</th>
                <th>Wages Rate Including</th>
                <th>EPF 12%</th>
                <th>ESIC 0.75%</th>
                <th>Total Deduction</th>
                <th>Net Salary</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalWorkingDays = 0; $totalGross = 0; $totalEpf = 0; $totalEsic = 0; $totalDeduction = 0; $totalNet = 0;
            @endphp
            @foreach ($wages as $i => $wage)
                @php
                    $deduction = $wage->epf_employee + $wage->esic_employee;
                    $totalWorkingDays += $wage->working_days;
                    $totalGross += $wage->gross_wages;
                    $totalEpf += $wage->epf_employee;
                    $totalEsic += $wage->esic_employee;
                    $totalDeduction += $deduction;
                    $totalNet += $wage->net_salary;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="col-name">{{ $wage->employee->name }}</td>
                    <td class="col-designation">{{ $wage->employee->designation ?? '-' }} ({{ ucfirst($wage->employee->skill_category) }})</td>
                    <td>{{ number_format($wage->working_days, 2) }}</td>
                    <td class="amt">Rs. {{ number_format($wage->rate_per_day, 2) }}</td>
                    <td class="amt">Rs. {{ number_format($wage->gross_wages, 2) }}</td>
                    <td class="amt">Rs. {{ number_format($wage->epf_employee, 2) }}</td>
                    <td class="amt">Rs. {{ number_format($wage->esic_employee, 2) }}</td>
                    <td class="amt">Rs. {{ number_format($deduction, 2) }}</td>
                    <td class="amt">Rs. {{ number_format($wage->net_salary, 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="3">Total</td>
                <td>{{ number_format($totalWorkingDays, 2) }}</td>
                <td></td>
                <td class="amt">Rs. {{ number_format($totalGross, 2) }}</td>
                <td class="amt">Rs. {{ number_format($totalEpf, 2) }}</td>
                <td class="amt">Rs. {{ number_format($totalEsic, 2) }}</td>
                <td class="amt">Rs. {{ number_format($totalDeduction, 2) }}</td>
                <td class="amt">Rs. {{ number_format($totalNet, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
