<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppInfoAndLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_and_privacy_are_open_to_everyone(): void
    {
        foreach (['terms' => 'legal/terms', 'privacy' => 'legal/privacy'] as $route => $component) {
            $this->get(route($route))->assertOk()->assertInertia(fn ($page) => $page->component($component));
        }
    }

    public function test_every_page_carries_the_version_developer_and_contact(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->where('appInfo.version', config('magnova.version'))
            ->where('appInfo.versionDate', config('magnova.version_date'))
            ->where('appInfo.developer', 'Digital Solutions Services')
            ->where('appInfo.contactEmail', config('magnova.legal_contact_email')));
    }

    public function test_the_version_and_its_date_are_set(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', config('magnova.version'));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', config('magnova.version_date'));
    }
}
