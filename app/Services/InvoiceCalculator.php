<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Wage;
use Illuminate\Support\Facades\DB;

class InvoiceCalculator
{
    public function generateForClient(Client $client, int $month, int $year, string $invoiceNo): Invoice
    {
        $wages = Wage::query()
            ->whereHas('employee', fn ($q) => $q->where('client_id', $client->id))
            ->where('month', $month)
            ->where('year', $year)
            ->with('employee')
            ->get();

        $noOfDuties = (float) $wages->sum('working_days');
        $noOfPersons = $wages->count();
        $ratePerDay = (float) ($wages->first()?->rate_per_day ?? 0);

        // base_amount is the sum of each employee's own gross wages, not a
        // uniform rate x duties figure — employees are billed at different
        // rates (skilled vs unskilled), so those can diverge.
        $baseAmount = round((float) $wages->sum('gross_wages'), 2);
        $epfEmployer = round($baseAmount * config('magnova.epf_employer_pct') / 100, 2);
        $epfAdmin = round($baseAmount * config('magnova.epf_admin_pct') / 100, 2);
        $edliCont = round($baseAmount * config('magnova.edli_cont_pct') / 100, 2);
        $esiEmployer = round($baseAmount * config('magnova.esi_employer_pct') / 100, 2);
        $serviceCharge = round($baseAmount * config('magnova.service_charge_pct') / 100, 2);
        $taxableValue = round($baseAmount + $epfEmployer + $epfAdmin + $edliCont + $esiEmployer + $serviceCharge, 2);
        $cgst = round($taxableValue * config('magnova.cgst_pct') / 100, 2);
        $sgst = round($taxableValue * config('magnova.sgst_pct') / 100, 2);
        // Verified against real Magnova invoices: the grand total is rounded
        // to the nearest whole rupee, even though every line item above it
        // keeps paise precision.
        $grandTotal = round($taxableValue + $cgst + $sgst);

        return DB::transaction(function () use ($client, $month, $year, $invoiceNo, $wages, $noOfPersons, $noOfDuties, $ratePerDay, $baseAmount, $epfEmployer, $epfAdmin, $edliCont, $esiEmployer, $taxableValue, $serviceCharge, $cgst, $sgst, $grandTotal) {
            $existing = Invoice::where('client_id', $client->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            $invoice = Invoice::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'tenant_id' => $client->tenant_id,
                    // Editable header fields (invoice_no, ref_no, due_date, place_of_supply,
                    // reverse_charge) are only defaulted on first creation — regenerating an
                    // invoice's figures from updated wages must not clobber edits made via
                    // InvoiceController::update() afterwards.
                    'invoice_no' => $existing->invoice_no ?? $invoiceNo,
                    'invoice_date' => $existing->invoice_date ?? now()->toDateString(),
                    'due_date' => $existing->due_date ?? now()->addDays(30)->toDateString(),
                    'rate_per_day' => $ratePerDay,
                    'no_of_persons' => $noOfPersons,
                    'no_of_duties' => $noOfDuties,
                    'base_amount' => $baseAmount,
                    'epf_employer' => $epfEmployer,
                    'epf_admin' => $epfAdmin,
                    'edli_cont' => $edliCont,
                    'esi_employer' => $esiEmployer,
                    'taxable_value' => $taxableValue,
                    'service_charge' => $serviceCharge,
                    'cgst' => $cgst,
                    'sgst' => $sgst,
                    'grand_total' => $grandTotal,
                ]
            );

            $invoice->lineItems()->delete();
            $invoice->lineItems()->createMany(
                $wages->map(fn (Wage $wage) => [
                    'employee_id' => $wage->employee_id,
                    'working_days' => $wage->working_days,
                    'rate_per_day' => $wage->rate_per_day,
                    'wage_amount' => $wage->gross_wages,
                ])->all()
            );

            return $invoice->fresh('lineItems');
        });
    }
}
