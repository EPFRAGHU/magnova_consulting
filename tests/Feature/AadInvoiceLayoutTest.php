<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceCalculator;
use App\Services\PdfExportService;
use App\Services\WageCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AadInvoiceLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->tenant->update(['name' => 'Magnova Consulting Pvt. Ltd.', 'gstin' => '21AAMCM4556E1ZL', 'pan' => 'AAMCM4556E', 'address' => 'Plot No. 481/770, P.O. Balianta, Dandilo']);
    }

    /** Five guards at 541 a day for 30 days, the figures of the AAD Gopalpur sample bill (Aug 2026). */
    private function invoice(string $layout, int $month = 8): Invoice
    {
        $client = Client::create([
            'tenant_id' => $this->user->tenant_id, 'name' => 'MCPL - AAD-Golabandh', 'active' => true, 'billing_name' => 'The Office the PAO (ORs)',
            'billing_address' => 'At/Po-Golabandh,Ganjam', 'gemc_no' => 'GEMC-511687767190144', 'service_charge_on_basic' => true, 'invoice_layout' => $layout,
        ]);
        foreach (range(1, 5) as $n) {
            $employee = Employee::create([
                'tenant_id' => $this->user->tenant_id, 'client_id' => $client->id, 'employee_code' => "AAD-$n", 'name' => "Guard $n", 'gender' => 'male',
                'skill_category' => 'unskilled', 'designation' => 'SECURITY GUARD MALE', 'rate_per_day' => 541, 'status' => 'active',
            ]);
            foreach (range(1, 30) as $day) {
                Attendance::create(['tenant_id' => $this->user->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('2026-%02d-%02d', $month, $day), 'status' => 'present']);
            }
            app(WageCalculator::class)->calculateForEmployee($employee, $month, 2026);
        }

        return app(InvoiceCalculator::class)->generateForClient($client, $month, 2026, 'MCPL/26-27/149');
    }

    private function html(Invoice $invoice): string
    {
        return view('pdf.invoice-aad', [
            'invoice' => $invoice->loadMissing('client', 'lineItems.employee', 'tenant'),
            'dutySummary' => app(PdfExportService::class)->dutySummary($invoice),
            'bonus' => 0,
            'amountInWords' => 'Words',
            'logoDataUri' => null,
        ])->render();
    }

    public function test_the_aad_bill_shows_the_sample_layout_and_figures_rounded_to_the_rupee(): void
    {
        $invoice = $this->invoice('aad_college');
        $html = $this->html($invoice);

        $this->assertSame('114061.00', (string) $invoice->grand_total);
        foreach (['TAX INVOICE', 'Bill For The month of Aug-2026', 'Details of Receiver (Billed To)', 'The Office the PAO (ORs)', 'GEMC-511687767190144',
            'Summary of Duties Performed in the Month of Aug 2026', 'EPF Edli @ 0.5% ON BASIC WAGES', 'EPF Admin Charges @ 0.5% ON BASIC WAGES',
            '81,150.00', '9,000.00', '2,637.38', '3,124.28', '96,661.66', '8,699.55', '114,060.76', 'ROUNDED OFF', '0.24', '114,061.00',
            'Ranjita Mohapatra ( Managing Director)', 'CIN NO: U74999OR2019PTC030507', '01-08-2026 to 31-08-2026'] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        $this->assertStringNotContainsString('SAC Code', $html);
    }

    public function test_from_the_current_register_rules_gst_is_also_on_the_taxable_value_with_the_service_charge(): void
    {
        $invoice = $this->invoice('aad_college', 9);
        $taxable = (float) $invoice->taxable_value + (float) $invoice->service_charge;

        $this->assertEqualsWithDelta($taxable * 0.09, (float) $invoice->cgst, 0.05);
        $this->assertEqualsWithDelta($taxable * 0.18, (float) $invoice->cgst + (float) $invoice->sgst, 0.05);
        $this->assertSame(round($taxable + (float) $invoice->cgst + (float) $invoice->sgst), (float) $invoice->grand_total);
    }

    public function test_only_aad_clients_get_the_aad_design_and_the_others_stay_exact(): void
    {
        $standard = $this->invoice('standard');
        $this->assertNotSame('114061.00', (string) $standard->grand_total, 'standard clients are not rounded');

        $used = false;
        View::composer('pdf.invoice-aad', function () use (&$used) {
            $used = true;
        });
        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->invoice($standard)->output());
        $this->assertFalse($used);

        $aad = Invoice::findOrFail($standard->id);
        $aad->client->update(['invoice_layout' => 'aad_college']);
        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->invoice($aad->fresh())->output());
        $this->assertTrue($used);
    }

    public function test_the_client_form_saves_the_invoice_layout(): void
    {
        $client = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Unit', 'active' => true]);

        $this->actingAs($this->user)->put(route('clients.update', $client), ['name' => 'Unit', 'invoice_layout' => 'aad_college'])->assertSessionHasNoErrors();
        $this->assertSame('aad_college', $client->fresh()->invoice_layout);

        $this->actingAs($this->user)->put(route('clients.update', $client), ['name' => 'Unit', 'invoice_layout' => 'fancy'])->assertSessionHasErrors('invoice_layout');
    }
}
