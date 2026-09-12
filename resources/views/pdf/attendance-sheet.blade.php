<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8px; color: #000; }
        .header { text-align: center; margin-bottom: 4px; }
        .header .title { font-size: 13px; font-weight: bold; }
        .header .company { font-size: 11px; font-weight: bold; margin-top: 2px; }
        .header .address { font-size: 8px; margin-top: 1px; }
        .meta { text-align: center; font-size: 9px; font-weight: bold; margin: 6px 0; }
        table.sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.sheet th, table.sheet td { border: 1px solid #000; padding: 1px; text-align: center; vertical-align: middle; }
        table.sheet th { font-size: 7px; font-weight: bold; }
        table.sheet td { font-size: 7px; }
        .col-sl { width: 20px; }
        .col-name { width: 110px; text-align: left !important; padding-left: 3px !important; }
        .col-day { width: 16px; }
        .col-total { width: 30px; }
        .signatures { width: 100%; margin-top: 24px; }
        .signatures td { width: 50%; text-align: center; font-size: 9px; vertical-align: top; }
        .signatures .sign-line { border-top: 1px solid #000; margin: 28px 30px 3px; }
        .signatures .caption { font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">DUTY ATTENDANCE SHEET</div>
        <div class="company">M/s MAGNOVA CONSULTING PVT. LTD.</div>
        <div class="address">AT: {{ $client->tenant->address }}@if($client->tenant->contact_phone), M. No.: {{ $client->tenant->contact_phone }}@endif</div>
    </div>

    <div class="meta">
        UNIT: {{ $client->name }}{{ $client->unit_label ? ' - '.$client->unit_label : '' }}
        &nbsp;&nbsp;&nbsp; MONTH: {{ \Carbon\Carbon::create()->month($month)->format('F') }}
        &nbsp;&nbsp;&nbsp; YEAR: {{ $year }}
    </div>

    <table class="sheet">
        <thead>
            <tr>
                <th class="col-sl">SL<br>NO.</th>
                <th class="col-name">NAME</th>
                @for ($d = 1; $d <= $daysInMonth; $d++)
                    <th class="col-day">{{ $d }}</th>
                @endfor
                <th class="col-total">ACTUAL<br>NO. OF<br>DAYS</th>
                <th class="col-total">ABSENT</th>
                <th class="col-total">DAYS<br>WORKED</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($employees as $i => $employee)
                @php
                    $marks = $attendance->get($employee->id, collect());
                    $actual = 0; $absentCount = 0; $daysWorked = 0;
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $status = $marks->get($d);
                        if ($status === 'present') { $actual++; $daysWorked++; }
                        elseif ($status === 'sunday') { $actual++; }
                        elseif ($status === 'absent') { $absentCount++; }
                    }
                @endphp
                <tr>
                    <td class="col-sl">{{ $i + 1 }}</td>
                    <td class="col-name">{{ $employee->name }}</td>
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $status = $marks->get($d);
                            $letter = match ($status) {
                                'present' => 'P',
                                'absent' => 'A',
                                'holiday' => 'H',
                                'leave' => 'L',
                                'sunday' => 'S',
                                default => '',
                            };
                        @endphp
                        <td class="col-day">{{ $letter }}</td>
                    @endfor
                    <td class="col-total">{{ $actual }}</td>
                    <td class="col-total">{{ $absentCount }}</td>
                    <td class="col-total">{{ $daysWorked }}</td>
                </tr>
            @endforeach
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
