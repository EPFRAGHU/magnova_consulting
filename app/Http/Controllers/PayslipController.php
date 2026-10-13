<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Wage;
use App\Services\PdfExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PayslipController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = $request->integer('client_id') ?: null;
        $month = $request->integer('month') ?: (int) now()->format('n');
        $year = $request->integer('year') ?: (int) now()->format('Y');

        $wages = Wage::query()
            ->with('employee')
            ->whereHas('employee', fn ($q) => $q->when($clientId, fn ($q2) => $q2->where('client_id', $clientId)))
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->sortBy(fn (Wage $wage) => $wage->employee->skillSortKey())
            ->values();

        return Inertia::render('payslips/index', [
            'wages' => $wages,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'unit_label']),
            'client_id' => $clientId,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function show(Wage $wage, PdfExportService $pdf): HttpResponse
    {
        $wage->load('employee');
        $fileName = "payslip-{$wage->employee->name}-{$wage->month}-{$wage->year}.pdf";

        return $pdf->payslip($wage)->download($fileName);
    }

    public function bulk(Request $request, PdfExportService $pdf): HttpResponse
    {
        $clientId = $request->integer('client_id') ?: null;
        $month = $request->integer('month');
        $year = $request->integer('year');

        $wages = Wage::query()
            ->with('employee')
            ->whereHas('employee', fn ($q) => $q->when($clientId, fn ($q2) => $q2->where('client_id', $clientId)))
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        return $pdf->bulkPayslips($wages, $month, $year)->download("payslips-{$month}-{$year}.pdf");
    }
}
