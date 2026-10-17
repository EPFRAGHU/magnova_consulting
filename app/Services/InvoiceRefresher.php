<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Wage;

/** Recalculates a unit's draft invoices after one of its billing settings (bonus, service charge basis, GST) changed. */
class InvoiceRefresher
{
    public function __construct(
        private readonly WageRegisterCalculator $register,
        private readonly InvoiceCalculator $invoices,
    ) {}

    public function draftInvoiceCount(Client $client): int
    {
        return Invoice::query()->where('client_id', $client->id)->where('status', 'draft')->count();
    }

    /**
     * Brings the saved wages and the invoice of each draft month in line with the unit's current settings.
     * Sent and paid invoices are never touched. Returns how many invoices were regenerated.
     */
    public function refreshDrafts(Client $client): int
    {
        $refreshed = 0;

        Invoice::query()
            ->where('client_id', $client->id)
            ->where('status', 'draft')
            ->get()
            ->each(function (Invoice $invoice) use ($client, &$refreshed) {
                $wages = Wage::query()
                    ->whereHas('employee', fn ($query) => $query->where('client_id', $client->id))
                    ->where('month', $invoice->month)
                    ->where('year', $invoice->year)
                    ->get();

                if ($wages->isEmpty()) {
                    return;
                }

                $wages->each(function (Wage $wage) use ($client) {
                    $figures = $this->register->figures(
                        (float) $wage->gross_wages,
                        $wage->month,
                        $wage->year,
                        (bool) $client->bonus_enabled,
                        (bool) $client->service_charge_on_basic,
                        (bool) $client->gst_exempt,
                        $wage->employee,
                        $wage,
                    );

                    $wage->update(['bonus' => $figures['bonus'], 'net_salary' => $figures['net_payable_salary']]);
                });

                $this->invoices->generateForClient($client, $invoice->month, $invoice->year, $invoice->invoice_no);
                $refreshed++;
            });

        return $refreshed;
    }
}
