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
            'bank_account_no' => '111122223333', 'bank_ifsc' => 'TEST0000001', 'bank_branch' => 'Bhubaneswar',
        ]);
        $client = Client::create([
            'tenant_id' => $tenant->id, 'name' => 'Balmer Lawrie - Jatni', 'active' => true, 'payroll_profile' => 'contract_labour',
            'principal_employer_name' => 'Balmer Lawrie and Company Limited', 'principal_employer_address' => 'Jatni, Khordha, Odisha',
            'principal_employer_gstin' => $buyerGstin, 'buyer_order_no' => 'PO-77', 'buyer_order_date' => '2026-08-20',
        ]);

        return app(ContractLabourInvoiceBuilder::class)->build($tenant, $client, 9, 2026, 'MCPL/26-27/9', Carbon::parse('2026-10-05'), [
            ['category' => 'FLO - Skilled', 'persons' => 3, 'mandays' => 75.5, 'amount' => 93525.16],
            ['category' => 'Picker - SS', 'persons' => 4, 'mandays' => 93.5, 'amount' => 87031.80],
            ['category' => 'Loader - UnS', 'persons' => 17, 'mandays' => 415, 'amount' => 330265.20],
        ]);
    }

    public function test_the_invoice_page_carries_every_part_of_the_layout(): void
    {
        $html = view('pdf.contract-labour-invoice', ['data' => $this->data(), 'logoDataUri' => null])->render();

        foreach ([
            'Tax Invoice', '(ORIGINAL FOR RECIPIENT)', 'Consignee (Ship to)', 'Buyer (Bill to)', "Buyer's Order No.", 'PO-77',
            'Terms of Delivery', 'Total Mandays from 1st September, 2026 to 30th September, 2026.', 'AS PER ATTACHMENT',
            'CGST 9%', 'SGST 9%', 'Round Off', 'Amount Chargeable (in words)', 'Tax Amount (in words)', "Company's PAN",
            'Declaration', "Company's Bank Details", "Customer's Seal and Signature", 'Authorised Signatory',
            'SUBJECT TO BHUBANESWAR JURISDICTION', 'This is a Computer Generated Invoice', 'ANNEXURE TO TAX INVOICE',
        ] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        $this->assertStringNotContainsString('IGST', $html, 'same state: no IGST');
    }

    public function test_a_buyer_in_another_state_gets_igst(): void
    {
        $html = view('pdf.contract-labour-invoice', ['data' => $this->data('36AABCB0984E1Z7'), 'logoDataUri' => null])->render();

        $this->assertStringContainsString('IGST 18%', $html);
        $this->assertStringNotContainsString('CGST 9%', $html);
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
