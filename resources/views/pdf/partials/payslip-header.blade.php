@php
    $monthName = \Carbon\Carbon::create()->month($wage->month)->format('F');
@endphp
<table class="payslip-header-table">
    <tr>
        <td class="logo-cell">@include('pdf.partials.logo-mark')</td>
        <td>
            <div class="company-name">Magnova Consulting Pvt. Ltd.</div>
            <div class="company-address">{{ $wage->employee->tenant->address }}</div>
        </td>
    </tr>
</table>
<div class="payslip-band">Payslip for the month of {{ $monthName }} {{ $wage->year }}</div>
