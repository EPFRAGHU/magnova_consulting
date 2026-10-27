@php
    $employee = $line->employee;
    $f = $line->figures;
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $money = fn ($v) => number_format((float) $v, 2);
    $earnings = [
        ['Earned wages', 'earn_wages'],
        ['Overtime', 'ot_amount'],
        ['Bonus', 'bonus'],
        ['Leave wages', 'leave_wages'],
        ['PMJJBY', 'pmjjby'],
        ['PMSBY', 'pmsby'],
    ];
    $deductions = [
        [$headings['pf_employee'], 'pf_employee'],
        [$headings['esic_employee'], 'esic_employee'],
    ];
    $rows = max(count($earnings), count($deductions));
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
        <td class="value">{{ $line->category }}</td>
        <td class="label">Bank Account No.</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->bank_account_no ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Date of Joining</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->date_of_joining?->format('d-m-Y') ?? '-' }}</td>
        <td class="label">Wages per day</td>
        <td class="c">:</td>
        <td class="value">{{ $money($line->rate_per_day) }}</td>
    </tr>
    <tr>
        <td class="label">Paid Days</td>
        <td class="c">:</td>
        <td class="value">{{ $num($line->paid_days) }}</td>
        <td class="label">UAN</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->uan ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Overtime Hours</td>
        <td class="c">:</td>
        <td class="value">{{ $num($line->ot_hours) }}</td>
        <td class="label">ESI Number</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->esic_no ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">PF A/C Number</td>
        <td class="c">:</td>
        <td class="value">{{ $employee->epf_member_id ?? '-' }}</td>
        <td class="label"></td>
        <td class="c"></td>
        <td class="value"></td>
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
    @for ($i = 0; $i < $rows; $i++)
        <tr>
            @if (isset($earnings[$i]))
                <td>{{ $earnings[$i][0] }}</td>
                <td class="amt">{{ $money($f[$earnings[$i][1]] ?? 0) }}</td>
                <td class="amt">{{ $money($ytd[$earnings[$i][1]]) }}</td>
            @else
                <td></td><td></td><td></td>
            @endif
            @if (isset($deductions[$i]))
                <td>{{ $deductions[$i][0] }}</td>
                <td class="amt">{{ $money($f[$deductions[$i][1]] ?? 0) }}</td>
                <td class="amt">{{ $money($ytd[$deductions[$i][1]]) }}</td>
            @else
                <td></td><td></td><td></td>
            @endif
        </tr>
    @endfor
    <tr class="total-row">
        <td>Gross Earnings</td>
        <td class="amt">{{ $money($f['gross'] ?? 0) }}</td>
        <td class="amt">{{ $money($ytd['gross']) }}</td>
        <td>Total Deductions</td>
        <td class="amt">{{ $money($f['total_deduction'] ?? 0) }}</td>
        <td class="amt">{{ $money($ytd['total_deduction']) }}</td>
    </tr>
</table>

<div class="net-payable">
    Total net payable Rs. {{ $money($f['net_salary'] ?? 0) }} (Indian Rupee {{ $netSalaryWords }} Only)
</div>

<div class="footer-note">
    ** Total net payable = Gross earnings - Total deductions<br>
    -- This is a system-generated document. --
</div>
