<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Services\PdfExportService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = $request->integer('client_id') ?: null;
        $month = $request->integer('month') ?: (int) now()->format('n');
        $year = $request->integer('year') ?: (int) now()->format('Y');

        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        $employees = Employee::query()
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code']);

        $attendance = Attendance::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($row) => $row->date->day)->map->status);

        return Inertia::render('attendance/index', [
            'employees' => $employees,
            'attendance' => $attendance,
            'clients' => Client::where('active', true)->orderBy('name')->get(['id', 'name', 'unit_label']),
            'client_id' => $clientId,
            'month' => $month,
            'year' => $year,
            'days_in_month' => $daysInMonth,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,holiday,leave,sunday,off'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        // whereDate() (not where()) because the 'date' cast stores a full
        // datetime string, which a plain string-equality WHERE won't match.
        $existing = Attendance::where('employee_id', $data['employee_id'])
            ->whereDate('date', $data['date'])
            ->first();

        if ($existing) {
            $existing->update(['status' => $data['status']]);
        } else {
            try {
                Attendance::create([
                    'tenant_id' => $employee->tenant_id,
                    'employee_id' => $data['employee_id'],
                    'date' => $data['date'],
                    'status' => $data['status'],
                ]);
            } catch (QueryException) {
                // Two rapid clicks raced to insert the same employee/date row; the
                // other request already created it, so just update it now.
                Attendance::where('employee_id', $data['employee_id'])
                    ->whereDate('date', $data['date'])
                    ->update(['status' => $data['status']]);
            }
        }

        return back();
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
            'records' => ['present', 'array'],
            'records.*.employee_id' => ['required', 'exists:employees,id'],
            'records.*.date' => ['required', 'date_format:Y-m-d'],
            'records.*.status' => ['required', 'in:present,absent,holiday,leave,sunday,off'],
        ]);

        $daysInMonth = Carbon::create($data['year'], $data['month'], 1)->daysInMonth;
        $employeeIds = collect($data['records'])
            ->pluck('employee_id')
            ->unique()
            ->values();
        $employees = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');

        foreach ($data['records'] as $record) {
            $date = Carbon::createFromFormat('Y-m-d', $record['date']);

            abort_unless(
                $date->year === (int) $data['year'] &&
                    $date->month === (int) $data['month'] &&
                    $date->day <= $daysInMonth &&
                    $employees->has((int) $record['employee_id']),
                422,
                'Attendance contains an invalid employee or date.',
            );
        }

        DB::transaction(function () use ($data, $employees): void {
            foreach ($data['records'] as $record) {
                $employee = $employees->get((int) $record['employee_id']);

                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $record['date'],
                    ],
                    [
                        'tenant_id' => $employee->tenant_id,
                        'status' => $record['status'],
                    ],
                );
            }
        });

        return back();
    }

    public function sheet(Request $request, PdfExportService $pdf): HttpResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
        ]);

        $client = Client::findOrFail($data['client_id']);
        $safeName = str_replace(['/', '\\'], '-', $client->name);
        $fileName = "attendance-sheet-{$safeName}-{$data['month']}-{$data['year']}.pdf";

        return $pdf->attendanceSheet($client, $data['month'], $data['year'])->download($fileName);
    }
}
