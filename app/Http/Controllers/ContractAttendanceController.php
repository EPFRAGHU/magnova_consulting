<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Services\PdfExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** Saves the attendance register of a contract-labour client: P / A / HD / holiday per day plus overtime hours. */
class ContractAttendanceController extends Controller
{
    public const CODES = ['present', 'absent', 'half_day', 'holiday'];

    /** The printable Register of Attendance of a contract-labour unit for a month. */
    public function register(Request $request, PdfExportService $pdf): HttpResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
        ]);

        $client = Client::findOrFail($data['client_id']);
        abort_unless($client->payroll_profile === 'contract_labour', 422, 'This unit does not use the contract labour attendance register.');

        $fileName = 'register-of-attendance-'.str_replace(['/', '\\', ' '], '-', $client->name)."-{$data['month']}-{$data['year']}.pdf";

        return $pdf->contractLabourAttendanceRegister($client, (int) $data['month'], (int) $data['year'])->download($fileName);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
            'records' => ['present', 'array'],
            'records.*.employee_id' => ['required', 'exists:employees,id'],
            'records.*.date' => ['required', 'date_format:Y-m-d'],
            'records.*.status' => ['nullable', 'in:'.implode(',', self::CODES)],
            'records.*.ot_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
        ]);

        $month = (int) $data['month'];
        $year = (int) $data['year'];
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $employees = Employee::with('client')
            ->whereIn('id', collect($data['records'])->pluck('employee_id')->unique())
            ->get()
            ->keyBy('id');

        foreach ($data['records'] as $record) {
            $date = Carbon::createFromFormat('Y-m-d', $record['date']);
            $employee = $employees->get((int) $record['employee_id']);

            abort_unless(
                $employee
                && $employee->client?->payroll_profile === 'contract_labour'
                && $date->year === $year && $date->month === $month && $date->day <= $daysInMonth,
                422,
                'Attendance contains an invalid employee or date.',
            );
        }

        DB::transaction(function () use ($data, $employees): void {
            foreach ($data['records'] as $record) {
                $employee = $employees->get((int) $record['employee_id']);
                $ot = round((float) ($record['ot_hours'] ?? 0), 2);
                $status = $record['status'] ?? null;
                $match = ['employee_id' => $employee->id, 'date' => $record['date']];

                if ($status === null && $ot <= 0) {
                    // A blank cell: there is nothing to keep.
                    Attendance::where('employee_id', $employee->id)->whereDate('date', $record['date'])->delete();

                    continue;
                }

                $existing = Attendance::where('employee_id', $employee->id)->whereDate('date', $record['date'])->first();
                // Overtime on a day with no attendance code is kept as a weekly off with overtime.
                $values = ['tenant_id' => $employee->tenant_id, 'status' => $status ?? 'off', 'ot_hours' => $ot];

                $existing ? $existing->update($values) : Attendance::create($match + $values);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attendance saved.']);

        return back();
    }
}
