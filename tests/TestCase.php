<?php

namespace Tests;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /** Switch the client ON in Clients & Sites for this user; the data menus then show only that client. */
    protected function loadClient(User $user, Client $client): void
    {
        $user->forceFill(['loaded_client_id' => $client->id])->save();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
