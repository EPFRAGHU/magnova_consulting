<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ContractLabourInvoiceBuilder;
use App\Services\PdfExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContractLabourInvoiceDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function data(string $buyerGstin = '21AABCB0984E1Z7'): array
    {
        $tenant = Tenant::create([
            'name' => 'Magnova Consulting Pvt. Ltd.', 'slug' => 'magnova-doc', 'gstin' => '21AAFCM1234B1Z5', 'pan' => 'AAFCM1234B',
            'address' => 'Plot 481/770, Balianta, Bhubaneswar', 'contact_email' => 'admin@magnovahr.in', 'bank_name' => 'Test Bank',
            'bank_account_no' => '111122223333', 'bank_ifsc' => 'TEST0000001', 'bank_branch' => 'Bhubaneswar', 'contact_phone' => '7008960018',
        ]);
        $client = Client::create([
            'tenant_id' => $tenant->id, 'name' => 'Balmer Lawrie - Jatni', 'active' => true, 'payroll_profile' => 'contract_labour',
            'principal_employer_name' => 'Balmer Lawrie and Company Limited', 'principal_employer_address' => 'Jatni, Khordha, Odisha',
            'principal_employer_gstin' => $buyerGstin, 'buyer_order_no' => 'PO-77', 'buyer_order_date' => '2026-08-20', 'gemc_no' => 'GEMC-511687723492093',
        ]);

        return app(ContractLabourInvoiceBuilder::class)->build($tenant, $client, 9, 2026, 'MCPL/26-27/9', Carbon::parse('2026-10-05'), [
            ['category' => 'FLO - Skilled', 'persons' => 3, 'mandays' => 75.5, 'amount' => 93525.16],
            ['category' => 'Picker - SS', 'persons' => 4, 'mandays' => 93.5, 'amount' => 87031.80],
            ['category' => 'Loader - UnS', 'persons' => 17, 'mandays' => 415, 'amount' => 330265.20],
        ]);
    }

    public function test_the_invoice_page_carries_every_part_of_the_bill_design(): void
    {
        $html = view('pdf.contract-labour-invoice', ['data' => $this->data(), 'logoDataUri' => null])->render();

        foreach ([
            'TAX INVOICE', 'Ref no: GEMC-511687723492093', 'Invoice No: MCPL/26-27/9', 'M/s MAGNOVA CONSULTING PVT. LTD.',
            'Security and Manpower Service Provider', 'GSTIN NO: 21AAFCM1234B1Z5', 'Bill for the month of September-2026', 'Date: 05-10-2026',
            'Details of Receiver (Billed To)', 'Details of Parties (Billed From)', 'M/S Balmer Lawrie and Company Limited', '21AABCB0984E1Z7',
            'PAN No.', 'Contact No.', '7008960018', 'Billing Period', '01-09-2026 to 30-09-2026',
            'Summary of Duties Performed in the Month of September 2026', 'Particulars (Rank)', 'SAC Code', 'Total Amount', 'Handling Manpower Contract',
            'SUB-TOTAL (Taxable Value)', 'GST @ 18.00%', 'GRAND TOTAL', 'Amount in words: <b>Six Lakh Two Thousand Seven Hundred Seventy only.</b>',
            'Bank Details', 'Terms and Conditions', 'For M/s Magnova Consulting Pvt. Ltd.', 'Authorized Signature', 'ANNEXURE TO TAX INVOICE',
        ] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        $this->assertStringNotContainsString('IGST', $html, 'same state: one GST line');
        $this->assertStringNotContainsString('Round Off', $html, 'the bill shows whole rupees, no round-off line');
    }

    public function test_the_bill_shows_whole_rupees_that_add_up_to_the_grand_total(): void
    {
        $data = $this->data();
        $html = view('pdf.contract-labour-invoice', ['data' => $data, 'logoDataUri' => null])->render();

        $this->assertSame(510822.0, $data['taxable_shown']);
        $this->assertSame(91948.0, $data['tax_shown']);
        $this->assertEquals($data['total'], $data['taxable_shown'] + $data['tax_shown']);
        $this->assertStringContainsString('510,822.00', $html);
        $this->assertStringContainsString('91,948.00', $html);
        $this->assertStringContainsString('602,770.00', $html);
        $this->assertSame(510822.16, $data['taxable_value'], 'the stored exact figures are unchanged');
    }

    public function test_a_buyer_in_another_state_gets_one_igst_line(): void
    {
        $html = view('pdf.contract-labour-invoice', ['data' => $this->data('36AABCB0984E1Z7'), 'logoDataUri' => null])->render();

        $this->assertStringContainsString('IGST @ 18.00%', $html);
        $this->assertStringNotContainsString('>GST @ 18.00%', $html, 'a different state gets IGST, not GST');
    }

    public function test_an_invoice_stored_before_this_design_still_prints(): void
    {
        $old = $this->data();
        foreach (['ref_no', 'summary_particulars', 'taxable_shown', 'tax_shown', 'invoice_date_iso', 'period_start'] as $key) {
            unset($old[$key]);
        }
        unset($old['supplier']['phone']);

        $html = view('pdf.contract-labour-invoice', ['data' => $old, 'logoDataUri' => null])->render();

        foreach (['Date: 05-10-2026', '01-09-2026 to 30-09-2026', 'Handling Manpower Contract', '510,822.00', '91,948.00', '602,770.00'] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
    }

    public function test_the_pdf_is_the_invoice_page_plus_one_annexure_page(): void
    {
        $pdf = app(PdfExportService::class)->contractLabourInvoice($this->data())->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(2, preg_match_all('#/Type\s*/Page\b#', $pdf));
    }

    public function test_the_unit_form_saves_the_buyers_order_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'BL', 'active' => 1, 'payroll_profile' => 'contract_labour', 'principal_employer_name' => 'Balmer Lawrie',
            'buyer_order_no' => 'PO-9', 'buyer_order_date' => '2026-08-20',
        ])->assertSessionHasNoErrors();

        $client = Client::first();
        $this->assertSame('PO-9', $client->buyer_order_no);
        $this->assertSame('2026-08-20', $client->buyer_order_date->toDateString());
    }
}
