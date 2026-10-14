<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_units_default_to_no_bonus_and_service_charge_on_gross(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), ['name' => 'Unit A', 'active' => 1])
            ->assertSessionHasNoErrors();

        $client = Client::where('name', 'Unit A')->first();
        $this->assertFalse($client->bonus_enabled);
        $this->assertFalse($client->service_charge_on_basic);
    }

    public function test_unit_settings_can_be_ticked_and_unticked(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit B', 'active' => true]);

        $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Unit B',
            'active' => 1,
            'bonus_enabled' => 1,
            'service_charge_on_basic' => 1,
        ])->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertTrue($client->bonus_enabled);
        $this->assertTrue($client->service_charge_on_basic);

        $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Unit B',
            'active' => 1,
        ])->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertFalse($client->bonus_enabled);
        $this->assertFalse($client->service_charge_on_basic);
    }
}
