<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $month = (int) now()->format('n');
        $year = (int) now()->format('Y');

        $clients = Client::query()
            ->where('active', true)
            ->withCount('employees')
            ->with(['invoices' => fn ($q) => $q->where('month', $month)->where('year', $year)])
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'unit_label' => $client->unit_label,
                'employees_count' => $client->employees_count,
                'status' => $client->invoices->first()?->status ?? 'pending',
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'active_clients' => Client::where('active', true)->count(),
                'employees' => Employee::where('status', 'active')->count(),
                'invoices_due' => Invoice::where('month', $month)->where('year', $year)->where('status', '!=', 'paid')->count(),
                'total_billed_this_month' => (float) Invoice::where('month', $month)->where('year', $year)->sum('grand_total'),
            ],
            'clients' => $clients,
            'month' => $month,
            'year' => $year,
        ]);
    }
}
