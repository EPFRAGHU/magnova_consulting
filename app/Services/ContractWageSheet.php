<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ContractWage;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The Register of Wages of a contract-labour client: builds each employee's line from the attendance register and
 * the client's rates (ContractLabourCalculator), saves it, and reads it back for the screen and the PDF.
 */
class ContractWageSheet
{
    public function __construct(
        private readonly ContractLabourCalculator $calculator,
        private readonly ContractLabourAttendance $attendance,
    ) {}

    /** The rates for a client: the defaults in config, ready for per-client overrides. */
    public function parameters(Client $client): array
    {
        return config('magnova.contract_labour');
    }

    /** @return Collection<int, Employee> */
    private function employees(Client $client): Collection
    {
        return Employee::query()->where('client_id', $client->id)->where('status', 'active')->orderBySkill()->get();
    }

    private function category(Employee $employee): string
    {
        return trim(($employee->designation ?: 'Guard').' - '.$employee->skillLabel());
    }

    /** @return array<string, float> */
    private function figures(Employee $employee, float $paidDays, float $otHours, int $month, int $year, array $parameters): array
    {
        return $this->calculator->calculate(
            (float) $employee->rate_per_day, $paidDays, $otHours, $month, $year, $parameters,
            esicApplicable: $employee->esic_applicable !== false,
        );
    }

    /**
     * One line per active employee: the saved line when there is one, otherwise a preview from the current attendance.
     * A saved line is "stale" when the attendance or rate it was calculated from has changed since.
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, mixed>}
     */
    public function rows(Client $client, int $month, int $year): array
    {
        $employees = $this->employees($client);
        $summary = $this->attendance->forEmployees($employees->pluck('id'), $month, $year);
        $saved = ContractWage::query()->where('client_id', $client->id)->where('month', $month)->where('year', $year)->get()->keyBy('employee_id');
        $parameters = $this->parameters($client);

        $rows = [];
        foreach ($employees->values() as $i => $employee) {
            $paid = (float) ($summary[$employee->id]['paid_days'] ?? 0);
            $ot = (float) ($summary[$employee->id]['ot_hours'] ?? 0);
            $rate = (float) $employee->rate_per_day;
            $line = $saved->get($employee->id);

            $rows[] = [
                'employee_id' => $employee->id,
                'serial' => $i + 1,
                'name' => $employee->name,
                'category' => $line?->category ?? $this->category($employee),
                'uan' => $employee->uan,
                'esic_no' => $employee->esic_no,
                'rate' => $line ? (float) $line->rate_per_day : $rate,
                'paid_days' => $line ? (float) $line->paid_days : $paid,
                'ot_hours' => $line ? (float) $line->ot_hours : $ot,
                'figures' => $line ? $line->figures : $this->figures($employee, $paid, $ot, $month, $year, $parameters),
                'saved' => $line !== null,
                'stale' => $line !== null && (
                    abs((float) $line->paid_days - $paid) > 0.005
                    || abs((float) $line->ot_hours - $ot) > 0.005
                    || abs((float) $line->rate_per_day - $rate) > 0.005
                ),
            ];
        }

        $totals = ['paid_days' => 0.0, 'ot_hours' => 0.0, 'figures' => []];
        foreach ($rows as $row) {
            $totals['paid_days'] += $row['paid_days'];
            $totals['ot_hours'] += $row['ot_hours'];
            foreach ($row['figures'] as $key => $value) {
                $totals['figures'][$key] = ($totals['figures'][$key] ?? 0.0) + $value;
            }
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    /** Calculates and saves the month for every active employee of the unit. Returns how many lines were saved. */
    public function save(Client $client, int $month, int $year): int
    {
        $employees = $this->employees($client);
        $summary = $this->attendance->forEmployees($employees->pluck('id'), $month, $year);
        $parameters = $this->parameters($client);

        foreach ($employees as $employee) {
            $paid = (float) ($summary[$employee->id]['paid_days'] ?? 0);
            $ot = (float) ($summary[$employee->id]['ot_hours'] ?? 0);

            ContractWage::updateOrCreate(
                ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
                [
                    'tenant_id' => $employee->tenant_id,
                    'client_id' => $client->id,
                    'category' => $this->category($employee),
                    'rate_per_day' => (float) $employee->rate_per_day,
                    'paid_days' => $paid,
                    'ot_hours' => $ot,
                    'figures' => $this->figures($employee, $paid, $ot, $month, $year, $parameters),
                    'parameters' => $parameters,
                    'calculated_at' => now(),
                ],
            );
        }

        return $employees->count();
    }

    /** Column headings that quote the rates in force. */
    public function headings(Client $client): array
    {
        $p = $this->parameters($client);
        $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.');

        return [
            'pf_employee' => 'PF '.$pct($p['pf_employee_pct']).'%',
            'esic_employee' => 'ESIC '.$pct($p['esic_employee_pct']).'%',
            'pf_employer' => 'PF '.$pct($p['pf_employer_pct']).'%',
            'esic_employer' => 'ESIC '.$pct($p['esic_employer_pct']).'%',
            'bonus' => 'Bonus',
        ];
    }

    public function registerView(Client $client, int $month, int $year, ?string $logoDataUri): View
    {
        return view('pdf.contract-labour-wage-register', [
            'client' => $client,
            'sheet' => $this->rows($client, $month, $year),
            'headings' => $this->headings($client),
            'monthLabel' => strtoupper(Carbon::create($year, $month, 1)->format('F')).','.$year,
            'logoDataUri' => $logoDataUri,
        ]);
    }
}
