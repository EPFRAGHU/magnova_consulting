<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Wage;
use Illuminate\Support\Collection;

class WageCalculator
{
    public function __construct(private WageRegisterCalculator $register) {}

    public function calculateForEmployee(Employee $employee, int $month, int $year): Wage
    {
        $workingDays = $this->register->workingDays($employee, $month, $year);

        $ratePerDay = (float) $employee->rate_per_day;
        $grossWages = round($workingDays * $ratePerDay, 2);
        $figures = $this->register->figures($grossWages, $month, $year, (bool) $employee->client?->bonus_enabled, (bool) $employee->client?->service_charge_on_basic, (bool) $employee->client?->gst_exempt, $employee);
        $bonus = $figures['bonus'];
        $epfEmployee = $figures['epf_employee'];
        $esicEmployee = $figures['esic_employee'];
        $netSalary = $figures['net_payable_salary'];

        return Wage::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'tenant_id' => $employee->tenant_id,
                'working_days' => $workingDays,
                'rate_per_day' => $ratePerDay,
                'gross_wages' => $grossWages,
                'bonus' => $bonus,
                'epf_employee' => $epfEmployee,
                'esic_employee' => $esicEmployee,
                // The rules and decision behind this ESIC figure; later changes to the settings never alter it.
                'esic_snapshot' => $figures['esic_result']?->snapshot(),
                'net_salary' => $netSalary,
            ]
        );
    }

    public function calculateForClient(int $clientId, int $month, int $year): Collection
    {
        return Employee::query()
            ->with('client')
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->orderBySkill()
            ->get()
            ->map(fn (Employee $employee) => $this->calculateForEmployee($employee, $month, $year));
    }
}
