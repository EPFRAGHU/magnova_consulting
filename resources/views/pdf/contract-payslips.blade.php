<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf.partials.styles')
</head>
<body>
    @foreach ($slips as $slip)
        <div @if (! $loop->last) class="page-break" @endif>
            @include('pdf.partials.payslip-header', ['wage' => $slip['line'], 'logoDataUri' => $logoDataUri])
            @include('pdf.partials.contract-payslip-slip', ['line' => $slip['line'], 'ytd' => $slip['ytd'], 'netSalaryWords' => $slip['words'], 'headings' => $headings])
        </div>
    @endforeach
</body>
</html>
