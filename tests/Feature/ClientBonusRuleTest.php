<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Models\Wage;
use App\Services\EsicCalculator;
use App\Services\InvoiceCalculator;
use App\Services\WageCalculator;
use App\Support\BonusRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientBonusRuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** The bonus of one employee for a client with these bonus settings. */
    private function rule(array $attributes, float $wage, ?float $days = 20, int $daysInMonth = 30): float
    {
        return BonusRule::forClient(new Client($attributes + ['bonus_enabled' => true]))->amount($wage, $days, $daysInMonth);
    }

    public function test_a_client_with_nothing_saved_keeps_the_original_rule(): void
    {
        $this->assertSame(1007.6, $this->rule([], 12096), '8.33% of 12,096');
        $this->assertSame(0.0, $this->rule([], 21000.01), 'nothing above Rs 21,000');
        $this->assertSame(0.0, BonusRule::forClient(new Client(['bonus_enabled' => false]))->amount(12096, 20, 30));
        $this->assertTrue(BonusRule::forClient(new Client(['bonus_enabled' => true]))->isOriginal());
        $this->assertTrue(BonusRule::forClient(new Client(['bonus_enabled' => true]))->billed, 'billed unless a client says otherwise');
    }

    public function test_the_percentage_can_be_changed(): void
    {
        $this->assertSame(500.0, $this->rule(['bonus_value' => 5], 10000));
    }

    public function test_a_daily_rate_is_rupees_times_paid_days(): void
    {
        $this->assertSame(600.0, $this->rule(['bonus_type' => 'per_day', 'bonus_value' => 30], 12000, 20));
        $this->assertSame(585.0, $this->rule(['bonus_type' => 'per_day', 'bonus_value' => 30], 12000, 19.5), 'half days count');
    }

    public function test_a_fixed_amount_is_the_same_every_month_or_reduced_for_fewer_days(): void
    {
        $fixed = ['bonus_type' => 'per_month', 'bonus_value' => 600];

        $this->assertSame(600.0, $this->rule($fixed, 9000, 10), 'the full amount whatever the days worked');
        $this->assertSame(200.0, $this->rule($fixed + ['bonus_prorate' => true], 9000, 10, 30), '10 of 30 days');
        $this->assertSame(600.0, $this->rule($fixed + ['bonus_prorate' => true], 9000, 31, 30), 'never above the full amount');
        $this->assertSame(0.0, $this->rule($fixed, 0, 0), 'no work, no bonus');
    }

    public function test_the_wage_limit_can_be_a_cut_off_none_or_a_different_bonus(): void
    {
        $this->assertSame(0.0, $this->rule(['bonus_ceiling' => 15000], 20000), 'a lower limit of 15,000');
        $this->assertSame(1000.0, $this->rule(['bonus_value' => 5, 'bonus_limit_mode' => 'none'], 20000), 'no limit');
        $this->assertSame(1666.0, $this->rule(['bonus_limit_mode' => 'none'], 20000), '8.33% on 20,000 and above too');

        $different = ['bonus_value' => 8.33, 'bonus_limit_mode' => 'different_above', 'bonus_ceiling' => 21000, 'bonus_value_above' => 4];
        $this->assertSame(833.0, $this->rule($different, 10000), 'below the limit');
        $this->assertSame(1200.0, $this->rule($different, 30000), '4% above the limit');
    }

    /** One guard of a client with these settings, 20 days present in October 2026, with the wage saved. */
    private function wage(array $client, float $rate = 800, int $days = 20): Wage
    {
        $unit = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Unit '.uniqid(), 'active' => true, 'bonus_enabled' => true] + $client);
        $employee = Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $unit->id, 'employee_code' => 'B-'.uniqid(), 'name' => 'Guard', 'gender' => 'male',
            'skill_category' => 'skilled', 'designation' => 'Guard', 'rate_per_day' => $rate, 'status' => 'active',
        ]);
        foreach (range(1, $days) as $day) {
            Attendance::create(['tenant_id' => $this->user->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('2026-10-%02d', $day), 'status' => 'present']);
        }

        return app(WageCalculator::class)->calculateForEmployee($employee->load('client'), 10, 2026);
    }

    public function test_saved_wages_keep_the_original_bonus_for_an_untouched_client(): void
    {
        $this->assertEquals(1332.8, $this->wage([])->bonus, '8.33% of 16,000');
    }

    public function test_a_daily_bonus_flows_into_the_wage_and_net_pay(): void
    {
        $wage = $this->wage(['bonus_type' => 'per_day', 'bonus_value' => 25]);

        $this->assertEquals(500, $wage->bonus, '20 days at Rs 25');
        $this->assertEqualsWithDelta(16000 + 500 - $wage->epf_employee - $wage->esic_employee, $wage->net_salary, 0.01);
    }

    public function test_a_bonus_that_is_not_billed_is_paid_but_left_off_the_invoice(): void
    {
        $billed = $this->wage(['bonus_billed' => true]);
        $unbilled = $this->wage(['bonus_billed' => false]);

        $this->assertEquals($billed->bonus, $unbilled->bonus, 'the employee gets it either way');

        $withBonus = app(InvoiceCalculator::class)->generateForClient(Client::find($billed->employee->client_id), 10, 2026, 'T/1');
        $withoutBonus = app(InvoiceCalculator::class)->generateForClient(Client::find($unbilled->employee->client_id), 10, 2026, 'T/2');

        $this->assertEqualsWithDelta((float) $withBonus->taxable_value - (float) $billed->bonus, (float) $withoutBonus->taxable_value, 0.01);
    }

    public function test_a_client_can_count_the_bonus_in_esic_wages_or_not(): void
    {
        $guard = fn (array $client) => Employee::create([
            'tenant_id' => $this->user->tenant_id,
            'client_id' => Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'E '.uniqid(), 'active' => true, 'bonus_enabled' => true] + $client)->id,
            'employee_code' => 'E-'.uniqid(), 'name' => 'Guard', 'skill_category' => 'skilled', 'rate_per_day' => 600, 'status' => 'active',
        ])->load('client');

        $esic = app(EsicCalculator::class);

        $this->assertEquals(12000, $esic->calculate(12000, 10, 2026, $guard(['bonus_in_esic' => false]), 1000)->contributionWages);
        $this->assertEquals(13000, $esic->calculate(12000, 10, 2026, $guard(['bonus_in_esic' => true]), 1000)->contributionWages);
        $this->assertEquals(12000, $esic->calculate(12000, 10, 2026, $guard([]), 1000)->contributionWages, 'the ESIC setting says no bonus by default');
    }

    public function test_the_client_form_saves_the_rule_and_the_register_heading_follows_it(): void
    {
        $this->actingAs($this->user)->post(route('clients.store'), [
            'name' => 'Fixed bonus unit', 'active' => 1, 'bonus_enabled' => 1, 'bonus_form' => 1, 'bonus_type' => 'per_month', 'bonus_value' => '750',
            'bonus_prorate' => 1, 'bonus_limit_mode' => 'none', 'bonus_in_esic' => 'yes',
        ])->assertSessionHasNoErrors();

        $client = Client::where('name', 'Fixed bonus unit')->firstOrFail();
        $this->assertSame('per_month', $client->bonus_type);
        $this->assertEquals(750, $client->bonus_value);
        $this->assertTrue($client->bonus_prorate);
        $this->assertSame('none', $client->bonus_limit_mode);
        $this->assertFalse($client->bonus_billed, 'the form sent no bonus_billed, so the box was unticked');
        $this->assertTrue($client->bonus_in_esic);
        $this->assertSame('Bonus @Rs.750 per month (reduced for fewer days) (no wage limit)', $client->bonusRule()->heading());
    }

    public function test_the_form_rejects_nonsense_values(): void
    {
        $this->actingAs($this->user)->post(route('clients.store'), ['name' => 'X', 'bonus_type' => 'lottery', 'bonus_value' => -5, 'bonus_limit_mode' => 'sometimes'])
            ->assertSessionHasErrors(['bonus_type', 'bonus_value', 'bonus_limit_mode']);
    }

    public function test_a_form_without_the_bonus_section_leaves_the_invoice_default_alone(): void
    {
        $this->actingAs($this->user)->post(route('clients.store'), ['name' => 'Old style', 'active' => 1, 'bonus_enabled' => 1])->assertSessionHasNoErrors();

        $this->assertTrue(Client::where('name', 'Old style')->firstOrFail()->bonus_billed);
    }

    public function test_the_original_heading_and_invoice_label_do_not_change(): void
    {
        $rule = BonusRule::forClient(new Client(['bonus_enabled' => true]));

        $this->assertSame('Bonus @8.33% of Monthly Wage (nil if Monthly Wage above Rs.21,000/-)', $rule->heading());
        $this->assertSame('Bonus @ 8.33% on capped monthly wages', $rule->invoiceLabel());
    }
}
