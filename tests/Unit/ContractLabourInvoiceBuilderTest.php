<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Tenant;
use App\Services\ContractLabourCalculator;
use App\Services\ContractLabourInvoiceBuilder;
use App\Services\NumberToWordsService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContractLabourInvoiceBuilderTest extends TestCase
{
    private function tenant(): Tenant
    {
        return Tenant::make([
            'name' => 'Magnova Consulting Pvt. Ltd.', 'gstin' => '21AAFCM1234B1Z5', 'pan' => 'AAFCM1234B', 'address' => 'Plot 481/770, Balianta, Bhubaneswar - 752100',
            'contact_email' => 'admin@magnovahr.in', 'bank_name' => 'Union Bank of India', 'bank_account_no' => '123456789012',
            'bank_ifsc' => 'UBIN0572586', 'bank_branch' => 'Bhubaneswar',
        ]);
    }

    private function client(array $overrides = []): Client
    {
        return Client::make(array_merge([
            'name' => 'Balmer Lawrie - Jatni', 'payroll_profile' => 'contract_labour', 'principal_employer_name' => 'Balmer Lawrie and Company Limited',
            'principal_employer_address' => 'Plot No 5 and 13, Chhatabara, Khordha, Odisha - 752054', 'principal_employer_gstin' => '21AABCB0984E1Z7',
        ], $overrides));
    }

    /** Category lines from the client's real September 2026 sheet: gross amount (cost plus service charge) per employee. */
    private function septemberLines(): array
    {
        $sheet = json_decode(file_get_contents(base_path('tests/Fixtures/contract_labour_sep_2026.json')), true);
        $calc = new ContractLabourCalculator;
        $lines = [];

        foreach ($sheet['rows'] as $row) {
            $figures = $calc->calculate($row['rate'], $row['paid_days'], $row['ot_hours'], 9, 2026);
            $key = trim(preg_replace('/\s*-\s*/', ' - ', $row['category']));
            $lines[$key] ??= ['category' => $key, 'persons' => 0, 'mandays' => 0.0, 'amount' => 0.0];
            $lines[$key]['persons']++;
            $lines[$key]['mandays'] += $row['paid_days'];
            $lines[$key]['amount'] += $figures['gross_amount'];
        }

        return array_values($lines);
    }

    private function build(?Client $client = null, ?Tenant $tenant = null): array
    {
        return (new ContractLabourInvoiceBuilder(new NumberToWordsService))->build(
            $tenant ?? $this->tenant(), $client ?? $this->client(), 9, 2026, 'MCPL/26-27/12', Carbon::parse('2026-10-05'), $this->septemberLines(),
        );
    }

    public function test_one_line_per_category_with_persons_mandays_and_the_amount(): void
    {
        $data = $this->build();

        $this->assertSame(['FLO - Skilled', 'Picker - SS', 'Loader - UnS'], array_column($data['lines'], 'particulars'));
        $this->assertSame([3, 4, 17], array_column($data['lines'], 'persons'));
        $this->assertEqualsWithDelta(75.5, $data['lines'][0]['mandays'], 0.0001);
        $this->assertSame('AS PER ATTACHMENT', $data['lines'][0]['note']);
        $this->assertSame(range(1, 3), array_column($data['lines'], 'sl'));
        $this->assertSame('998525', $data['lines'][0]['hsn']);
    }

    public function test_the_taxable_value_is_the_sum_of_the_rounded_lines_and_tax_is_18_percent_of_it(): void
    {
        $data = $this->build();

        $this->assertEqualsWithDelta(array_sum(array_column($data['lines'], 'amount')), $data['taxable_value'], 0.0001);
        foreach ($data['lines'] as $line) {
            $this->assertEquals(round($line['amount'], 2), $line['amount'], 'line amounts are whole paise');
        }
        $this->assertEqualsWithDelta(round($data['taxable_value'] * 0.18, 2), $data['tax_total'], 0.0001);
        $this->assertEqualsWithDelta($data['tax_total'], $data['cgst'] + $data['sgst'], 0.0001);
        $this->assertSame(0.0, $data['igst']);
        $this->assertSame('intra', $data['tax_mode']);
    }

    public function test_the_total_is_exact_to_the_paisa_with_no_round_off(): void
    {
        $data = $this->build();
        $exact = $data['taxable_value'] + $data['tax_total'];

        $this->assertEqualsWithDelta($exact, $data['total'], 0.0001, 'nothing is rounded to the rupee');
        $this->assertEquals(round($data['total'], 2), $data['total'], 'whole paise');
        $this->assertSame(0.0, $data['round_off']);
        $this->assertSame($data['taxable_value'], $data['taxable_shown']);
        $this->assertSame($data['tax_total'], $data['tax_shown']);
        $this->assertSame((new NumberToWordsService)->amountToWordsWithPaise($data['total']), $data['total_words']);
        $this->assertSame((new NumberToWordsService)->amountToWordsWithPaise($data['tax_total']), $data['tax_words']);
    }

    public function test_different_states_give_igst_instead_of_cgst_and_sgst(): void
    {
        $data = $this->build($this->client(['principal_employer_gstin' => '36AABCB0984E1Z7']));

        $this->assertSame('inter', $data['tax_mode']);
        $this->assertEqualsWithDelta($data['tax_total'], $data['igst'], 0.0001);
        $this->assertSame(0.0, $data['cgst'] + $data['sgst']);
        $this->assertSame('Telangana', $data['buyer']['state_name']);
        $this->assertSame('36', $data['buyer']['state_code']);
    }

    public function test_party_blocks_state_names_and_header_fields(): void
    {
        $data = $this->build();

        $this->assertSame('Odisha', $data['supplier']['state_name']);
        $this->assertSame('21', $data['supplier']['state_code']);
        $this->assertSame('Balmer Lawrie and Company Limited', $data['consignee']['name']);
        $this->assertSame('21AABCB0984E1Z7', $data['buyer']['gstin']);
        $this->assertSame('Total Mandays from 1st September, 2026 to 30th September, 2026.', $data['terms_of_delivery']);
        $this->assertSame('MCPL/26-27/12', $data['invoice_no']);
        $this->assertSame('05-Oct-26', $data['invoice_date']);
        $this->assertSame('Union Bank of India', $data['bank']['bank']);
    }

    public function test_a_separate_billing_party_becomes_the_buyer_while_the_principal_employer_is_the_consignee(): void
    {
        $data = $this->build($this->client(['billing_name' => 'Balmer Lawrie Head Office', 'billing_address' => 'Kolkata']));

        $this->assertSame('Balmer Lawrie Head Office', $data['buyer']['name']);
        $this->assertSame('Balmer Lawrie and Company Limited', $data['consignee']['name']);
    }

    public function test_the_buyers_order_number_and_date_are_carried_through(): void
    {
        $data = $this->build($this->client(['buyer_order_no' => 'PO/2026/115', 'buyer_order_date' => '2026-08-20']));

        $this->assertSame('PO/2026/115', $data['buyer_order_no']);
        $this->assertSame('20-Aug-26', $data['buyer_order_date']);
    }

    public function test_the_paise_wording(): void
    {
        $words = new NumberToWordsService;

        $this->assertSame('Ten Thousand One Hundred Five and Ninety Two Paise', $words->amountToWordsWithPaise(10105.92));
        $this->assertSame('Sixty Six Thousand Two Hundred Fifty', $words->amountToWordsWithPaise(66250.00));
        $this->assertSame('One and Five Paise', $words->amountToWordsWithPaise(1.05));
    }
}
