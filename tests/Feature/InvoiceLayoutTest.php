<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function render(string $tenantName = 'Magnova Consulting Pvt. Ltd.', bool $serviceOnBasic = false): string
    {
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => 'layout-'.uniqid(),
            'address' => 'Plot No. 481/770, P.O. Balianta, Dandilo, Bhubaneswar - 752100, Khordha, Odisha',
            'contact_phone' => '7008960018',
            'gstin' => '21AAFCM1234B1Z5',
            'pan' => 'AAFCM1234B',
        ]);
        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kendriya Vidyalaya',
            'active' => true,
            'sac_code' => '998519',
            'contact_phone' => '9999999999',
            'service_charge_on_basic' => $serviceOnBasic,
        ]);
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'invoice_no' => 'MCPL/26-27/'.uniqid(),
            'invoice_date' => '2026-10-14',
            'month' => 9,
            'year' => 2026,
            'rate_per_day' => 781,
            'no_of_persons' => 4,
            'no_of_duties' => 90,
            'base_amount' => 70290,
            'epf_employer' => 8435,
            'epf_admin' => 351,
            'edli_cont' => 351,
            'esi_employer' => 0,
            'taxable_value' => 79427,
            'service_charge' => 3058,
            'cgst' => 7148.5,
            'sgst' => 7148.5,
            'grand_total' => 96782,
            'status' => 'draft',
        ]);

        return view('pdf.invoice', [
            'invoice' => $invoice->load('client', 'lineItems.employee', 'tenant'),
            'dutySummary' => collect([['rank' => 'Security Guard Male', 'rate' => 781, 'persons' => 4, 'duties' => 90, 'amount' => 70290]]),
            'bonus' => 0,
            'amountInWords' => 'Ninety Six Thousand Seven Hundred Eighty Two',
            'logoDataUri' => null,
        ])->render();
    }

    public function test_company_name_has_ms_prefix_in_header_billed_from_and_signature(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('M/s MAGNOVA CONSULTING PVT. LTD.', $html);
        $this->assertStringContainsString('M/s Magnova Consulting Pvt. Ltd.', $html);
        $this->assertStringContainsString('For M/s Magnova Consulting Pvt. Ltd.', $html);
    }

    public function test_ms_prefix_is_not_doubled_when_the_name_already_has_it(): void
    {
        $html = $this->render('M/s Magnova Consulting Pvt. Ltd.');

        $this->assertStringNotContainsString('M/s M/s', $html);
        $this->assertStringContainsString('M/s MAGNOVA CONSULTING PVT. LTD.', $html);
    }

    public function test_billed_to_has_no_sac_code_or_contact_number(): void
    {
        $html = $this->render();
        $billedTo = explode('Details of Parties (Billed From)', explode('Details of Receiver (Billed To)', $html)[1])[0];

        $this->assertStringNotContainsString('SAC Code', $billedTo);
        $this->assertStringNotContainsString('Contact No.', $billedTo);
        $this->assertStringNotContainsString('9999999999', $html);
    }

    public function test_sac_code_column_follows_particulars_with_the_company_code(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/Particulars \(Rank\)<\/th>\s*<th class="col-sac">SAC Code<\/th>/', $html);
        $this->assertMatchesRegularExpression('/Security Guard Male<\/td>\s*<td class="center">998525<\/td>/', $html);
        $this->assertStringNotContainsString('998519', $html);
    }

    public function test_subtotal_block_shows_cgst_and_sgst_instead_of_gst(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('CGST @ 9.00%', $html);
        $this->assertStringContainsString('SGST @ 9.00%', $html);
        $this->assertStringContainsString('7,148.50', $html);
        $this->assertStringNotContainsString('GST @ 18.00%', $html);
    }

    public function test_service_charge_line_states_its_basis(): void
    {
        $this->assertStringContainsString('Service Charges @ 3.85% on Gross Wages', $this->render());
        $this->assertStringContainsString('Service Charges @ 3.85% on Basic Wages', $this->render('Magnova Consulting Pvt. Ltd.', true));
    }

    public function test_company_details_update_replaces_demo_values_but_keeps_real_ones(): void
    {
        $demo = Tenant::create([
            'name' => 'Magnova Consulting Pvt Ltd',
            'slug' => 'demo-co',
            'address' => 'Plot No. 123, Nayapalli, Bhubaneswar, Odisha - 751012',
            'contact_phone' => '9861000000',
        ]);
        $real = Tenant::create([
            'name' => 'Other Company',
            'slug' => 'real-co',
            'address' => '5 Real Street, Cuttack',
            'contact_phone' => '9000000001',
        ]);

        (require database_path('migrations/2026_10_15_000002_update_magnova_company_details.php'))->up();

        $demo = DB::table('tenants')->find($demo->id);
        $this->assertSame('Magnova Consulting Pvt. Ltd.', $demo->name);
        $this->assertSame('Plot No. 481/770, P.O. Balianta, Dandilo, Bhubaneswar - 752100, Khordha, Odisha', $demo->address);
        $this->assertSame('7008960018', $demo->contact_phone);
        $this->assertSame('752100', $demo->establishment_pin);

        $real = DB::table('tenants')->find($real->id);
        $this->assertSame('Other Company', $real->name);
        $this->assertSame('5 Real Street, Cuttack', $real->address);
        $this->assertSame('9000000001', $real->contact_phone);
    }
}
