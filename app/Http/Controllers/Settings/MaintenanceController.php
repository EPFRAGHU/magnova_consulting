<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\MaintenanceNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function edit(MaintenanceNotice $notice): Response
    {
        return Inertia::render('settings/maintenance', [
            'active' => $notice->message() !== null,
            'message' => $notice->message() ?? MaintenanceNotice::DEFAULT_MESSAGE,
        ]);
    }

    public function update(Request $request, MaintenanceNotice $notice): RedirectResponse
    {
        $request->merge(['active' => $request->boolean('active')]);

        $data = $request->validate([
            'active' => ['boolean'],
            'message' => ['nullable', 'string', 'max:300'],
        ]);

        if ($data['active']) {
            $notice->turnOn($data['message'] ?? null);
        } else {
            $notice->turnOff();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $data['active'] ? 'Maintenance banner is ON for everyone.' : 'Maintenance banner is OFF.',
        ]);

        return to_route('maintenance.edit');
    }
}
