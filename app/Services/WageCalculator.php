<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Wage;
use Illuminate\Support\Collection;

class WageCalculator
{
    public function calculateForEmployee(Employee $employee, int $month, int $year): Wage
    {
        $workingDays = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->sum(fn (Attendance $attendance) => match ($attendance->status) {
                'present', 'sunday' => 1,
                default => 0,
            });

        $ratePerDay = (float) $employee->rate_per_day;
        $grossWages = round($workingDays * $ratePerDay, 2);
        $bonus = round(
            min($grossWages, config('magnova.bonus_cap')) * config('magnova.bonus_pct') / 100,
            2,
        );
        $epfEmployee = round(min($grossWages, 15000) * config('magnova.epf_employee_pct') / 100, 2);
        $esicEmployee = round(min($grossWages, 21000) * config('magnova.esic_employee_pct') / 100, 2);
        $netSalary = round($grossWages + $bonus - $epfEmployee - $esicEmployee, 2);

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
                'net_salary' => $netSalary,
            ]
        );
    }

    public function calculateForClient(int $clientId, int $month, int $year): Collection
    {
        return Employee::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->orderBySkill()
            ->get()
            ->map(fn (Employee $employee) => $this->calculateForEmployee($employee, $month, $year));
    }
}
