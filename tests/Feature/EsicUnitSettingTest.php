<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wage;
use App\Services\EsicCalculator;
use App\Services\WageCalculator;
use App\Services\WageRegisterCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ESIC on the wage ceiling is chosen per unit (establishment the company supplies staff to), never for all units. */
class EsicUnitSettingTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test']);
    }

    /** @param bool|string $mode true = on the ceiling, false = statutory, or one of 'statutory', 'ceiling', 'cutoff' */
    private function unit(string $name, bool|string $mode): Client
    {
        $mode = is_bool($mode) ? ($mode ? 'ceiling' : 'statutory') : $mode;

        return Client::create(['tenant_id' => $this->tenant->id, 'name' => $name, 'active' => true, 'esic_unit_mode' => $mode]);
    }

    private function guard(Client $client, float $rate = 918, array $attributes = []): Employee
    {
        static $n = 0;
        $n++;

        return Employee::create(array_merge([
            'tenant_id' => $this->tenant->id, 'client_id' => $client->id, 'employee_code' => 'U-'.$n, 'name' => "Guard {$n}",
            'skill_category' => 'skilled', 'rate_per_day' => $rate, 'status' => 'active',
        ], $attributes))->load('client');
    }

    private function work(Employee $employee, int $days = 30): void
    {
        foreach (range(1, $days) as $day) {
            Attendance::create(['tenant_id' => $this->tenant->id, 'employee_id' => $employee->id, 'date' => sprintf('2026-09-%02d', $day), 'status' => 'present']);
        }
    }

    public function test_a_unit_set_to_the_ceiling_gives_esic_on_the_ceiling_even_for_new_employees_above_it(): void
    {
        $unit = $this->unit('NACIN', true);
        $result = app(EsicCalculator::class)->calculate(27540, 9, 2026, $this->guard($unit));

        $this->assertTrue($result->covered);
        $this->assertSame('capped', $result->mode);
        $this->assertSame('capped_unit_setting', $result->reason);
        $this->assertEquals(21000, $result->contributionWages);
        $this->assertEquals(158, $result->employeeAmount);
        $this->assertEquals(683, $result->employerAmount);
    }

    public function test_other_units_keep_the_statutory_rule(): void
    {
        $other = $this->unit('Other unit', false);

        $result = app(EsicCalculator::class)->calculate(27540, 9, 2026, $this->guard($other));

        $this->assertFalse($result->covered);
        $this->assertSame('statutory', $result->mode);
        $this->assertEquals(0, $result->employeeAmount + $result->employerAmount);
    }

    public function test_within_the_ceiling_a_capped_unit_pays_the_normal_amounts(): void
    {
        $result = app(EsicCalculator::class)->calculate(20000, 9, 2026, $this->guard($this->unit('NACIN', true)));

        $this->assertEquals(20000, $result->contributionWages);
        $this->assertEquals(150, $result->employeeAmount);
        $this->assertEquals(650, $result->employerAmount);
    }

    public function test_an_employee_marked_not_applicable_still_pays_nothing_in_a_capped_unit(): void
    {
        $result = app(EsicCalculator::class)->calculate(27540, 9, 2026, $this->guard($this->unit('NACIN', true), 918, ['esic_applicable' => false]));

        $this->assertFalse($result->covered);
        $this->assertSame('employee_not_applicable', $result->reason);
    }

    public function test_saving_wages_applies_each_units_own_rule(): void
    {
        $capped = $this->guard($this->unit('NACIN', true));
        $plain = $this->guard($this->unit('Other unit', false));
        $this->work($capped);
        $this->work($plain);

        $cappedWage = app(WageCalculator::class)->calculateForEmployee($capped, 9, 2026);
        $plainWage = app(WageCalculator::class)->calculateForEmployee($plain, 9, 2026);

        $this->assertEquals(158, $cappedWage->esic_employee);
        $this->assertEquals(0, $plainWage->esic_employee);
        $this->assertSame('capped', $cappedWage->esic_snapshot['mode']);
        $this->assertSame('statutory', $plainWage->esic_snapshot['mode']);
    }

    public function test_the_register_heading_tells_a_capped_unit_apart(): void
    {
        $register = app(WageRegisterCalculator::class);

        $this->assertStringContainsString('capped at Rs.21,000', $register->headings(9, 2026, false, false, $this->tenant->id, 'ceiling')['esic']);
        $this->assertStringContainsString('NOT statutory', $register->headings(9, 2026, false, false, $this->tenant->id, 'ceiling')['esic_individual']);
        $this->assertStringNotContainsString('capped', $register->headings(9, 2026, false, false, $this->tenant->id, 'statutory')['esic']);
    }

    public function test_the_unit_form_saves_the_setting(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), ['name' => 'NACIN', 'active' => 1, 'esic_unit_mode' => 'ceiling'])
            ->assertSessionHasNoErrors();
        $client = Client::where('name', 'NACIN')->first();
        $this->assertSame('ceiling', $client->esic_unit_mode);

        $this->actingAs($user)->put(route('clients.update', $client), ['name' => 'NACIN', 'active' => 1, 'esic_unit_mode' => 'cutoff'])
            ->assertSessionHasNoErrors();
        $this->assertSame('cutoff', $client->fresh()->esic_unit_mode);

        $this->actingAs($user)->put(route('clients.update', $client), ['name' => 'NACIN', 'active' => 1, 'esic_unit_mode' => 'nonsense'])
            ->assertSessionHasErrors('esic_unit_mode');
    }

    public function test_units_are_statutory_by_default(): void
    {
        $this->assertSame('statutory', Client::create(['tenant_id' => $this->tenant->id, 'name' => 'Plain', 'active' => true])->fresh()->esic_unit_mode);
    }

    public function test_a_unit_set_to_no_esic_above_the_ceiling_pays_nothing_above_it_even_for_a_continuing_employee(): void
    {
        $employee = $this->guard($this->unit('Cutoff unit', 'cutoff'));
        Wage::create([
            'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id, 'month' => 8, 'year' => 2026, 'working_days' => 22,
            'rate_per_day' => 918, 'gross_wages' => 20000, 'bonus' => 0, 'epf_employee' => 0, 'esic_employee' => 150, 'net_salary' => 19850,
        ]);

        $result = app(EsicCalculator::class)->calculate(27540, 9, 2026, $employee);

        $this->assertFalse($result->covered);
        $this->assertSame('cutoff', $result->mode);
        $this->assertSame('above_ceiling_unit_cutoff', $result->reason);
        $this->assertEquals(0, $result->employeeAmount + $result->employerAmount);

        $statutory = app(EsicCalculator::class)->calculate(27540, 9, 2026, $this->guard($this->unit('Plain unit', 'statutory')));
        $this->assertFalse($statutory->covered, 'new employee in a statutory unit: also nothing');
    }

    public function test_the_same_continuing_employee_in_a_statutory_unit_still_pays_on_actual_wages(): void
    {
        $employee = $this->guard($this->unit('Plain unit', 'statutory'));
        Wage::create([
            'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id, 'month' => 8, 'year' => 2026, 'working_days' => 22,
            'rate_per_day' => 918, 'gross_wages' => 20000, 'bonus' => 0, 'epf_employee' => 0, 'esic_employee' => 150, 'net_salary' => 19850,
        ]);

        $result = app(EsicCalculator::class)->calculate(27540, 9, 2026, $employee);

        $this->assertTrue($result->covered);
        $this->assertEquals(27540, $result->contributionWages);
    }

    public function test_within_the_ceiling_a_cutoff_unit_pays_the_normal_amounts(): void
    {
        $result = app(EsicCalculator::class)->calculate(21000, 9, 2026, $this->guard($this->unit('Cutoff unit', 'cutoff')));

        $this->assertTrue($result->covered);
        $this->assertEquals(158, $result->employeeAmount);
        $this->assertEquals(683, $result->employerAmount);
    }

    public function test_the_register_heading_for_a_cutoff_unit_says_so(): void
    {
        $heading = app(WageRegisterCalculator::class)->headings(9, 2026, false, false, $this->tenant->id, 'cutoff')['esic'];

        $this->assertStringContainsString('nil if', $heading);
        $this->assertStringContainsString('NOT statutory', $heading);
    }

    public function test_the_wages_page_headings_follow_the_selected_units_esic_mode(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $ceiling = $this->unit('Ceiling unit', 'ceiling');
        $cutoff = $this->unit('Cutoff unit', 'cutoff');
        $plain = $this->unit('Plain unit', 'statutory');

        $heading = fn (Client $unit) => $this->actingAs($user)->get(route('wages.index', ['client_id' => $unit->id, 'month' => 9, 'year' => 2026]))
            ->viewData('page')['props']['headings']['esic'];

        $this->assertStringContainsString('capped at Rs.21,000', $heading($ceiling));
        $this->assertStringContainsString('nil if wages above Rs.21,000', $heading($cutoff));
        $this->assertStringContainsString('covered employees only', $heading($plain));
    }
}
