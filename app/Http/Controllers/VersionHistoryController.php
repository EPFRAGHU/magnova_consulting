<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class VersionHistoryController extends Controller
{
    /** Every release from config/releases.php, newest first. */
    public function __invoke(): Response
    {
        return Inertia::render('version-history', [
            'releases' => array_map(fn (array $release) => [
                'version' => $release['version'],
                'released_at' => $release['released_at'],
                'title' => $release['title'],
                'changes' => array_map(fn (array $change) => ['type' => $change[0], 'text' => $change[1]], $release['changes']),
            ], (array) config('releases')),
        ]);
    }
}
