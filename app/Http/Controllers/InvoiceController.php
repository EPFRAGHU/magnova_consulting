<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Wage;
use App\Services\InvoiceCalculator;
use App\Services\PdfExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $clientId = $request->integer('client_id') ?: null;

        $invoices = Invoice::query()
            ->with('client')
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'unit_label']),
            'client_id' => $clientId,
            'invoice_prefix' => Invoice::numberPrefix(Auth::user()->tenant->name, now()),
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load('client', 'lineItems.employee');
        $invoice->setRelation(
            'lineItems',
            $invoice->lineItems->sortBy(fn ($item) => $item->employee->skillSortKey())->values(),
        );

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $request->merge(['reverse_charge' => $request->boolean('reverse_charge')]);

        $data = $request->validate([
            'invoice_no' => ['required', 'string', 'max:255', 'unique:invoices,invoice_no,'.$invoice->id],
            'ref_no' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'place_of_supply' => ['nullable', 'string', 'max:255'],
            'reverse_charge' => ['boolean'],
            'status' => ['required', 'in:draft,sent,paid'],
        ]);

        $invoice->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invoice details updated.']);

        return to_route('invoices.show', $invoice);
    }

    public function store(Request $request, InvoiceCalculator $calculator): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
            'invoice_number' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
        ], [
            'invoice_number.regex' => 'The invoice number can contain only letters, digits and hyphens.',
        ]);

        $client = Client::findOrFail($data['client_id']);

        $hasWages = Wage::whereHas('employee', fn ($q) => $q->where('client_id', $client->id))
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->exists();

        if (! $hasWages) {
            return back()->withErrors(['month' => 'No wage records found for this client and month. Save wages first.']);
        }

        $existingInvoice = Invoice::where('client_id', $client->id)
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->first();

        // A new invoice needs a number typed by the user; regenerating an existing
        // invoice keeps the number it already has.
        $invoiceNo = $existingInvoice?->invoice_no;

        if (! $existingInvoice) {
            $number = trim((string) ($data['invoice_number'] ?? ''));

            if ($number === '') {
                return back()->withErrors(['invoice_number' => 'Enter the invoice number.']);
            }

            $invoiceNo = Invoice::numberPrefix(Auth::user()->tenant->name, now()).$number;

            if (Invoice::withoutGlobalScopes()->where('invoice_no', $invoiceNo)->exists()) {
                return back()->withErrors(['invoice_number' => "Invoice number {$invoiceNo} is already used."]);
            }
        }

        $invoice = $calculator->generateForClient($client, $data['month'], $data['year'], $invoiceNo);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Invoice {$invoice->invoice_no} generated."]);

        return to_route('invoices.show', $invoice);
    }

    public function pdf(Invoice $invoice, PdfExportService $pdf): HttpResponse
    {
        $fileName = str_replace(['/', '\\'], '-', $invoice->invoice_no) . '.pdf';

        return $pdf->invoice($invoice)->download($fileName);
    }
}
