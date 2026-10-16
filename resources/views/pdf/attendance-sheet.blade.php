<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        @page { margin: 0.28in 0.28in 0.28in 0.28in; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #000; }
        .header { text-align: center; margin-bottom: 8px; }
        .header div { font-weight: bold; line-height: 1.25; }
        .header .title { font-size: 16px; }
        .header .company { font-size: 12px; margin-top: 2px; }
        .header .salary-period,
        .header .attendance-period { font-size: 12px; margin-top: 2px; }
        table.sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.sheet th,
        table.sheet td { border: 1px solid #000; padding: 3px 2px; text-align: center; vertical-align: middle; line-height: 1.1; }
        table.sheet thead { display: table-header-group; }
        table.sheet tr { page-break-inside: avoid; }
        table.sheet th { height: 28px; font-size: 12px; font-weight: bold; }
        table.sheet td { min-height: 34px; height: 34px; font-size: 12px; }
        table.sheet .col-sl { width: 2.8%; }
        table.sheet .col-name { width: 13%; text-align: left !important; padding-left: 4px !important; }
        table.sheet .col-gender { width: 3.5%; }
        table.sheet .col-designation { width: 11%; text-align: left !important; padding-left: 4px !important; }
        table.sheet .col-day { width: 2%; }
        table.sheet .attendance-mark { font-size: 8px; }
        table.sheet .col-payable { width: 4%; font-size: 12px; }
        table.sheet .total-row td { height: 28px; font-weight: bold; }
        table.sheet .total-label { text-align: right !important; padding-right: 6px !important; }
        .employee-name,
        .employee-code { display: block; overflow: hidden; word-wrap: break-word; }
        .employee-name { font-size: 12px; font-weight: bold; }
        .employee-code { color: #444; font-size: 10px; margin-top: 2px; }
        .signatures { width: 100%; margin-top: 28px; }
        .signatures td { width: 50%; text-align: center; font-size: 12px; vertical-align: top; }
        .signatures .sign-line { border-top: 1px solid #000; margin: 30px 45px 4px; }
        .signatures .caption { font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">DUTY ATTENDANCE SHEET</div>
        <div class="company">NAME OF THE ESTABLISHMENT : {{ $client->tenant->name ?? 'M/s MAGNOVA CONSULTING PVT. LTD.' }}</div>
        <div class="salary-period">SALARY SHEET FOR THE MONTH OF {{ strtoupper(\Carbon\Carbon::create()->month($month)->format('F')) }} {{ $year }}</div>
        <div class="attendance-period">ATTENDANCE SHEET FOR THE MONTH OF : 01.{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}.{{ $year }} TO {{ str_pad($daysInMonth, 2, '0', STR_PAD_LEFT) }}.{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}.{{ $year }}</div>
    </div>

    <table class="sheet">
        <colgroup>
            <col class="col-sl">
            <col class="col-name">
            <col class="col-gender">
            <col class="col-designation">
            @for ($d = 1; $d <= $daysInMonth; $d++)
                <col class="col-day">
            @endfor
            <col class="col-payable">
        </colgroup>
        <thead>
            <tr>
                <th class="col-sl">Sl.<br>No</th>
                <th class="col-name">NAME</th>
                <th class="col-gender">(M/F)</th>
                <th class="col-designation">Designation</th>
                @for ($d = 1; $d <= $daysInMonth; $d++)
                    <th class="col-day">{{ $d }}</th>
                @endfor
                <th class="col-payable">NO OF DAYS<br>PAYBLE</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalDaysPayable = 0;
            @endphp
            @foreach ($employees as $i => $employee)
                @php
                    $marks = $attendance->get($employee->id, collect());
                    $daysPayable = 0;
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $status = $marks->get($d);
                        if (in_array($status, ['present', 'sunday'], true)) { $daysPayable++; }
                    }
                    $totalDaysPayable += $daysPayable;
                @endphp
                <tr>
                    <td class="col-sl">{{ $i + 1 }}</td>
                    <td class="col-name">
                        <span class="employee-name">{{ $employee->name }}</span>
                        @if ($employee->employee_code)
                            <span class="employee-code">{{ $employee->employee_code }}</span>
                        @endif
                    </td>
                    <td class="col-gender">{{ $employee->gender === 'male' ? 'M' : ($employee->gender === 'female' ? 'F' : '-') }}</td>
                    <td class="col-designation">{{ $employee->designation ?: '-' }}</td>
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $status = $marks->get($d);
                            $letter = match ($status) {
                                'present' => 'P',
                                'absent' => 'A',
                                'holiday' => 'H',
                                'leave' => 'L',
                                'sunday' => 'S',
                                'off' => 'OFF',
                                default => '',
                            };
                        @endphp
                        <td class="col-day attendance-mark">{{ $letter }}</td>
                    @endfor
                    <td class="col-payable">{{ $daysPayable }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="total-label" colspan="{{ 4 + $daysInMonth }}">TOTAL NO. OF DAYS PAYABLE</td>
                <td class="col-payable">{{ $totalDaysPayable }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div>Checked and verified by</div>
                <div>The I/C of {{ $client->name }}</div>
                <div class="sign-line"></div>
                <div class="caption">(Authorized signature)</div>
            </td>
            <td>
                <div>The site incharge</div>
                <div>For M/s Magnova Consulting Pvt. Ltd.</div>
                <div class="sign-line"></div>
                <div class="caption">(Authorized signature)</div>
            </td>
        </tr>
    </table>
</body>
</html>
