<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Issues the tax invoice of a contract-labour client from its saved Register of Wages. The category totals come from
 * the saved wage lines; the printed content is stored with the invoice so it can never drift from what was issued.
 */
class ContractInvoiceService
{
    public function __construct(
        private readonly ContractWageSheet $sheet,
        private readonly ContractLabourInvoiceBuilder $builder,
    ) {}

    /** Why an invoice cannot be issued for the month yet, or null when it can. */
    public function blocker(Client $client, int $month, int $year): ?string
    {
        $rows = $this->sheet->rows($client, $month, $year)['rows'];

        if ($rows === []) {
            return 'This unit has no active employees.';
        }
        if (collect($rows)->contains(fn ($row) => ! $row['saved'])) {
            return 'Save the wages first: press "Calculate & Save Wages" so every employee has a saved line.';
        }
        if (collect($rows)->contains(fn ($row) => $row['stale'])) {
            return 'Attendance or rates changed after the wages were saved. Press "Calculate & Save Wages" again first.';
        }

        return null;
    }

    public function generate(Client $client, int $month, int $year, ?string $typedNumber, string $prefix): Invoice
    {
        $existing = Invoice::where('client_id', $client->id)->where('month', $month)->where('year', $year)->first();

        if ($existing && $existing->status !== 'draft') {
            throw ValidationException::withMessages(['month' => "Invoice {$existing->invoice_no} is already {$existing->status} and cannot be regenerated."]);
        }

        if ($blocker = $this->blocker($client, $month, $year)) {
            throw ValidationException::withMessages(['month' => $blocker]);
        }

        $invoiceNo = $existing?->invoice_no;
        if (! $existing) {
            $number = trim((string) $typedNumber);
            if ($number === '') {
                throw ValidationException::withMessages(['invoice_number' => 'Enter the invoice number.']);
            }
            $invoiceNo = $prefix.$number;
            if (Invoice::withoutGlobalScopes()->where('invoice_no', $invoiceNo)->exists()) {
                throw ValidationException::withMessages(['invoice_number' => "Invoice number {$invoiceNo} is already used."]);
            }
        }

        $rows = collect($this->sheet->rows($client, $month, $year)['rows']);
        $categoryLines = $rows->groupBy('category')->map(fn ($group, $category) => [
            'category' => $category,
            'persons' => $group->count(),
            'mandays' => $group->sum('paid_days'),
            'amount' => $group->sum(fn ($row) => $row['figures']['gross_amount']),
        ])->values()->all();

        $invoiceDate = $existing?->invoice_date ? Carbon::parse($existing->invoice_date) : now();
        $data = $this->builder->build(Tenant::findOrFail($client->tenant_id), $client, $month, $year, $invoiceNo, $invoiceDate, $categoryLines);

        $sum = fn (string $key) => round($rows->sum(fn ($row) => $row['figures'][$key] ?? 0), 2);

        return DB::transaction(fn () => Invoice::updateOrCreate(
            ['client_id' => $client->id, 'month' => $month, 'year' => $year],
            [
                'tenant_id' => $client->tenant_id,
                'invoice_no' => $invoiceNo,
                'ref_no' => $data['ref_no'],
                'invoice_date' => $existing->invoice_date ?? $invoiceDate->toDateString(),
                'due_date' => $existing->due_date ?? $invoiceDate->copy()->addDays(30)->toDateString(),
                'rate_per_day' => 0,
                'no_of_persons' => $rows->count(),
                'no_of_duties' => $rows->sum('paid_days'),
                'base_amount' => $sum('earn_wages'),
                'epf_employer' => $sum('pf_employer'),
                'epf_admin' => 0,
                'edli_cont' => 0,
                'esi_employer' => $sum('esic_employer'),
                'taxable_value' => $data['taxable_value'],
                'service_charge' => $sum('service_charge'),
                'cgst' => $data['cgst'],
                'sgst' => $data['sgst'],
                'igst' => $data['igst'],
                'grand_total' => $data['total'],
                'document' => $data,
            ],
        ));
    }
}
