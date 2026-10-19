<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Collection;

/**
 * Month totals from the contract-labour attendance register. Present = 1 day, half day = 0.5, a holiday is paid;
 * absences, leave and weekly offs are not. Overtime hours are summed from the same rows.
 */
class ContractLabourAttendance
{
    /**
     * @param  Collection<int, int>  $employeeIds
     * @return array<int, array{present: float, half_days: int, holidays: int, absent: int, paid_days: float, ot_hours: float}>
     */
    public function forEmployees(Collection $employeeIds, int $month, int $year): array
    {
        $rows = Attendance::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get(['employee_id', 'status', 'ot_hours']);

        $summary = [];

        foreach ($rows as $row) {
            $s = $summary[$row->employee_id] ?? ['present' => 0.0, 'half_days' => 0, 'holidays' => 0, 'absent' => 0, 'paid_days' => 0.0, 'ot_hours' => 0.0];

            match ($row->status) {
                'present' => $s['present']++,
                'half_day' => $s['half_days']++,
                'holiday' => $s['holidays']++,
                'absent' => $s['absent']++,
                default => null,
            };
            $s['ot_hours'] += (float) $row->ot_hours;

            $summary[$row->employee_id] = $s;
        }

        foreach ($summary as $employeeId => $s) {
            $summary[$employeeId]['paid_days'] = $s['present'] + 0.5 * $s['half_days'] + $s['holidays'];
        }

        return $summary;
    }
}
