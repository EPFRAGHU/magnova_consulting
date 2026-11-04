<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_lists_every_release_newest_first_and_needs_a_login(): void
    {
        $this->get(route('version-history'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())->get(route('version-history'))
            ->assertInertia(fn ($page) => $page->component('version-history')
                ->has('releases', count(config('releases')))
                ->where('releases.0.version', config('releases.0.version'))
                ->where('releases.0.changes.0.type', config('releases.0.changes.0.0')));
    }

    public function test_the_sidebar_version_comes_from_the_newest_release(): void
    {
        $this->assertSame(config('releases.0.version'), config('magnova.version'));
        $this->assertSame(substr(config('releases.0.released_at'), 0, 10), config('magnova.version_date'));
    }

    public function test_the_history_is_well_formed_so_a_new_entry_cannot_break_the_page(): void
    {
        $releases = config('releases');

        foreach ($releases as $i => $release) {
            $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $release['version']);
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $release['released_at']);
            $this->assertNotSame('', trim($release['title']));
            $this->assertNotEmpty($release['changes'], $release['version']);
            foreach ($release['changes'] as [$type, $text]) {
                $this->assertContains($type, ['new', 'improved', 'fixed'], $release['version']);
                $this->assertNotSame('', trim($text));
            }
            if ($i > 0) {
                $this->assertTrue(version_compare($releases[$i - 1]['version'], $release['version'], '>'), "{$release['version']} must be older than the entry above it");
                $this->assertGreaterThanOrEqual($release['released_at'], $releases[$i - 1]['released_at']);
            }
        }

        $this->assertSame(config('magnova.project_started'), end($releases)['released_at'], 'the oldest release is the project start');
    }
}
