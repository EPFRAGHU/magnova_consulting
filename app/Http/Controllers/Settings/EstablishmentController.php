<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstablishmentController extends Controller
{
    public function edit(Request $request): Response
    {
        $tenant = $request->user()->tenant;

        return Inertia::render('settings/establishment', [
            'establishment' => array_merge($tenant->only([
                'name',
                'gstin',
                'pan',
                'establishment_address1',
                'establishment_address2',
                'establishment_city',
                'establishment_district',
                'establishment_pin',
                'establishment_mobile',
                'establishment_email',
                'establishment_website',
            ]), [
                'establishment_address1' => $tenant->establishment_address1 ?: $tenant->address,
                'establishment_mobile' => $tenant->establishment_mobile ?: $tenant->contact_phone,
                'establishment_email' => $tenant->establishment_email ?: $tenant->contact_email,
            ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:30'],
            'pan' => ['nullable', 'string', 'max:20'],
            'establishment_address1' => ['nullable', 'string', 'max:255'],
            'establishment_address2' => ['nullable', 'string', 'max:255'],
            'establishment_city' => ['nullable', 'string', 'max:100'],
            'establishment_district' => ['nullable', 'string', 'max:100'],
            'establishment_pin' => ['nullable', 'string', 'max:10'],
            'establishment_mobile' => ['nullable', 'string', 'max:30'],
            'establishment_email' => ['nullable', 'email', 'max:255'],
            'establishment_website' => ['nullable', 'url', 'max:255'],
        ]);

        $data['address'] = collect([
            $data['establishment_address1'] ?? null,
            $data['establishment_address2'] ?? null,
            $data['establishment_city'] ?? null,
            $data['establishment_district'] ?? null,
            $data['establishment_pin'] ?? null,
        ])->filter()->implode(', ');
        $data['contact_phone'] = $data['establishment_mobile'] ?? null;
        $data['contact_email'] = $data['establishment_email'] ?? null;

        $request->user()->tenant->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Establishment details updated.']);

        return to_route('establishment.edit');
    }
}