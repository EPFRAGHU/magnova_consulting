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

class ZonalInvoiceLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->tenant->update(['name' => 'Magnova Consulting Pvt. Ltd.', 'gstin' => '21AAMCM4556E1ZL', 'pan' => 'AAMCM4556E', 'address' => 'Plot No. 481/770, P.O. Balianta, Dandilo']);
    }

    /** One guard at 918 a day for 26 days, the figures of the Indian Bank Zonal sample bill (Sep 2026). */
    private function invoice(string $layout): Invoice
    {
        $client = Client::create([
            'tenant_id' => $this->user->tenant_id, 'name' => 'MCPL - The Zonal Main,Indian Bank', 'active' => true, 'billing_name' => 'The Zonal Manager,Indian Bank,',
            'billing_address' => 'Cuttack Zonal Office', 'gstin' => '21AAACI1607G2ZA', 'service_charge_on_basic' => true, 'invoice_layout' => $layout,
        ]);
        $employee = Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'IB-1', 'name' => 'Guard', 'gender' => 'male',
            'skill_category' => 'skilled', 'designation' => 'SECURITY GUARD', 'rate_per_day' => 918, 'status' => 'active',
        ]);
        foreach (range(1, 26) as $day) {
            Attendance::create(['tenant_id' => $this->user->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('2026-09-%02d', $day), 'status' => 'present']);
        }
        app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);

        return app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'MCPL/26-27/163');
    }

    public function test_the_zonal_bill_has_lettered_rows_one_epf_line_and_exact_paise(): void
    {
        $invoice = $this->invoice('indian_bank_zonal');
        $html = view('pdf.invoice-zonal', [
            'invoice' => $invoice->loadMissing('client', 'lineItems.employee', 'tenant'),
            'dutySummary' => app(PdfExportService::class)->dutySummary($invoice),
            'bonus' => 0,
            'amountInWords' => 'Words',
            'logoDataUri' => null,
        ])->render();

        // The EPF ceiling rises on 17 Sep 2026 (config), so the amounts are read from the invoice, not typed in.
        $epf = number_format((float) $invoice->epf_employer + (float) $invoice->edli_cont + (float) $invoice->epf_admin, 2);
        $taxable = number_format((float) $invoice->taxable_value + (float) $invoice->service_charge, 2);

        foreach (['TAX INVOICE', 'Bill For The month of Sep 2026', 'The Zonal Manager,Indian Bank,', '21AAACI1607G2ZA', 'No Of Person Engaged',
            'EPF @ 13%', '23,868.00', $epf, 'SERVICE CHARGES @ 3.85%', '918.92', 'TOTAL TAXABLE', $taxable, 'CGST @9 %', 'GRAND TOTAL',
            number_format((float) $invoice->grand_total, 2), 'Managing Director', 'CIN NO: U74999OR2019PTC030507', '01-09-2026 to 30-09-2026'] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        $this->assertNotSame(0, (int) round((float) $invoice->grand_total * 100) % 100, 'exact paise, not rounded to the rupee');
        $this->assertStringNotContainsString('ROUNDED OFF', $html);
        $this->assertStringNotContainsString('ESI @', $html, 'no ESIC line when the guard earns above the ESIC limit');
    }

    public function test_only_zonal_clients_get_the_zonal_design(): void
    {
        $invoice = $this->invoice('standard');
        $used = false;
        View::composer('pdf.invoice-zonal', function () use (&$used) {
            $used = true;
        });

        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->invoice($invoice)->output());
        $this->assertFalse($used);

        $invoice->client->update(['invoice_layout' => 'indian_bank_zonal']);
        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->invoice($invoice->fresh())->output());
        $this->assertTrue($used);
    }
}
