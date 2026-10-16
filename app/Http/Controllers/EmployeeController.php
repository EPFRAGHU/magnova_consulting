<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DesignationSacCode;
use App\Models\Employee;
use App\Services\DesignationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = $request->integer('client_id') ?: null;

        $employees = Employee::query()
            ->with('client')
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->orderBySkill()
            ->get();

        return Inertia::render('employees/index', [
            'employees' => $employees,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'unit_label']),
            'client_id' => $clientId,
        ]);
    }

    /** The master list of designations, each with the SAC code and description that apply to it. */
    private function designationOptions(DesignationCatalog $catalog): array
    {
        return DesignationSacCode::query()
            ->orderBy('designation')
            ->get()
            ->map(fn (DesignationSacCode $row) => ['designation' => $row->designation] + collect($catalog->lookup($row->tenant_id, $row->designation))->only(['sac_code', 'sac_description'])->all())
            ->all();
    }

    public function create(DesignationCatalog $catalog): Response
    {
        return Inertia::render('employees/form', [
            'clients' => Client::where('active', true)->orderBy('name')->get(['id', 'name', 'unit_label']),
            'designations' => $this->designationOptions($catalog),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Employee::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee created.']);

        return to_route('employees.index');
    }

    public function edit(Employee $employee, DesignationCatalog $catalog): Response
    {
        return Inertia::render('employees/form', [
            'employee' => $employee,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'unit_label']),
            'designations' => $this->designationOptions($catalog),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validated($request, $employee));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee updated.']);

        return to_route('employees.index');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee archived.']);

        return to_route('employees.index');
    }

    private function validated(Request $request, ?Employee $employee = null): array
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'employee_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female'],
            'uan' => ['nullable', 'string', 'max:20'],
            'epf_member_id' => ['nullable', 'string', 'max:50'],
            'esic_no' => ['nullable', 'string', 'max:50'],
            'skill_category' => ['required', 'in:unskilled,semi_skilled,skilled,high_skilled'],
            'designation' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail) use ($employee) {
                $keepsCurrent = $employee && mb_strtolower(trim((string) $employee->designation)) === mb_strtolower(trim((string) $value));

                if (! $keepsCurrent && ! DesignationSacCode::query()->whereRaw('LOWER(designation) = ?', [mb_strtolower(trim((string) $value))])->exists()) {
                    $fail('Choose a designation from the list. Add new ones under Settings > SAC codes.');
                }
            }],
            'rate_per_day' => ['required', 'numeric', 'min:0'],
            'date_of_joining' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive,left'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
        ]);

        // Store the designation spelled exactly as in the master list so it matches everywhere.
        if (! empty($data['designation'])) {
            $data['designation'] = DesignationSacCode::query()
                ->whereRaw('LOWER(designation) = ?', [mb_strtolower(trim($data['designation']))])
                ->value('designation') ?? trim($data['designation']);
        }

        return $data;
    }
}
