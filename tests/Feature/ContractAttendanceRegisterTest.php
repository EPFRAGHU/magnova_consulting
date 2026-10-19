<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContractAttendanceRegister;
use App\Services\ContractLabourAttendance;
use App\Services\PdfExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractAttendanceRegisterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $contract;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->tenant->update(['name' => 'Magnova Consulting Pvt. Ltd.']);
        $this->contract = Client::create([
            'tenant_id' => $this->user->tenant_id, 'name' => 'Balmer Lawrie - Jatni', 'active' => true, 'payroll_profile' => 'contract_labour',
            'principal_employer_name' => 'Balmer Lawrie and Co. Ltd.', 'principal_employer_address' => 'Jatni, Khordha',
            'contract_start' => '2026-09-01', 'contract_end' => '2029-08-30', 'register_contractor_address' => 'Sisupalgarh, Bhubaneswar - 751020',
        ]);
    }

    private function guard(string $name = 'Guard', ?Client $client = null): Employee
    {
        static $n = 0;
        $n++;

        return Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => ($client ?? $this->contract)->id, 'employee_code' => 'AR-'.$n, 'name' => "{$name} {$n}",
            'skill_category' => 'skilled', 'rate_per_day' => 781, 'status' => 'active', 'designation' => 'FLO',
        ]);
    }

    private function mark(Employee $employee, array $days, int $month = 9, int $year = 2026): void
    {
        foreach ($days as $day => [$status, $ot]) {
            Attendance::create([
                'tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('%04d-%02d-%02d', $year, $month, $day),
                'status' => $status, 'ot_hours' => $ot,
            ]);
        }
    }

    public function test_the_register_codes_and_totals_follow_the_clients_sheet(): void
    {
        $employee = $this->guard();
        $this->mark($employee, [
            1 => ['present', 2], 2 => ['present', 0], 3 => ['absent', 0], 4 => ['half_day', 0], 5 => ['holiday', 0],
            6 => ['off', 4.5], 7 => ['sunday', 0], 8 => ['leave', 0],
        ]);

        $data = app(ContractAttendanceRegister::class)->data($this->contract, 9, 2026);
        $row = $data['employees'][0];

        $this->assertSame(30, count($data['days']));
        $this->assertSame(['P', 'P', 'A', 'HD', 'H', '', '', ''], array_slice(array_column($row['days'], 'code'), 0, 8));
        $this->assertEquals(4.5, $row['days'][5]['ot'], 'overtime on a weekly off is kept');
        $this->assertEquals(2.5, $row['present'], '2 present + 1 half day');
        $this->assertEquals(1, $row['holidays']);
        $this->assertEquals(3.5, $row['total']);
        $this->assertEquals(6.5, $row['ot_hours']);
    }

    public function test_the_register_totals_agree_with_the_wage_sheet_service(): void
    {
        $employee = $this->guard();
        $this->mark($employee, [1 => ['present', 0], 2 => ['half_day', 1], 3 => ['holiday', 0], 4 => ['absent', 0]]);

        $row = app(ContractAttendanceRegister::class)->data($this->contract, 9, 2026)['employees'][0];
        $summary = app(ContractLabourAttendance::class)->forEmployees(collect([$employee->id]), 9, 2026)[$employee->id];

        $this->assertEquals($summary['paid_days'], $row['total']);
        $this->assertEquals($summary['ot_hours'], $row['ot_hours']);
    }

    public function test_only_active_employees_of_the_unit_and_only_this_month_are_included(): void
    {
        $mine = $this->guard('Mine');
        $left = $this->guard('Left');
        $left->update(['status' => 'left']);
        $other = $this->guard('Other', Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Plain', 'active' => true]));
        $this->mark($mine, [1 => ['present', 0]]);
        $this->mark($mine, [31 => ['present', 9]], 8, 2026);

        $data = app(ContractAttendanceRegister::class)->data($this->contract, 9, 2026);

        $this->assertCount(1, $data['employees']);
        $this->assertSame($mine->name, $data['employees'][0]['name']);
        $this->assertEquals(1, $data['employees'][0]['total']);
        $this->assertEquals(0, $data['employees'][0]['ot_hours'], 'last month is not counted');
    }

    public function test_a_short_month_has_only_its_own_days(): void
    {
        $this->guard();

        $this->assertCount(28, app(ContractAttendanceRegister::class)->data($this->contract, 2, 2027)['days']);
        $this->assertCount(31, app(ContractAttendanceRegister::class)->data($this->contract, 10, 2026)['days']);
    }

    public function test_the_register_page_has_the_clients_headings(): void
    {
        $employee = $this->guard();
        $this->mark($employee, [1 => ['present', 2]]);

        $html = app(ContractAttendanceRegister::class)->view($this->contract, 9, 2026)->render();

        foreach ([
            'CONTRACTOR NAME - MAGNOVA CONSULTING PVT. LTD', 'SISUPALGARH', 'PRINCIPAL EMPLOYER- BALMER LAWRIE AND CO. LTD.', 'Register of Attendance',
            'Contract Period - 01.09.2026 To 30.08.2029', 'Month - September,2026', 'S.No.', 'Name', 'OT HOURS', 'Present Days', 'Holidays',
            'Total Present Days including Holidays',
        ] as $text) {
            $this->assertStringContainsStringIgnoringCase($text, $html, $text);
        }
    }

    public function test_a_blank_contractor_address_falls_back_to_magnovas_own_on_the_screens_and_the_register(): void
    {
        $this->user->tenant->update(['address' => 'Magnova Plot 1, Sisupalgarh']);
        $this->contract->update(['register_contractor_address' => null]);
        $this->guard();
        $query = ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026];

        $this->actingAs($this->user)->get(route('attendance.index', $query))
            ->assertInertia(fn ($page) => $page->where('client.register_contractor_address', 'Magnova Plot 1, Sisupalgarh'));
        $this->actingAs($this->user)->get(route('wages.index', $query))
            ->assertInertia(fn ($page) => $page->where('client.register_contractor_address', 'Magnova Plot 1, Sisupalgarh'));
        $this->assertStringContainsString('MAGNOVA PLOT 1, SISUPALGARH', app(ContractAttendanceRegister::class)->view($this->contract, 9, 2026)->render());

        $this->contract->update(['register_contractor_address' => 'Override Road']);
        $this->actingAs($this->user)->get(route('attendance.index', $query))
            ->assertInertia(fn ($page) => $page->where('client.register_contractor_address', 'Override Road'));
    }

    public function test_the_pdf_downloads_and_a_regular_unit_is_refused(): void
    {
        $this->mark($this->guard(), [1 => ['present', 0]]);

        $response = $this->actingAs($this->user)->get(route('attendance.contract.register', ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026]));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $plain = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Plain', 'active' => true]);
        $this->actingAs($this->user)->get(route('attendance.contract.register', ['client_id' => $plain->id, 'month' => 9, 'year' => 2026]))->assertStatus(422);
    }

    public function test_a_full_unit_of_24_employees_prints_on_at_most_two_pages(): void
    {
        foreach (range(1, 24) as $i) {
            $employee = $this->guard();
            $this->mark($employee, array_fill_keys(range(1, 30), ['present', 1]));
        }

        $pdf = app(PdfExportService::class)->contractLabourAttendanceRegister($this->contract, 9, 2026)->output();

        $this->assertLessThanOrEqual(2, preg_match_all('#/Type\s*/Page\b#', $pdf));
    }
}
