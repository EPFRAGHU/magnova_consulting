<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\ContractWage;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContractLabourCalculator;
use App\Services\ContractWageSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractWageSheetTest extends TestCase
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
            'tenant_id' => $this->user->tenant_id, 'name' => 'Balmer Lawrie - Jatni', 'active' => true, 'payroll_profile' => 'contract_labour',
            'principal_employer_name' => 'Balmer Lawrie and Co. Ltd.', 'principal_employer_address' => 'Jatni, Khordha',
            'contract_start' => '2026-09-01', 'contract_end' => '2029-08-30', 'register_contractor_address' => 'Sisupalgarh, Bhubaneswar - 751020',
        ]);
        $this->standard = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Plain unit', 'active' => true]);
    }

    private function guard(Client $client, float $rate = 781, array $extra = []): Employee
    {
        static $n = 0;
        $n++;

        return Employee::create(array_merge([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'W-'.$n, 'name' => "Guard {$n}",
            'skill_category' => 'skilled', 'rate_per_day' => $rate, 'status' => 'active', 'designation' => 'FLO',
            'uan' => '1000000000'.$n, 'esic_no' => '52000000'.$n,
        ], $extra));
    }

    /** Gives an employee attendance that adds up to $paidDays paid days and $ot overtime hours in September 2026. */
    private function attend(Employee $employee, float $paidDays, float $ot = 0, int $month = 9, int $year = 2026): void
    {
        $whole = (int) floor($paidDays);
        $half = $paidDays - $whole > 0 ? 1 : 0;
        $statuses = array_merge(
            array_fill(0, max(0, $whole - 1), 'present'),
            $whole >= 1 ? ['holiday'] : [],
            $half ? ['half_day'] : [],
        );

        foreach ($statuses as $i => $status) {
            Attendance::create([
                'tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('%04d-%02d-%02d', $year, $month, $i + 1),
                'status' => $status, 'ot_hours' => $i === 0 ? $ot : 0,
            ]);
        }

        if ($ot > 0 && $statuses === []) {
            Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('%04d-%02d-01', $year, $month), 'status' => 'off', 'ot_hours' => $ot]);
        }
    }

    private function calculate(Client $client, int $month = 9, int $year = 2026)
    {
        return $this->actingAs($this->user)->post(route('wages.contract.save'), ['client_id' => $client->id, 'month' => $month, 'year' => $year]);
    }

    public function test_saving_the_sheet_reproduces_the_clients_own_figures_for_a_real_row(): void
    {
        config(['magnova.contract_labour.pf_round_digits' => 0]);   // the client's workbook rounds PF to the rupee
        $employee = $this->guard($this->contract);   // Ramesh Mohapatra: Rs 781, 26 paid days, 38 overtime hours
        $this->attend($employee, 26, 38);

        $this->calculate($this->contract)->assertSessionHasNoErrors();

        $wage = ContractWage::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEquals(781, (float) $wage->rate_per_day);
        $this->assertEquals(26, (float) $wage->paid_days);
        $this->assertEquals(38, (float) $wage->ot_hours);
        $this->assertEqualsWithDelta(30431.2398, $wage->figures['gross'], 0.0001);
        $this->assertEqualsWithDelta(33609.318549999996, $wage->figures['total_ctc'], 0.0001);
        $this->assertEqualsWithDelta(40952.954653174995, $wage->figures['amount_incl_gst'], 0.0001);
        $this->assertSame('FLO - Skilled', $wage->category);
        $this->assertNotNull($wage->parameters, 'the rates used are kept with the row');
    }

    public function test_a_saved_line_is_exact_to_the_paisa_by_default(): void
    {
        $employee = $this->guard($this->contract);
        $this->attend($employee, 26, 38);

        $this->calculate($this->contract)->assertSessionHasNoErrors();

        $wage = ContractWage::where('employee_id', $employee->id)->firstOrFail();
        $this->assertEqualsWithDelta(2097.136, $wage->figures['pf_employee'], 0.0001, 'PF is not rounded to the rupee');
        $this->assertEqualsWithDelta(33609.2159, $wage->figures['total_ctc'], 0.0001);
    }

    public function test_the_whole_september_sheet_adds_up_to_the_clients_workbook_totals(): void
    {
        config(['magnova.contract_labour.pf_round_digits' => 0]);   // the client's workbook rounds PF to the rupee
        $sheet = json_decode(file_get_contents(base_path('tests/Fixtures/contract_labour_sep_2026.json')), true);

        foreach ($sheet['rows'] as $row) {
            $employee = $this->guard($this->contract, $row['rate']);
            $this->attend($employee, $row['paid_days'], $row['ot_hours']);
        }

        $this->calculate($this->contract)->assertSessionHasNoErrors();

        $this->assertSame(24, ContractWage::count());
        $totals = [];
        foreach (ContractWage::all() as $wage) {
            foreach ($wage->figures as $key => $value) {
                $totals[$key] = ($totals[$key] ?? 0) + $value;
            }
        }
        foreach ($sheet['totals'] as $key => $expected) {
            $this->assertEqualsWithDelta($expected, $totals[$key], 0.001, "total {$key}");
        }
    }

    public function test_saving_twice_updates_the_same_rows_and_follows_attendance_changes(): void
    {
        $employee = $this->guard($this->contract);
        $this->attend($employee, 20);
        $this->calculate($this->contract);
        $first = ContractWage::first()->figures['gross'];

        Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-09-01')->update(['ot_hours' => 6]);
        $this->calculate($this->contract);

        $this->assertSame(1, ContractWage::count());
        $this->assertGreaterThan($first, ContractWage::first()->figures['gross']);
        $this->assertEquals(6, (float) ContractWage::first()->ot_hours);
    }

    public function test_only_active_employees_of_this_unit_are_included(): void
    {
        $mine = $this->guard($this->contract);
        $left = $this->guard($this->contract, 781, ['status' => 'left']);
        $other = $this->guard($this->standard);
        foreach ([$mine, $left, $other] as $e) {
            $this->attend($e, 20);
        }

        $this->calculate($this->contract);

        $this->assertSame([$mine->id], ContractWage::pluck('employee_id')->all());
    }

    public function test_an_employee_not_covered_by_esic_pays_none(): void
    {
        $employee = $this->guard($this->contract, 781, ['esic_applicable' => false]);
        $this->attend($employee, 26, 10);

        $this->calculate($this->contract);

        $figures = ContractWage::first()->figures;
        $this->assertSame(0.0, (float) $figures['esic_employee']);
        $this->assertSame(0.0, (float) $figures['esic_employer']);
        $this->assertGreaterThan(0, $figures['pf_employee']);
    }

    public function test_a_standard_unit_is_refused(): void
    {
        $this->attend($this->guard($this->standard), 20);

        $this->calculate($this->standard)->assertStatus(422);
        $this->assertSame(0, ContractWage::count());
    }

    public function test_the_wages_page_shows_the_contract_sheet_for_a_contract_unit_and_the_old_page_otherwise(): void
    {
        $this->attend($this->guard($this->contract), 26, 38);
        $this->attend($this->guard($this->standard), 20);

        $this->actingAs($this->user)->get(route('wages.index', ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026]))
            ->assertInertia(fn ($page) => $page->component('wages/contract-labour')
                ->where('client.principal_employer_name', 'Balmer Lawrie and Co. Ltd.')
                ->has('rows', 1)
                ->where('rows.0.saved', false)
                ->where('rows.0.paid_days', 26)
                ->where('rows.0.figures.gross', fn ($v) => abs($v - 30431.2398) < 0.0001)
                ->where('totals.figures.gross', fn ($v) => abs($v - 30431.2398) < 0.0001)
                ->has('headings'));

        $this->actingAs($this->user)->get(route('wages.index', ['client_id' => $this->standard->id, 'month' => 9, 'year' => 2026]))
            ->assertInertia(fn ($page) => $page->component('wages/index'));
    }

    public function test_a_saved_row_is_marked_saved_and_goes_stale_when_attendance_changes(): void
    {
        $employee = $this->guard($this->contract);
        $this->attend($employee, 20);
        $this->calculate($this->contract);

        $url = route('wages.index', ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026]);
        $this->actingAs($this->user)->get($url)
            ->assertInertia(fn ($page) => $page->where('rows.0.saved', true)->where('rows.0.stale', false));

        Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-09-02')->update(['status' => 'absent']);

        $this->actingAs($this->user)->get($url)
            ->assertInertia(fn ($page) => $page->where('rows.0.saved', true)->where('rows.0.stale', true));
    }

    public function test_saved_figures_are_kept_when_the_default_rates_change_later(): void
    {
        $employee = $this->guard($this->contract);
        $this->attend($employee, 26, 10);
        $this->calculate($this->contract);
        $saved = ContractWage::first()->figures['bonus'];

        config(['magnova.contract_labour.bonus_pct' => 0]);

        $this->actingAs($this->user)->get(route('wages.index', ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026]))
            ->assertInertia(fn ($page) => $page->where('rows.0.figures.bonus', fn ($v) => abs($v - $saved) < 0.0001)->where('rows.0.stale', false));
    }

    public function test_the_register_pdf_has_the_clients_layout(): void
    {
        $employee = $this->guard($this->contract);
        $this->attend($employee, 26, 38);
        $this->calculate($this->contract);

        $html = app(ContractWageSheet::class)->registerView($this->contract, 9, 2026, null)->render();

        foreach ([
            'REGISTER OF WAGES', 'MONTH- SEPTEMBER,2026', 'Sisupalgarh', 'Balmer Lawrie and Co. Ltd.', 'Employee Deduction', 'Employer Contribution',
            'Earn Wages', 'Leave Wages', 'PMJJBY', 'Service Charges', 'Amount Including GST', 'Total',
        ] as $text) {
            $this->assertStringContainsStringIgnoringCase($text, $html, $text);
        }

        $response = $this->actingAs($this->user)->get(route('wages.contract.register', ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026]));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_the_register_pdf_is_refused_for_a_standard_unit(): void
    {
        $this->actingAs($this->user)->get(route('wages.contract.register', ['client_id' => $this->standard->id, 'month' => 9, 'year' => 2026]))
            ->assertStatus(422);
    }

    public function test_zero_attendance_gives_a_zero_row_not_an_error(): void
    {
        $this->guard($this->contract);

        $this->calculate($this->contract)->assertSessionHasNoErrors();

        $this->assertEquals(0, ContractWage::first()->figures['amount_incl_gst']);
    }

    public function test_the_service_agrees_with_the_calculator(): void
    {
        $employee = $this->guard($this->contract, 650);
        $this->attend($employee, 22.5, 4);

        app(ContractWageSheet::class)->save($this->contract, 9, 2026);

        $expected = (new ContractLabourCalculator)->calculate(650, 22.5, 4, 9, 2026);
        foreach ($expected as $key => $value) {
            $this->assertEqualsWithDelta($value, ContractWage::first()->figures[$key], 0.0001, $key);
        }
    }
}
