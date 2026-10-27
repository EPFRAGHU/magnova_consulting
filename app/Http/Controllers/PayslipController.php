<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContractWage;
use App\Models\Wage;
use App\Services\ContractPayslip;
use App\Services\PdfExportService;
use App\Support\WageMonth;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PayslipController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = $request->integer('client_id') ?: null;
        $month = $request->integer('month') ?: WageMonth::month();
        $year = $request->integer('year') ?: WageMonth::year();

        $contractClient = $this->contractClient($clientId);

        // A contract-labour unit has no regular wage records: its payslips come from the saved Register of Wages.
        $wages = $contractClient
            ? app(ContractPayslip::class)->forMonth($contractClient, $month, $year)->map(fn (ContractWage $line) => [
                'id' => $line->id,
                'net_salary' => (string) ($line->figures['net_salary'] ?? 0),
                'employee' => $line->employee->only(['id', 'name', 'employee_code']),
            ])->values()
            : Wage::query()
                ->with('employee')
                ->whereHas('employee', fn ($q) => $q->when($clientId, fn ($q2) => $q2->where('client_id', $clientId)))
                ->where('month', $month)
                ->where('year', $year)
                ->get()
                ->sortBy(fn (Wage $wage) => $wage->employee->skillSortKey())
                ->values();

        return Inertia::render('payslips/index', [
            'contract' => $contractClient !== null,
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

    /** One payslip of a contract-labour unit, printed from its saved wage line. */
    public function contractShow(ContractWage $contractWage, PdfExportService $pdf): HttpResponse
    {
        $contractWage->load('employee');
        $client = Client::findOrFail($contractWage->client_id);
        $fileName = "payslip-{$contractWage->employee->name}-{$contractWage->month}-{$contractWage->year}.pdf";

        return $pdf->contractPayslips($client, new EloquentCollection([$contractWage]))->download($fileName);
    }

    public function bulk(Request $request, PdfExportService $pdf): HttpResponse
    {
        $clientId = $request->integer('client_id') ?: null;
        $month = $request->integer('month');
        $year = $request->integer('year');

        $contractClient = $this->contractClient($clientId);
        if ($contractClient) {
            $lines = app(ContractPayslip::class)->forMonth($contractClient, $month, $year);
            abort_if($lines->isEmpty(), 404, 'No saved wages for this unit and month.');

            return $pdf->contractPayslips($contractClient, $lines)->download("payslips-{$month}-{$year}.pdf");
        }

        $wages = Wage::query()
            ->with('employee')
            ->whereHas('employee', fn ($q) => $q->when($clientId, fn ($q2) => $q2->where('client_id', $clientId)))
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        return $pdf->bulkPayslips($wages, $month, $year)->download("payslips-{$month}-{$year}.pdf");
    }

    /** The unit when it is billed as contract labour; those units have their own payslips. */
    private function contractClient(?int $clientId): ?Client
    {
        $client = $clientId ? Client::find($clientId) : null;

        return $client?->payroll_profile === 'contract_labour' ? $client : null;
    }
}
