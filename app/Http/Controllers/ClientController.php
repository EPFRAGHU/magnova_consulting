<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\InvoiceRefresher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $clients = Client::query()
            ->withCount('employees')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'search' => $search,
            'regenerate' => session('regenerate'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('clients/form');
    }

    public function store(Request $request): RedirectResponse
    {
        Client::create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Client created.']);

        return to_route('clients.index');
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('clients/form', [
            'client' => $client,
        ]);
    }

    public function update(Request $request, Client $client, InvoiceRefresher $refresher): RedirectResponse
    {
        $client->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => $client->wasChanged('esic_on_ceiling')
            ? 'Client updated. The ESIC setting applies to wages saved from now on; save the wages again for any month you want recalculated.'
            : 'Client updated.']);

        $redirect = to_route('clients.index');
        $settingsChanged = $client->wasChanged(['bonus_enabled', 'service_charge_on_basic', 'gst_exempt']);
        $drafts = $settingsChanged ? $refresher->draftInvoiceCount($client) : 0;

        // Ask before recalculating: the page shows a Yes/No question for this unit's draft invoices.
        return $drafts > 0
            ? $redirect->with('regenerate', ['client_id' => $client->id, 'name' => $client->name, 'count' => $drafts])
            : $redirect;
    }

    public function regenerateInvoices(Client $client, InvoiceRefresher $refresher): RedirectResponse
    {
        $count = $refresher->refreshDrafts($client);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $count === 1 ? '1 draft invoice regenerated.' : "{$count} draft invoices regenerated.",
        ]);

        return to_route('clients.index');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Client archived.']);

        return to_route('clients.index');
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'active' => $request->boolean('active'),
            'bonus_enabled' => $request->boolean('bonus_enabled'),
            'service_charge_on_basic' => $request->boolean('service_charge_on_basic'),
            'gst_exempt' => $request->boolean('gst_exempt'),
            'esic_on_ceiling' => $request->boolean('esic_on_ceiling'),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit_label' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'sac_code' => ['nullable', 'string', 'max:20'],
            'gemc_no' => ['nullable', 'string', 'max:255'],
            'billing_name' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'active' => ['boolean'],
            'bonus_enabled' => ['boolean'],
            'service_charge_on_basic' => ['boolean'],
            'gst_exempt' => ['boolean'],
            'esic_on_ceiling' => ['boolean'],
        ]);
    }
}
