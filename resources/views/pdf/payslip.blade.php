<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf.partials.styles')
</head>
<body>
    @include('pdf.partials.payslip-header', ['wage' => $wage, 'logoDataUri' => $logoDataUri])
    @include('pdf.partials.payslip-slip', ['wage' => $wage, 'ytd' => $ytd, 'netSalaryWords' => $netSalaryWords])
</body>
</html>
