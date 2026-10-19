<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientContractProfileTest extends TestCase
{
    use RefreshDatabase;

    private function contractFields(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Balmer Lawrie - Jatni', 'active' => 1, 'payroll_profile' => 'contract_labour',
            'principal_employer_name' => 'Balmer Lawrie and Co. Ltd.',
            'principal_employer_address' => 'Plot No 5&13 (P), Chhatabar Industrial Area, Jatni, Khordha, Odisha - 752054',
            'principal_employer_gstin' => '21AAACB0000A1Z5',
            'contract_start' => '2026-09-01', 'contract_end' => '2029-08-30',
            'register_contractor_address' => 'Plot No 7253332, Sisupalgarh, Lingipur, Bhubaneswar, Khordha, Odisha - 751020',
        ], $overrides);
    }

    public function test_every_unit_is_a_standard_unit_unless_set_otherwise(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Old unit', 'active' => true]);

        $this->assertSame('standard', $client->fresh()->payroll_profile);
        $this->assertNull($client->fresh()->principal_employer_name);
    }

    public function test_a_contract_labour_unit_is_saved_with_its_principal_employer_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), $this->contractFields())->assertSessionHasNoErrors();

        $client = Client::where('name', 'Balmer Lawrie - Jatni')->firstOrFail();
        $this->assertSame('contract_labour', $client->payroll_profile);
        $this->assertSame('Balmer Lawrie and Co. Ltd.', $client->principal_employer_name);
        $this->assertSame('2026-09-01', $client->contract_start->toDateString());
        $this->assertSame('2029-08-30', $client->contract_end->toDateString());
        $this->assertStringContainsString('Sisupalgarh', $client->register_contractor_address);
    }

    public function test_a_contract_labour_unit_must_name_its_principal_employer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), $this->contractFields(['principal_employer_name' => '']))
            ->assertSessionHasErrors('principal_employer_name');
        $this->assertSame(0, Client::count());
    }

    public function test_a_standard_unit_needs_none_of_those_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), ['name' => 'Plain unit', 'active' => 1, 'payroll_profile' => 'standard'])
            ->assertSessionHasNoErrors();
        $this->assertSame('standard', Client::first()->payroll_profile);
    }

    public function test_the_contract_cannot_end_before_it_starts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), $this->contractFields(['contract_start' => '2029-08-30', 'contract_end' => '2026-09-01']))
            ->assertSessionHasErrors('contract_end');
    }

    public function test_an_unknown_profile_is_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), ['name' => 'X', 'active' => 1, 'payroll_profile' => 'mystery'])
            ->assertSessionHasErrors('payroll_profile');
    }

    public function test_saving_an_existing_unit_without_the_profile_field_keeps_it_standard_and_changes_nothing_else(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Old unit', 'active' => true, 'bonus_enabled' => true]);

        $this->actingAs($user)->put(route('clients.update', $client), ['name' => 'Old unit renamed', 'active' => 1, 'bonus_enabled' => 1])
            ->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertSame('standard', $client->payroll_profile);
        $this->assertSame('Old unit renamed', $client->name);
        $this->assertTrue($client->bonus_enabled);
    }

    public function test_switching_a_unit_back_to_standard_keeps_the_stored_details(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('clients.store'), $this->contractFields());
        $client = Client::first();

        $this->actingAs($user)->put(route('clients.update', $client), ['name' => $client->name, 'active' => 1, 'payroll_profile' => 'standard'])
            ->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertSame('standard', $client->payroll_profile);
        $this->assertSame('Balmer Lawrie and Co. Ltd.', $client->principal_employer_name, 'details are not lost');
    }

    public function test_the_clients_list_exposes_the_profile(): void
    {
        $user = User::factory()->create();
        Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Contract unit', 'active' => true, 'payroll_profile' => 'contract_labour', 'principal_employer_name' => 'BL']);

        $this->actingAs($user)->get(route('clients.index'))
            ->assertInertia(fn ($page) => $page->where('clients.0.payroll_profile', 'contract_labour'));
    }
}
