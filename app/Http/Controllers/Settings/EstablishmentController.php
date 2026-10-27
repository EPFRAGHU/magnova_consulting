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
                'pf_establishment_code',
                'establishment_address1',
                'establishment_address2',
                'establishment_city',
                'establishment_district',
                'establishment_pin',
                'establishment_mobile',
                'establishment_email',
                'establishment_website',
                'bank_name',
                'bank_account_no',
                'bank_ifsc',
                'bank_branch',
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
            'pf_establishment_code' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9]+$/'],
            'establishment_address1' => ['nullable', 'string', 'max:255'],
            'establishment_address2' => ['nullable', 'string', 'max:255'],
            'establishment_city' => ['nullable', 'string', 'max:100'],
            'establishment_district' => ['nullable', 'string', 'max:100'],
            'establishment_pin' => ['nullable', 'string', 'max:10'],
            'establishment_mobile' => ['nullable', 'string', 'max:30'],
            'establishment_email' => ['nullable', 'email', 'max:255'],
            'establishment_website' => ['nullable', 'url', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_no' => ['nullable', 'string', 'regex:/^[0-9]{9,18}$/'],
            'bank_ifsc' => ['nullable', 'string', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
        ], [
            'pf_establishment_code.regex' => 'The PF code can contain only letters and digits, like ORBBS2012397000.',
            'bank_account_no.regex' => 'The account number must be 9 to 18 digits.',
            'bank_ifsc.regex' => 'The IFSC code must be 11 characters, like UBIN0572586.',
        ]);

        if (! empty($data['pf_establishment_code'])) {
            $data['pf_establishment_code'] = strtoupper($data['pf_establishment_code']);
        }

        if (! empty($data['bank_ifsc'])) {
            $data['bank_ifsc'] = strtoupper($data['bank_ifsc']);
        }

        $cityAndPin = collect([
            $data['establishment_city'] ?? null,
            $data['establishment_pin'] ?? null,
        ])->filter()->implode(' - ');

        $data['address'] = collect([
            $data['establishment_address1'] ?? null,
            $data['establishment_address2'] ?? null,
            $cityAndPin,
            $data['establishment_district'] ?? null,
            config('magnova.state'),
        ])->filter()->implode(', ');
        $data['contact_phone'] = $data['establishment_mobile'] ?? null;
        $data['contact_email'] = $data['establishment_email'] ?? null;

        $request->user()->tenant->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Establishment details updated.']);

        return to_route('establishment.edit');
    }
}
