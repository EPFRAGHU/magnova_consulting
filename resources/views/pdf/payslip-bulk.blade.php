<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf.partials.styles')
</head>
<body>
    @foreach($wages as $wage)
        <div @if(!$loop->last) class="page-break" @endif>
            @include('pdf.partials.payslip-header', ['wage' => $wage])
            @include('pdf.partials.payslip-slip', ['wage' => $wage, 'ytd' => $ytd[$wage->id], 'netSalaryWords' => $netSalaryWords[$wage->id]])
        </div>
    @endforeach
</body>
</html>
