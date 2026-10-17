<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
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

    private function unit(string $name, bool $onCeiling): Client
    {
        return Client::create(['tenant_id' => $this->tenant->id, 'name' => $name, 'active' => true, 'esic_on_ceiling' => $onCeiling]);
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

        $this->assertStringContainsString('capped at Rs.21,000', $register->headings(9, 2026, false, false, $this->tenant->id, true)['esic']);
        $this->assertStringContainsString('NOT statutory', $register->headings(9, 2026, false, false, $this->tenant->id, true)['esic_individual']);
        $this->assertStringNotContainsString('capped', $register->headings(9, 2026, false, false, $this->tenant->id, false)['esic']);
    }

    public function test_the_unit_form_saves_the_setting(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), ['name' => 'NACIN', 'active' => 1, 'esic_on_ceiling' => 1])
            ->assertSessionHasNoErrors();
        $client = Client::where('name', 'NACIN')->first();
        $this->assertTrue($client->esic_on_ceiling);

        $this->actingAs($user)->put(route('clients.update', $client), ['name' => 'NACIN', 'active' => 1])
            ->assertSessionHasNoErrors();
        $this->assertFalse($client->fresh()->esic_on_ceiling, 'an unticked box turns it off');
    }

    public function test_units_are_statutory_by_default(): void
    {
        $this->assertFalse(Client::create(['tenant_id' => $this->tenant->id, 'name' => 'Plain', 'active' => true])->fresh()->esic_on_ceiling);
    }
}
