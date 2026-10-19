<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 0.3in; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8px; color: #000; }
        .line { text-align: center; font-weight: bold; font-size: 8.5px; margin-bottom: 1px; }
        .title { text-align: center; font-weight: bold; font-size: 12px; margin: 3px 0 1px; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; margin-top: 5px; }
        thead { display: table-header-group; }
        tbody { page-break-inside: avoid; }
        th, td { border: 1px solid #000; padding: 1px 1px; font-size: 6.6px; text-align: center; overflow: hidden; }
        th { background: #e8e8e8; font-weight: bold; vertical-align: middle; }
        td.name { text-align: left; padding-left: 3px; font-size: 7px; }
        td.sun, th.sun { background: #dcdcdc; }
        td.ot { color: #333; font-size: 6px; }
        td.lbl { text-align: left; padding-left: 3px; font-size: 6.4px; font-weight: bold; }
        tr.otrow td { height: 9px; }
    </style>
</head>
<body>
@php
    $n = count($register['days']);
    $dayWidth = 2.3;
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
@endphp
<div class="line">{{ $contractorLine }}</div>
<div class="line">{{ $principalLine }}</div>
<div class="title">Register of Attendance</div>
<div class="line">{{ $contractPeriod }}</div>
<div class="line">{{ $monthLabel }}</div>

<table>
    <colgroup>
        <col style="width:2.6%"><col style="width:13%">
        @foreach ($register['days'] as $d)<col style="width:{{ $dayWidth }}%">@endforeach
        <col style="width:{{ (100 - 15.6 - $n * $dayWidth) / 3 }}%"><col style="width:{{ (100 - 15.6 - $n * $dayWidth) / 3 }}%"><col style="width:{{ (100 - 15.6 - $n * $dayWidth) / 3 }}%">
    </colgroup>
    <thead>
        <tr>
            @php $tail = (100 - 15.6 - $n * $dayWidth) / 3; @endphp
            <th style="width:2.6%">S.No.</th><th style="width:13%">Name</th>
            @foreach ($register['days'] as $d)<th class="{{ $d['sunday'] ? 'sun' : '' }}" style="width:{{ $dayWidth }}%">{{ $d['day'] }}</th>@endforeach
            <th style="width:{{ $tail }}%">Present Days</th><th style="width:{{ $tail }}%">Holidays</th><th style="width:{{ $tail }}%">Total Present Days including Holidays</th>
        </tr>
    </thead>
    @foreach ($register['employees'] as $e)
        <tbody>
            <tr>
                <td>{{ $e['serial'] }}</td>
                <td class="name">{{ $e['name'] }}</td>
                @foreach ($e['days'] as $i => $cell)<td class="{{ $register['days'][$i]['sunday'] ? 'sun' : '' }}">{{ $cell['code'] }}</td>@endforeach
                <td>{{ $num($e['present']) }}</td><td>{{ $e['holidays'] }}</td><td>{{ $num($e['total']) }}</td>
            </tr>
            <tr class="otrow">
                <td></td>
                <td class="lbl">OT HOURS</td>
                @foreach ($e['days'] as $i => $cell)<td class="ot {{ $register['days'][$i]['sunday'] ? 'sun' : '' }}">{{ $cell['ot'] > 0 ? $num($cell['ot']) : '' }}</td>@endforeach
                <td></td><td></td><td>{{ $num($e['ot_hours']) }}</td>
            </tr>
        </tbody>
    @endforeach
</table>
</body>
</html>
