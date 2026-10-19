<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

/**
 * The printed Register of Attendance of a contract-labour client: one block per employee with the day codes
 * (P, A, HD, H; blank for a weekly off) and an "OT HOURS" row, plus the present, holiday, total and overtime columns.
 */
class ContractAttendanceRegister
{
    private const CODES = ['present' => 'P', 'absent' => 'A', 'half_day' => 'HD', 'holiday' => 'H'];

    /** @return array{days: array<int, array{day: int, weekday: string, sunday: bool}>, employees: array<int, array<string, mixed>>} */
    public function data(Client $client, int $month, int $year): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $days = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $days[] = ['day' => $day, 'weekday' => $date->format('D'), 'sunday' => $date->isSunday()];
        }

        $employees = Employee::query()->where('client_id', $client->id)->where('status', 'active')->orderBySkill()->get();
        $records = Attendance::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('employee_id');

        $rows = [];
        foreach ($employees->values() as $i => $employee) {
            $byDay = ($records[$employee->id] ?? collect())->keyBy(fn (Attendance $a) => $a->date->day);
            $cells = [];
            $present = 0.0;
            $holidays = 0;
            $ot = 0.0;

            foreach ($days as $d) {
                $record = $byDay->get($d['day']);
                $status = $record?->status;
                $hours = (float) ($record?->ot_hours ?? 0);

                $cells[] = ['code' => self::CODES[$status] ?? '', 'ot' => $hours];
                $present += match ($status) {
                    'present' => 1.0,
                    'half_day' => 0.5,
                    default => 0.0,
                };
                $holidays += $status === 'holiday' ? 1 : 0;
                $ot += $hours;
            }

            $rows[] = [
                'serial' => $i + 1,
                'name' => $employee->name,
                'days' => $cells,
                'present' => $present,
                'holidays' => $holidays,
                'total' => $present + $holidays,
                'ot_hours' => $ot,
            ];
        }

        return ['days' => $days, 'employees' => $rows];
    }

    /** The four heading lines shared by every printed register of the client (attendance, fines, deductions). */
    public function headings(Client $client, int $month, int $year): array
    {
        $tenant = Tenant::find($client->tenant_id);
        $contractor = strtoupper(trim(preg_replace('~^\s*m/s\.?\s*~i', '', (string) ($tenant?->name ?? 'Magnova Consulting Pvt. Ltd.'))));
        $address = $client->contractorAddress();

        return [
            'contractorLine' => 'CONTRACTOR NAME - '.$contractor.($address ? ', '.strtoupper($address) : ''),
            'principalLine' => 'PRINCIPAL EMPLOYER- '.strtoupper((string) $client->principal_employer_name)
                .($client->principal_employer_address ? ', '.strtoupper($client->principal_employer_address) : ''),
            'contractPeriod' => 'Contract Period - '.($client->contract_start?->format('d.m.Y') ?? '-').' To '.($client->contract_end?->format('d.m.Y') ?? '-'),
            'monthLabel' => 'Month - '.Carbon::create($year, $month, 1)->format('F').','.$year,
        ];
    }

    public function view(Client $client, int $month, int $year): View
    {
        return view('pdf.contract-labour-attendance-register', ['register' => $this->data($client, $month, $year)] + $this->headings($client, $month, $year));
    }
}
