<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_app_name_is_always_magnova_whatever_the_server_environment_says(): void
    {
        $this->assertSame('MAGNOVA', config('app.name'));

        $this->get('/')->assertInertia(fn ($page) => $page->where('name', 'MAGNOVA'));
        $this->assertStringContainsString('<title>MAGNOVA</title>', $this->get('/')->getContent());
        $this->assertStringNotContainsString('Laravel', $this->get(route('login'))->getContent());
    }

    public function test_the_home_page_is_public(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('welcome')->where('auth.user', null));
    }

    public function test_the_home_page_knows_when_someone_is_signed_in(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk()
            ->assertInertia(fn ($page) => $page->component('welcome')->where('auth.user.id', $user->id));
    }

    public function test_the_sign_in_page_posts_to_the_real_login_route(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/login')->has('canResetPassword'));
    }

    public function test_signing_in_with_wrong_details_shows_the_standard_error(): void
    {
        User::factory()->create(['email' => 'real@example.test']);

        $this->from(route('login'))->post(route('login.store'), ['email' => 'real@example.test', 'password' => 'not-the-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_signing_in_with_the_right_details_goes_to_the_dashboard(): void
    {
        $user = User::factory()->create(['email' => 'real@example.test']);

        $this->post(route('login.store'), ['email' => 'real@example.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_sign_in_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => 'real@example.test', 'password' => 'wrong'.$i]);
        }

        $this->post(route('login.store'), ['email' => 'real@example.test', 'password' => 'wrong'])->assertStatus(429);
    }
}
