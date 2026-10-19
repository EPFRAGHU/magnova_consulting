<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Assembles everything the contract-labour tax invoice prints (the Tally-style layout with Consignee, Buyer,
 * an HSN/SAC tax summary and a round-off line) from the establishment, the client and the category totals.
 * It computes nothing about wages: the category amounts come from the wage sheet.
 */
class ContractLabourInvoiceBuilder
{
    public function __construct(private readonly NumberToWordsService $words) {}

    /**
     * @param  array<int, array{category: string, persons: int, mandays: float, amount: float}>  $categoryLines
     * @return array<string, mixed>
     */
    public function build(Tenant $tenant, Client $client, int $month, int $year, string $invoiceNo, CarbonInterface $invoiceDate, array $categoryLines): array
    {
        $supplier = $this->party(
            $this->plainName((string) $tenant->name),
            $tenant->address,
            $tenant->gstin,
            config('magnova.state'),
        ) + ['email' => $tenant->contact_email, 'pan' => $tenant->pan];

        $consignee = $this->party($client->principal_employer_name ?: $client->name, $client->principal_employer_address ?: $client->address, $client->principal_employer_gstin ?: $client->gstin);
        $buyer = $client->billing_name
            ? $this->party($client->billing_name, $client->billing_address ?: $client->address, $client->principal_employer_gstin ?: $client->gstin)
            : $consignee;

        $hsn = $client->sac_code ?: config('magnova.hsn_sac_code');
        $lines = [];
        foreach (array_values($categoryLines) as $i => $line) {
            $lines[] = [
                'sl' => $i + 1,
                'particulars' => $line['category'],
                'note' => 'AS PER ATTACHMENT',
                'hsn' => $hsn,
                'persons' => (int) $line['persons'],
                'mandays' => (float) $line['mandays'],
                'amount' => round((float) $line['amount'], 2),
            ];
        }

        $taxable = round(array_sum(array_column($lines, 'amount')), 2);
        $rate = (float) config('magnova.contract_labour.gst_pct');
        $taxTotal = round($taxable * $rate / 100, 2);

        // Different states (read from the GSTINs) mean IGST; same state, or a missing GSTIN, means CGST + SGST.
        $inter = $buyer['state_code'] !== '' && $supplier['state_code'] !== '' && $buyer['state_code'] !== $supplier['state_code'];
        $cgst = $inter ? 0.0 : round($taxTotal / 2, 2);
        $sgst = $inter ? 0.0 : round($taxTotal - $cgst, 2);
        $igst = $inter ? $taxTotal : 0.0;

        $exact = $taxable + $taxTotal;
        $total = (int) round($exact);

        $periodStart = Carbon::create($year, $month, 1);

        return [
            'supplier' => $supplier,
            'consignee' => $consignee,
            'buyer' => $buyer,
            'invoice_no' => $invoiceNo,
            'invoice_date' => $invoiceDate->format('d-M-y'),
            'buyer_order_no' => $client->buyer_order_no,
            'buyer_order_date' => $client->buyer_order_date?->format('d-M-y'),
            'terms_of_delivery' => 'Total Mandays from '.$periodStart->format('jS F, Y').' to '.$periodStart->copy()->endOfMonth()->format('jS F, Y').'.',
            'period_label' => $periodStart->format('F Y'),
            'lines' => $lines,
            'total_persons' => array_sum(array_column($lines, 'persons')),
            'total_mandays' => array_sum(array_column($lines, 'mandays')),
            'hsn' => $hsn,
            'taxable_value' => $taxable,
            'tax_mode' => $inter ? 'inter' : 'intra',
            'tax_rate' => $rate,
            'half_rate' => $rate / 2,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'tax_total' => $taxTotal,
            'round_off' => round($total - $exact, 2),
            'total' => (float) $total,
            'total_words' => $this->words->amountToWords($total),
            'tax_words' => $this->words->amountToWordsWithPaise($taxTotal),
            'bank' => [
                'holder' => $this->plainName((string) $tenant->name) ? 'M/s '.$this->plainName((string) $tenant->name) : null,
                'bank' => $tenant->bank_name,
                'account' => $tenant->bank_account_no,
                'ifsc' => $tenant->bank_ifsc,
                'branch' => $tenant->bank_branch,
            ],
            'declaration' => $tenant->terms_and_conditions ?: config('magnova.default_terms_and_conditions'),
            'jurisdiction' => config('magnova.jurisdiction'),
        ];
    }

    /** @return array{name: string, address: ?string, gstin: ?string, state_code: string, state_name: string} */
    private function party(string $name, ?string $address, ?string $gstin, ?string $fallbackState = null): array
    {
        $code = preg_match('/^\d{2}/', (string) $gstin, $m) ? $m[0] : '';
        $states = config('gst_states');

        if ($code === '' && $fallbackState !== null) {
            $code = (string) (array_search($fallbackState, $states, true) ?: '');
        }

        return [
            'name' => $name,
            'address' => $address,
            'gstin' => $gstin,
            'state_code' => $code,
            'state_name' => $states[$code] ?? '',
        ];
    }

    private function plainName(string $name): string
    {
        return trim(preg_replace('~^\s*m/s\.?\s*~i', '', $name));
    }
}
