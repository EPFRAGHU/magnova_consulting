<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Wage;
use App\Services\PdfExportService;
use App\Services\WageCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WageController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = $request->integer('client_id') ?: null;
        $month = $request->integer('month') ?: (int) now()->format('n');
        $year = $request->integer('year') ?: (int) now()->format('Y');

        $employees = Employee::query()
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $existingWages = Wage::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('employee_id');

        $rows = $employees->map(function (Employee $employee) use ($existingWages, $month, $year) {
            if ($existing = $existingWages->get($employee->id)) {
                return [
                    'employee_id' => $employee->id,
                    'name' => $employee->name,
                    'employee_code' => $employee->employee_code,
                    'working_days' => (float) $existing->working_days,
                    'rate_per_day' => (float) $existing->rate_per_day,
                    'bonus' => (float) $existing->bonus,
                    'saved' => true,
                ];
            }

            $workingDays = (float) Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get()
                ->sum(fn (Attendance $a) => match ($a->status) {
                    'present', 'sunday' => 1,
                    default => 0,
                });

            return [
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
                'working_days' => $workingDays,
                'rate_per_day' => (float) $employee->rate_per_day,
                'bonus' => 0,
                'saved' => false,
            ];
        });

        return Inertia::render('wages/index', [
            'rows' => $rows,
            'clients' => Client::where('active', true)->orderBy('name')->get(['id', 'name', 'unit_label']),
            'client_id' => $clientId,
            'month' => $month,
            'year' => $year,
            'rates' => [
                'epf_employee_pct' => config('magnova.epf_employee_pct'),
                'esic_employee_pct' => config('magnova.esic_employee_pct'),
            ],
        ]);
    }

    public function store(Request $request, WageCalculator $calculator): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
            'rows' => ['required', 'array'],
            'rows.*.employee_id' => ['required', 'exists:employees,id'],
            'rows.*.bonus' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($data['rows'] as $row) {
            $employee = Employee::findOrFail($row['employee_id']);
            $calculator->calculateForEmployee($employee, $data['month'], $data['year'], (float) ($row['bonus'] ?? 0));
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
