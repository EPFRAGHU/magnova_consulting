<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContractLabourAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $contract;

    private Client $standard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->contract = Client::create([
            'tenant_id' => $this->user->tenant_id, 'name' => 'Balmer Lawrie - Jatni', 'active' => true,
            'payroll_profile' => 'contract_labour', 'principal_employer_name' => 'Balmer Lawrie and Co. Ltd.',
            'contract_start' => '2026-09-01', 'contract_end' => '2029-08-30',
        ]);
        $this->standard = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Plain unit', 'active' => true]);
    }

    private function guard(Client $client, string $name = 'Guard'): Employee
    {
        static $n = 0;
        $n++;

        return Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'CA-'.$n, 'name' => "{$name} {$n}",
            'skill_category' => 'skilled', 'rate_per_day' => 781, 'status' => 'active', 'designation' => 'FLO',
        ]);
    }

    private function save(array $records, int $month = 9, int $year = 2026)
    {
        return $this->actingAs($this->user)->post(route('attendance.contract.save'), ['month' => $month, 'year' => $year, 'records' => $records]);
    }

    public function test_half_day_and_overtime_can_be_stored_and_old_statuses_still_work(): void
    {
        $employee = $this->guard($this->contract);

        Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'half_day', 'ot_hours' => 2.5]);
        Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => '2026-09-02', 'status' => 'present']);
        Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => '2026-09-03', 'status' => 'sunday']);

        $this->assertEquals(2.5, (float) Attendance::whereDate('date', '2026-09-01')->first()->ot_hours);
        $this->assertEquals(0, (float) Attendance::whereDate('date', '2026-09-02')->first()->ot_hours, 'overtime defaults to none');
        $this->assertSame('sunday', Attendance::whereDate('date', '2026-09-03')->first()->status);
    }

    public function test_the_contract_attendance_page_shows_for_a_contract_labour_unit_and_the_old_page_for_others(): void
    {
        $this->guard($this->contract);
        $this->guard($this->standard);

        $this->actingAs($this->user)->get(route('attendance.index', ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026]))
            ->assertInertia(fn ($page) => $page->component('attendance/contract-labour')
                ->where('client.principal_employer_name', 'Balmer Lawrie and Co. Ltd.')
                ->has('employees', 1)
                ->where('days_in_month', 30));

        $this->actingAs($this->user)->get(route('attendance.index', ['client_id' => $this->standard->id, 'month' => 9, 'year' => 2026]))
            ->assertInertia(fn ($page) => $page->component('attendance/index'));

        $this->actingAs($this->user)->get(route('attendance.index'))
            ->assertInertia(fn ($page) => $page->component('attendance/index'));
    }

    public function test_the_page_carries_each_employees_codes_and_overtime_by_day(): void
    {
        $employee = $this->guard($this->contract);
        Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'present', 'ot_hours' => 2]);
        Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => '2026-09-02', 'status' => 'half_day']);

        $this->actingAs($this->user)->get(route('attendance.index', ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026]))
            ->assertInertia(fn ($page) => $page
                ->where("days.{$employee->id}.1.status", 'present')
                ->where("days.{$employee->id}.1.ot", 2)
                ->where("days.{$employee->id}.2.status", 'half_day'));
    }

    public function test_saving_creates_updates_and_clears_cells(): void
    {
        $employee = $this->guard($this->contract);

        $this->save([
            ['employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'present', 'ot_hours' => 2],
            ['employee_id' => $employee->id, 'date' => '2026-09-02', 'status' => 'half_day', 'ot_hours' => 0],
            ['employee_id' => $employee->id, 'date' => '2026-09-03', 'status' => 'absent', 'ot_hours' => 0],
            ['employee_id' => $employee->id, 'date' => '2026-09-04', 'status' => 'holiday', 'ot_hours' => 0],
        ])->assertSessionHasNoErrors();

        $this->assertSame(4, Attendance::count());

        $this->save([
            ['employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'absent', 'ot_hours' => 0],   // changed
            ['employee_id' => $employee->id, 'date' => '2026-09-02', 'status' => null, 'ot_hours' => 0],        // cleared
        ])->assertSessionHasNoErrors();

        $this->assertSame('absent', Attendance::whereDate('date', '2026-09-01')->first()->status);
        $this->assertEquals(0, (float) Attendance::whereDate('date', '2026-09-01')->first()->ot_hours);
        $this->assertNull(Attendance::whereDate('date', '2026-09-02')->first(), 'a blank cell removes the record');
        $this->assertSame(3, Attendance::count());
    }

    public function test_overtime_on_a_blank_day_is_kept(): void
    {
        $employee = $this->guard($this->contract);

        $this->save([['employee_id' => $employee->id, 'date' => '2026-09-06', 'status' => null, 'ot_hours' => 4]])->assertSessionHasNoErrors();

        $row = Attendance::whereDate('date', '2026-09-06')->first();
        $this->assertNotNull($row);
        $this->assertEquals(4, (float) $row->ot_hours);
        $this->assertSame('off', $row->status, 'stored as a weekly off with overtime');
    }

    public function test_saving_never_touches_other_months_or_employees(): void
    {
        $a = $this->guard($this->contract);
        $b = $this->guard($this->contract);
        Attendance::create(['tenant_id' => $a->tenant_id, 'employee_id' => $a->id, 'date' => '2026-08-31', 'status' => 'present']);
        Attendance::create(['tenant_id' => $b->tenant_id, 'employee_id' => $b->id, 'date' => '2026-09-01', 'status' => 'present']);

        $this->save([['employee_id' => $a->id, 'date' => '2026-09-01', 'status' => 'present', 'ot_hours' => 0]])->assertSessionHasNoErrors();

        $this->assertSame(3, Attendance::count());
        $this->assertSame('present', Attendance::where('employee_id', $a->id)->whereDate('date', '2026-08-31')->first()->status);
        $this->assertSame('present', Attendance::where('employee_id', $b->id)->first()->status);
    }

    public function test_an_employee_of_a_standard_unit_is_refused(): void
    {
        $employee = $this->guard($this->standard);

        $this->save([['employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'present', 'ot_hours' => 0]])->assertStatus(422);
        $this->assertSame(0, Attendance::count());
    }

    public function test_bad_input_is_refused(): void
    {
        $employee = $this->guard($this->contract);
        $row = fn (array $over) => [array_merge(['employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'present', 'ot_hours' => 0], $over)];

        $this->save($row(['status' => 'sunday']))->assertSessionHasErrors('records.0.status');
        $this->save($row(['status' => 'mystery']))->assertSessionHasErrors('records.0.status');
        $this->save($row(['ot_hours' => -1]))->assertSessionHasErrors('records.0.ot_hours');
        $this->save($row(['ot_hours' => 25]))->assertSessionHasErrors('records.0.ot_hours');
        $this->save($row(['date' => '2026-10-01']))->assertStatus(422);
        $this->save($row(['date' => '2026-09-31']))->assertSessionHasErrors('records.0.date');
        $this->assertSame(0, Attendance::count());
    }

    public function test_paid_days_count_presents_half_days_and_holidays_only(): void
    {
        $employee = $this->guard($this->contract);
        $days = ['present' => [1, 2, 3, 4, 5], 'half_day' => [6, 7], 'holiday' => [8], 'absent' => [9, 10], 'off' => [11, 12], 'sunday' => [13], 'leave' => [14]];
        foreach ($days as $status => $list) {
            foreach ($list as $day) {
                Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('2026-09-%02d', $day), 'status' => $status, 'ot_hours' => $day === 1 ? 2 : ($day === 12 ? 3.5 : 0)]);
            }
        }
        Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => '2026-08-31', 'status' => 'present', 'ot_hours' => 9]);

        $summary = app(ContractLabourAttendance::class)->forEmployees(collect([$employee->id]), 9, 2026)[$employee->id];

        $this->assertEquals(5, $summary['present']);
        $this->assertEquals(2, $summary['half_days']);
        $this->assertEquals(1, $summary['holidays']);
        $this->assertEquals(5 + 1 + 1, $summary['paid_days'], '5 present + 2 half days + 1 holiday; weekly offs, Sundays, absences and leave unpaid');
        $this->assertEquals(5.5, $summary['ot_hours'], 'overtime of the month only');
    }

    public function test_an_employee_with_no_records_has_zero_everything(): void
    {
        $employee = $this->guard($this->contract);

        $summary = app(ContractLabourAttendance::class)->forEmployees(collect([$employee->id]), 9, 2026);

        $this->assertEquals(0, $summary[$employee->id]['paid_days'] ?? 0);
        $this->assertEquals(0, $summary[$employee->id]['ot_hours'] ?? 0);
    }

    public function test_the_standard_attendance_save_still_works_without_overtime(): void
    {
        $employee = $this->guard($this->standard);

        $this->actingAs($this->user)->post(route('attendance.save'), [
            'month' => 9, 'year' => 2026, 'records' => [['employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'sunday']],
        ])->assertSessionHasNoErrors();

        $this->assertSame('sunday', Attendance::first()->status);
        $this->assertEquals(0, (float) Attendance::first()->ot_hours);
    }
}
