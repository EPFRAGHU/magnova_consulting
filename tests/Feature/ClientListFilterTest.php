<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientListFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $make = fn (array $extra) => Client::create(['tenant_id' => $this->user->tenant_id] + $extra);
        $make(['name' => 'MCPL', 'unit_label' => 'Visakhapatnam Centre, PURI', 'gstin' => '37AAAGC1724Q1DI', 'contact_person' => 'RANJITA', 'contact_phone' => '7008960018', 'active' => true]);
        $make(['name' => 'MCPL-BL Jatani', 'unit_label' => 'Balmer Lawrie', 'gstin' => '21AABCB0984E1Z7', 'contact_person' => 'Suresh', 'contact_phone' => '9437000000', 'active' => false]);
    }

    private function names(array $query): array
    {
        return collect($this->actingAs($this->user)->get(route('clients.index', $query))->viewData('page')['props']['clients'])->pluck('name')->all();
    }

    public function test_search_looks_in_name_location_gstin_and_contact(): void
    {
        $this->assertSame(['MCPL', 'MCPL-BL Jatani'], $this->names([]));
        $this->assertSame(['MCPL'], $this->names(['search' => 'puri']), 'location');
        $this->assertSame(['MCPL-BL Jatani'], $this->names(['search' => '21AABCB']), 'GSTIN');
        $this->assertSame(['MCPL'], $this->names(['search' => 'ranjita']), 'contact person');
        $this->assertSame(['MCPL-BL Jatani'], $this->names(['search' => '943700']), 'phone');
    }

    public function test_the_status_filter_shows_active_or_inactive_clients(): void
    {
        $this->assertSame(['MCPL'], $this->names(['status' => 'active']));
        $this->assertSame(['MCPL-BL Jatani'], $this->names(['status' => 'inactive']));
        $this->assertSame(['MCPL', 'MCPL-BL Jatani'], $this->names(['status' => 'anything-else']));
    }
}
