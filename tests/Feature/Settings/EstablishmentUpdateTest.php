<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstablishmentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_establishment_details_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('establishment.update'), [
            'name' => 'Updated Establishment',
            'establishment_address1' => '12 Main Street',
            'establishment_address2' => 'Second Floor',
            'establishment_city' => 'Bhubaneswar',
            'establishment_district' => 'Khordha',
            'establishment_pin' => '751001',
            'establishment_mobile' => '9876543210',
            'establishment_email' => 'office@example.com',
            'establishment_website' => 'https://example.com',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('establishment.edit'));

        $this->assertDatabaseHas('tenants', [
            'id' => $user->tenant_id,
            'name' => 'Updated Establishment',
            'establishment_address1' => '12 Main Street',
            'establishment_city' => 'Bhubaneswar',
            'establishment_email' => 'office@example.com',
            'address' => '12 Main Street, Second Floor, Bhubaneswar, Khordha, 751001',
            'contact_phone' => '9876543210',
            'contact_email' => 'office@example.com',
        ]);
    }
}