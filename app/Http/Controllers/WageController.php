<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Wage;
use App\Services\ContractWageSheet;
use App\Services\PdfExportService;
use App\Services\WageCalculator;
use App\Services\WageRegisterCalculator;
use App\Support\WageMonth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WageController extends Controller
{
    public function index(Request $request, WageRegisterCalculator $registerCalculator): Response
    {
        $clientId = $request->integer('client_id') ?: null;
        $month = $request->integer('month') ?: WageMonth::month();
        $year = $request->integer('year') ?: WageMonth::year();

        $selected = $clientId ? Client::find($clientId) : null;

        if ($selected?->payroll_profile === 'contract_labour') {
            return $this->contractLabour($selected, $month, $year, $request, app(ContractWageSheet::class));
        }

        $employees = Employee::query()
            ->with('client')
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->where('status', 'active')
            ->orderBySkill()
            ->get();

        $existingWages = Wage::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('employee_id');

        $rows = $employees->values()->map(
            fn (Employee $employee, int $index) => $registerCalculator->row(
                $employee,
                $existingWages->get($employee->id),
                $month,
                $year,
                $index + 1,
            ),
        );

        return Inertia::render('wages/index', [
            'rows' => $rows,
            'clients' => Client::where('active', true)->orderBy('name')->get(['id', 'name', 'unit_label']),
            'client_id' => $clientId,
            'month' => $month,
            'year' => $year,
            'headings' => $registerCalculator->headings(
                $month,
                $year,
                $clientId ? (bool) Client::find($clientId)?->service_charge_on_basic : null,
                $clientId ? (bool) Client::find($clientId)?->gst_exempt : null,
                $request->user()->tenant_id,
                $clientId ? Client::find($clientId)?->esic_unit_mode : null,
            ),
        ]);
    }

    /** The Register of Wages in the client's own layout, for a contract-labour unit. */
    private function contractLabour(Client $client, int $month, int $year, Request $request, ContractWageSheet $sheet): Response
    {
        return Inertia::render('wages/contract-labour', [
            'client' => $client->only(['id', 'name', 'unit_label', 'principal_employer_name', 'principal_employer_address', 'contract_start', 'contract_end']) + ['register_contractor_address' => $client->contractorAddress()],
            ...($rows = $sheet->rows($client, $month, $year)),
            'invoice' => Invoice::where('client_id', $client->id)->where('month', $month)->where('year', $year)
                ->first(['id', 'invoice_no', 'status', 'grand_total']),
            'invoice_prefix' => Invoice::numberPrefix($request->user()->tenant->name, now()),
            'can_invoice' => $rows['rows'] !== [] && ! collect($rows['rows'])->contains(fn ($row) => ! $row['saved'] || $row['stale']),
            'headings' => $sheet->headings($client),
            'clients' => Client::where('active', true)->orderBy('name')->get(['id', 'name', 'unit_label']),
            'client_id' => $client->id,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function store(Request $request, WageCalculator $calculator): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
            'rows' => ['required', 'array'],
            'rows.*.employee_id' => ['required', 'exists:employees,id'],
        ]);

        foreach ($data['rows'] as $row) {
            $employee = Employee::findOrFail($row['employee_id']);
            $calculator->calculateForEmployee($employee, $data['month'], $data['year']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Wages saved.']);

        return back();
    }

    public function register(Request $request, PdfExportService $pdf): HttpResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
        ]);

        $client = Client::findOrFail($data['client_id']);
        $safeName = str_replace(['/', '\\'], '-', $client->name);
        $fileName = "wage-register-{$safeName}-{$data['month']}-{$data['year']}.pdf";

        return $pdf->wageRegister($client, $data['month'], $data['year'])->download($fileName);
    }
}
