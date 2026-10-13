<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Wage;
use App\Services\PdfExportService;
use App\Services\WageCalculator;
use App\Services\WageRegisterCalculator;
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
        $month = $request->integer('month') ?: (int) now()->format('n');
        $year = $request->integer('year') ?: (int) now()->format('Y');

        $employees = Employee::query()
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
            'headings' => $registerCalculator->headings($month, $year),
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
