<?php

namespace App\Http\Controllers;

use App\Models\Client;
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

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Client updated.']);

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
        $request->merge(['active' => $request->boolean('active')]);

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
        ]);
    }
}
