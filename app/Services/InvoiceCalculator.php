<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Wage;
use Illuminate\Support\Facades\DB;

class InvoiceCalculator
{
    public function __construct(private WageRegisterCalculator $register) {}

    public function generateForClient(Client $client, int $month, int $year, string $invoiceNo): Invoice
    {
        $wages = Wage::query()
            ->whereHas('employee', fn ($q) => $q->where('client_id', $client->id))
            ->where('month', $month)
            ->where('year', $year)
            ->with('employee')
            ->get()
            ->sortBy(fn (Wage $wage) => $wage->employee->skillSortKey())
            ->values();

        $noOfDuties = (float) $wages->sum('working_days');
        $noOfPersons = $wages->count();
        $ratePerDay = (float) ($wages->first()?->rate_per_day ?? 0);

        // base_amount is the sum of each employee's own gross wages, not a
        // uniform rate x duties figure — employees are billed at different
        // rates (skilled vs unskilled), so those can diverge.
        $baseAmount = round((float) $wages->sum('gross_wages'), 2);

        // These are calculated per employee, as in the workbook's wage rows,
        // so the statutory caps are applied before the values are totalled.
        // The invoice workbook uses EPF at 12% (not the 13% wage-register
        // employer setting) and includes bonus in the taxable subtotal.
        $epfEmployer = round($wages->sum(
            fn (Wage $wage) => min((float) $wage->gross_wages, 15000)
                * config('magnova.epf_employee_pct') / 100,
        ), 2);
        $epfAdmin = round($wages->sum(
            fn (Wage $wage) => min((float) $wage->gross_wages, 15000)
                * config('magnova.epf_admin_pct') / 100,
        ), 2);
        $edliCont = round($wages->sum(
            fn (Wage $wage) => min((float) $wage->gross_wages, 15000)
                * config('magnova.edli_cont_pct') / 100,
        ), 2);
        $esiEmployer = round($wages->sum(
            fn (Wage $wage) => min((float) $wage->gross_wages, 21000)
                * config('magnova.esi_employer_pct') / 100,
        ), 2);
        $bonus = round((float) $wages->sum('bonus'), 2);
        $taxableValue = round(
            $baseAmount + $epfEmployer + $epfAdmin + $edliCont + $esiEmployer + $bonus,
            2,
        );
        $cgst = round($taxableValue * config('magnova.cgst_pct') / 100, 2);
        $sgst = round($taxableValue * config('magnova.sgst_pct') / 100, 2);
        $serviceCharge = round($taxableValue * config('magnova.service_charge_pct') / 100, 2);
        $grandTotal = round($taxableValue + $serviceCharge + $cgst + $sgst, 2);

        // From the current register rules the invoice is the sum of the Register
        // of Wages rows, so both documents always show the same figures.
        if ($this->register->usesCurrentRules($month, $year)) {
            $figures = $wages->map(
                fn (Wage $wage) => $this->register->figures(
                    (float) $wage->gross_wages,
                    $month,
                    $year,
                    (bool) $client->bonus_enabled,
                ),
            );
            $edliAndAdmin = round($figures->sum('edli_and_epf_admin'), 2);
            $epfAdmin = round($edliAndAdmin / 2, 2);
            $edliCont = round($edliAndAdmin - $epfAdmin, 2);
            $epfEmployer = round($figures->sum('epf_employer'), 2);
            $esiEmployer = round($figures->sum('esic_employer'), 2);
            $taxableValue = round($figures->sum('total_gross_salary'), 2);
            $gst = round($figures->sum('gst'), 2);
            $cgst = round($gst / 2, 2);
            $sgst = round($gst - $cgst, 2);
            $serviceCharge = round($figures->sum('agency_fee'), 2);
            $grandTotal = round($figures->sum('total_bill_amount'), 2);
        }

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
