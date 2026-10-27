@php
    $employee = $wage->employee;
    $totalEarnings = $wage->gross_wages + $wage->bonus;
    $totalDeductions = $wage->epf_employee + $wage->esic_employee;
    $ytdTotalEarnings = $ytd['gross_wages'] + $ytd['bonus'];
    $ytdTotalDeductions = $ytd['epf_employee'] + $ytd['esic_employee'];
    $daysInMonth = \Carbon\Carbon::create($wage->year, $wage->month, 1)->daysInMonth;
    $lopDays = max($daysInMonth - (float) $wage->working_days, 0);
@endphp

<table class="pay-summary">
    <tr>
        <td class="label">Employee Name</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->name }}</td>
        <td class="label">Employee ID</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->employee_code ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Designation</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->designation ?? '-' }} ({{ $employee->skillLabel() }})</td>
        <td class="label">Bank Account No.</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->bank_account_no ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Date of Joining</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->date_of_joining?->format('d-m-Y') ?? '-' }}</td>
        <td class="label">LOP Days</td>
        <td class="c">:</td>
        <td class="value">{{ rtrim(rtrim(number_format($lopDays, 2), '0'), '.') }}</td>
    </tr>
    <tr>
        <td class="label">Paid Days</td>
        <td class="c">:</td>
        <td class="value">{{ rtrim(rtrim(number_format((float) $wage->working_days, 2), '0'), '.') }}</td>
        <td class="label">UAN</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->uan ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">PF A/C Number</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->pfAccountNumber() ?? '-' }}</td>
        <td class="label">ESI Number</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->esic_no ?? '-' }}</td>
    </tr>
</table>

<table class="earn-deduct">
    <tr>
        <th>Earnings</th>
        <th>Amount</th>
        <th>YTD</th>
        <th>Deductions</th>
        <th>Amount</th>
        <th>YTD</th>
    </tr>
    <tr>
        <td>Basic Wages</td>
        <td class="amt">{{ number_format($wage->gross_wages, 2) }}</td>
        <td class="amt">{{ number_format($ytd['gross_wages'], 2) }}</td>
        <td>EPF ({{ config('magnova.epf_employee_pct') }}%)</td>
        <td class="amt">{{ number_format($wage->epf_employee, 2) }}</td>
        <td class="amt">{{ number_format($ytd['epf_employee'], 2) }}</td>
    </tr>
    <tr>
        <td>Bonus</td>
        <td class="amt">{{ number_format($wage->bonus, 2) }}</td>
        <td class="amt">{{ number_format($ytd['bonus'], 2) }}</td>
        <td>ESIC ({{ $wage->esic_snapshot['employee_rate'] ?? config('magnova.esic_employee_pct') }}%)</td>
        <td class="amt">{{ number_format($wage->esic_employee, 2) }}</td>
        <td class="amt">{{ number_format($ytd['esic_employee'], 2) }}</td>
    </tr>
    <tr class="total-row">
        <td>Gross Earnings</td>
        <td class="amt">{{ number_format($totalEarnings, 2) }}</td>
        <td class="amt">{{ number_format($ytdTotalEarnings, 2) }}</td>
        <td>Total Deductions</td>
        <td class="amt">{{ number_format($totalDeductions, 2) }}</td>
        <td class="amt">{{ number_format($ytdTotalDeductions, 2) }}</td>
    </tr>
</table>

<div class="net-payable">
    Total net payable Rs. {{ number_format($wage->net_salary, 2) }} (Indian Rupee {{ $netSalaryWords }} Only)
</div>

<div class="footer-note">
    ** Total net payable = Gross earnings - Total deductions<br>
    -- This is a system-generated document. --
</div>
