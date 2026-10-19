<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 0.4in; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8px; color: #000; }
        .line { text-align: center; font-weight: bold; font-size: 9px; margin-bottom: 1px; }
        .title { text-align: center; font-weight: bold; font-size: 13px; margin: 4px 0 2px; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; margin-top: 6px; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #000; padding: 3px 3px; font-size: 7.5px; text-align: center; vertical-align: middle; }
        th { background: #e8e8e8; font-weight: bold; }
        th.sub { font-size: 7px; }
        tr.numbers td { font-size: 7px; background: #f4f4f4; }
        td.name { text-align: left; font-size: 8px; }
        td.nil { font-weight: bold; letter-spacing: 1px; }
    </style>
</head>
<body>
@php
    $span = count($columns);
    $hasSub = collect($columns)->contains(fn ($c) => isset($c['sub']));
@endphp
<div class="line">{{ $contractorLine }}</div>
<div class="line">{{ $principalLine }}</div>
<div class="title">{{ $title }}</div>
<div class="line">{{ $contractPeriod }}</div>
<div class="line">{{ $monthLabel }}</div>

<table>
    <thead>
        <tr>
            @foreach ($columns as $c)
                @if (isset($c['sub']))
                    <th style="width:{{ $c['width'] }}%">{{ $c['label'] }}</th>
                @else
                    <th style="width:{{ $c['width'] }}%" @if ($hasSub) rowspan="2" @endif>{{ $c['label'] }}</th>
                @endif
            @endforeach
        </tr>
        @if ($hasSub)
            <tr>
                @foreach ($columns as $c)@if (isset($c['sub']))<th class="sub">{{ $c['sub'] }}</th>@endif @endforeach
            </tr>
        @endif
        <tr class="numbers">
            @foreach ($columns as $i => $c)<td>{{ $i + 1 }}</td>@endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($workmen as $w)
            <tr>
                <td>{{ $w['serial'] }}</td>
                <td class="name">{{ $w['name'] }}</td>
                <td class="nil" colspan="{{ $span - 2 }}">NIL</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
