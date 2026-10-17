<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EsicSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EsicSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function rules(array $overrides = []): array
    {
        return array_merge([
            'effective_from' => '2027-04-01', 'establishment_covered' => 1, 'calculation_mode' => 'statutory',
            'coverage_ceiling' => 25000, 'employee_rate' => 0.75, 'employer_rate' => 3.25, 'daily_wage_exemption' => 176,
            'rounding' => 'nearest',
        ], $overrides);
    }

    public function test_the_page_shows_the_rules_in_force_with_the_defaults_when_nothing_is_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('esic.edit'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('settings/esic')
                ->where('current.coverage_ceiling', 21000)
                ->where('current.employee_rate', 0.75)
                ->where('current.employer_rate', 3.25)
                ->where('current.calculation_mode', 'statutory')
                ->has('history', 0));
    }

    public function test_saving_rules_adds_a_dated_entry_starting_on_the_first_of_the_month(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('esic.store'), $this->rules(['effective_from' => '2027-04-17', 'note' => 'Ceiling raised']))
            ->assertSessionHasNoErrors()->assertRedirect(route('esic.edit'));

        $row = EsicSetting::first();
        $this->assertSame('2027-04-01', $row->effective_from->toDateString());
        $this->assertSame(25000.0, $row->coverage_ceiling);
        $this->assertSame($user->tenant_id, $row->tenant_id);
    }

    public function test_rules_for_a_month_that_already_has_rules_are_refused_so_history_is_never_edited(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('esic.store'), $this->rules());

        $this->actingAs($user)->post(route('esic.store'), $this->rules(['coverage_ceiling' => 30000]))
            ->assertSessionHasErrors('effective_from');

        $this->assertSame(1, EsicSetting::count());
        $this->assertSame(25000.0, EsicSetting::first()->coverage_ceiling);
    }

    public function test_the_capped_mode_needs_an_explicit_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('esic.store'), $this->rules(['calculation_mode' => 'capped']))
            ->assertSessionHasErrors('confirm_capped');
        $this->assertSame(0, EsicSetting::count());

        $this->actingAs($user)->post(route('esic.store'), $this->rules(['calculation_mode' => 'capped', 'confirm_capped' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertSame('capped', EsicSetting::first()->calculation_mode);
    }

    public function test_invalid_rules_are_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('esic.store'), $this->rules(['employee_rate' => 150, 'rounding' => 'banana', 'coverage_ceiling' => 0]))
            ->assertSessionHasErrors(['employee_rate', 'rounding', 'coverage_ceiling']);
    }

    public function test_the_employee_form_saves_the_esic_flags_and_leaves_them_alone_when_not_sent(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit A', 'active' => true]);
        $employee = Employee::create([
            'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'F-1', 'name' => 'Form Guard',
            'skill_category' => 'skilled', 'rate_per_day' => 500, 'status' => 'active',
        ]);
        $base = ['client_id' => $client->id, 'name' => 'Form Guard', 'skill_category' => 'skilled', 'rate_per_day' => 500, 'status' => 'active'];

        // The form always sends the marker; an unticked box sends nothing.
        $this->actingAs($user)->put(route('employees.update', $employee), $base + ['esic_applicable_form' => 1, 'esic_covered_from' => '2026-10-02'])
            ->assertSessionHasNoErrors();
        $employee->refresh();
        $this->assertFalse($employee->esic_applicable);
        $this->assertSame('2026-10-02', $employee->esic_covered_from->toDateString());

        $this->actingAs($user)->put(route('employees.update', $employee), $base + ['esic_applicable_form' => 1, 'esic_applicable' => 'on'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($employee->fresh()->esic_applicable);

        // Without the marker (any other caller) the setting is not touched.
        $employee->refresh()->update(['esic_applicable' => false]);
        $this->actingAs($user)->put(route('employees.update', $employee), $base)->assertSessionHasNoErrors();
        $this->assertFalse($employee->fresh()->esic_applicable);
    }

    public function test_existing_and_new_employees_are_esic_applicable_by_default(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit A', 'active' => true]);
        $employee = Employee::create([
            'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'D-1', 'name' => 'Default Guard',
            'skill_category' => 'skilled', 'rate_per_day' => 500, 'status' => 'active',
        ]);

        $this->assertTrue($employee->fresh()->esic_applicable);
    }
}
