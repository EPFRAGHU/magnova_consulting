<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MaintenanceNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_banner_is_off_by_default(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('maintenance', null));
    }

    public function test_switching_it_on_shows_the_message_on_every_page_including_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('maintenance.update'), ['active' => 1, 'message' => 'Updating, back at 6 PM.'])
            ->assertSessionHasNoErrors();

        $this->get(route('clients.index'))
            ->assertInertia(fn ($page) => $page->where('maintenance', 'Updating, back at 6 PM.'));

        auth()->logout();
        $this->get(route('login'))
            ->assertInertia(fn ($page) => $page->where('maintenance', 'Updating, back at 6 PM.'));
    }

    public function test_an_empty_message_falls_back_to_the_default_and_off_removes_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('maintenance.update'), ['active' => 1, 'message' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame(MaintenanceNotice::DEFAULT_MESSAGE, app(MaintenanceNotice::class)->message());

        $this->actingAs($user)->patch(route('maintenance.update'), ['message' => 'ignored'])
            ->assertSessionHasNoErrors();
        $this->assertNull(app(MaintenanceNotice::class)->message());
    }

    public function test_the_settings_page_loads(): void
    {
        $this->actingAs(User::factory()->create())->get(route('maintenance.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('active', false));
    }
}
