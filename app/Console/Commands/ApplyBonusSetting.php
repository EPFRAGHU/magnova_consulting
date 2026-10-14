<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Wage;
use App\Services\InvoiceCalculator;
use App\Services\WageRegisterCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ApplyBonusSetting extends Command
{
    protected $signature = 'wages:apply-bonus-setting {--from= : First month to recalculate (YYYY-MM); defaults to the current register rules start}';

    protected $description = "Re-apply each unit's bonus on/off setting to saved wages and draft invoices";

    public function handle(WageRegisterCalculator $register, InvoiceCalculator $invoices): int
    {
        $from = Carbon::parse(($this->option('from') ?: Carbon::parse(config('magnova.register_rules_from'))->format('Y-m')).'-01');
        $fromKey = $from->year * 12 + $from->month;

        $wagesChanged = 0;
        Wage::query()
            ->with('employee.client')
            ->whereRaw('(year * 12 + month) >= ?', [$fromKey])
            ->get()
            ->each(function (Wage $wage) use ($register, &$wagesChanged) {
                if (! $wage->employee) {
                    return;
                }

                $figures = $register->figures(
                    (float) $wage->gross_wages,
                    $wage->month,
                    $wage->year,
                    (bool) $wage->employee->client?->bonus_enabled,
                );

                if ((float) $wage->bonus !== (float) $figures['bonus']
                    || (float) $wage->net_salary !== (float) $figures['net_payable_salary']) {
                    $wage->update([
                        'bonus' => $figures['bonus'],
                        'net_salary' => $figures['net_payable_salary'],
                    ]);
                    $wagesChanged++;
                }
            });

        $regenerated = 0;
        $skipped = 0;
        Invoice::query()
            ->with('client')
            ->whereRaw('(year * 12 + month) >= ?', [$fromKey])
            ->get()
            ->each(function (Invoice $invoice) use ($invoices, &$regenerated, &$skipped) {
                if (! $invoice->client) {
                    return;
                }

                if ($invoice->status !== 'draft') {
                    $skipped++;

                    return;
                }

                $invoices->generateForClient($invoice->client, $invoice->month, $invoice->year, $invoice->invoice_no);
                $regenerated++;
            });

        $this->info("Wages updated: {$wagesChanged}. Draft invoices regenerated: {$regenerated}. Sent/paid invoices left untouched: {$skipped}.");

        return self::SUCCESS;
    }
}
