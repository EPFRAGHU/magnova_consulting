<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
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
            ->orderBy('name')
            ->get();

        return Inertia::render('employees/index', [
            'employees' => $employees,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'unit_label']),
            'client_id' => $clientId,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('employees/form', [
            'clients' => Client::where('active', true)->orderBy('name')->get(['id', 'name', 'unit_label']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Employee::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee created.']);

        return to_route('employees.index');
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('employees/form', [
            'employee' => $employee,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'unit_label']),
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee updated.']);

        return to_route('employees.index');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee archived.']);

        return to_route('employees.index');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'employee_code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female'],
            'uan' => ['nullable', 'string', 'max:20'],
            'epf_member_id' => ['nullable', 'string', 'max:50'],
            'esic_no' => ['nullable', 'string', 'max:50'],
            'skill_category' => ['required', 'in:unskilled,semi_skilled,skilled,high_skilled'],
            'designation' => ['nullable', 'string', 'max:255'],
            'rate_per_day' => ['required', 'numeric', 'min:0'],
            'date_of_joining' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive,left'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
