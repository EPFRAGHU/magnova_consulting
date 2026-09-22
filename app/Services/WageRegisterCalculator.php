<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Wage;

class WageRegisterCalculator
{
    public function workingDays(Employee $employee, int $month, int $year): float
    {
        return (float) Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->sum(fn (Attendance $attendance) => match ($attendance->status) {
                'present', 'sunday' => 1,
                default => 0,
            });
    }

    public function row(Employee $employee, ?Wage $wage, int $month, int $year, int $serialNumber): array
    {
        $daysDeployed = $this->workingDays($employee, $month, $year);
        $minimumWage = (float) ($wage?->rate_per_day ?? $employee->rate_per_day);
        $monthlyWage = round($daysDeployed * $minimumWage, 2);
        $bonus = round(
            min($monthlyWage, config('magnova.bonus_cap')) * config('magnova.bonus_pct') / 100,
            2,
        );

        $epfEmployer = round(
            min($monthlyWage, 15000) * config('magnova.epf_employee_pct') / 100,
            2,
        );
        $edliAndEpfAdmin = round(
            min($monthlyWage, 15000)
                * (config('magnova.epf_admin_pct') + config('magnova.edli_cont_pct'))
                / 100,
            2,
        );
        $esicEmployer = round(
            min($monthlyWage, 21000) * config('magnova.esi_employer_pct') / 100,
            2,
        );
        $totalGrossSalary = round(
            $monthlyWage + $epfEmployer + $edliAndEpfAdmin + $esicEmployer + $bonus,
            2,
        );
        $gst = round(
            $totalGrossSalary
                * (config('magnova.cgst_pct') + config('magnova.sgst_pct'))
                / 100,
            2,
        );
        $agencyFee = round($totalGrossSalary * config('magnova.service_charge_pct') / 100, 2);
        $totalBillAmount = round($totalGrossSalary + $gst + $agencyFee, 2);
        $epfEmployee = round(
            min($monthlyWage, 15000) * config('magnova.epf_employee_pct') / 100,
            2,
        );
        $esicEmployee = round(
            min($monthlyWage, 21000) * config('magnova.esic_employee_pct') / 100,
            2,
        );
        $otherDeduction = 0.0;
        $netPayableSalary = round(
            $monthlyWage + $bonus - $epfEmployee - $esicEmployee - $otherDeduction,
            2,
        );

        return [
            'serial_number' => $serialNumber,
            'employee_id' => $employee->id,
            'name' => $employee->name,
            'employee_code' => $employee->employee_code,
            'nature_of_deployment' => $employee->designation
                ?: ucfirst($employee->skill_category).' Security Guard',
            'days_deployed' => $daysDeployed,
            'minimum_wage' => $minimumWage,
            'monthly_wage' => $monthlyWage,
            'epf_employer' => $epfEmployer,
            'edli_and_epf_admin' => $edliAndEpfAdmin,
            'esic_employer' => $esicEmployer,
            'bonus' => $bonus,
            'total_gross_salary' => $totalGrossSalary,
            'gst' => $gst,
            'agency_fee' => $agencyFee,
            'total_bill_amount' => $totalBillAmount,
            'epf_employee' => $epfEmployee,
            'esic_employee' => $esicEmployee,
            'other_deduction' => $otherDeduction,
            'net_payable_salary' => $netPayableSalary,
            'saved' => $wage !== null,
        ];
    }
}