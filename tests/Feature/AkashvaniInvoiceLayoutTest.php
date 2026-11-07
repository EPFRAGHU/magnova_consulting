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

class AkashvaniInvoiceLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->tenant->update(['name' => 'Magnova Consulting Pvt. Ltd.', 'gstin' => '21AAMCM4556E1ZL', 'pan' => 'AAMCM4556E', 'address' => 'Plot No. 481/770, P.O. Balianta, Dandilo']);
    }

    private function guard(Client $client, string $name, int $rate, int $days, int $month): void
    {
        $employee = Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $client->id, 'employee_code' => $name, 'name' => $name, 'gender' => 'male',
            'skill_category' => 'unskilled', 'designation' => 'SECURITY GUARD', 'rate_per_day' => $rate, 'status' => 'active',
        ]);
        foreach (range(1, $days) as $day) {
            Attendance::create(['tenant_id' => $this->user->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('2026-%02d-%02d', $month, $day), 'status' => 'present']);
        }
        app(WageCalculator::class)->calculateForEmployee($employee, $month, 2026);
    }

    /** The figures of the Akashvani sample bill: 9 guards at 693 for 224 duties in all, one at 556 for 26 (Aug 2026). */
    private function invoice(string $layout, int $month = 8): Invoice
    {
        $client = Client::create([
            'tenant_id' => $this->user->tenant_id, 'name' => 'Unit 1-Akashvani Cuttack', 'active' => true, 'billing_name' => 'The Head of the office,'."\n".'Akashvani Cuttack',
            'billing_address' => 'Cantonment Road Buxibazar,'."\n".'Cuttack, ODISHA-753001, India', 'gstin' => '21AAAJP0288R1ZR', 'sac_code' => '998519',
            'gemc_no' => 'GEMC-511687712208214', 'invoice_layout' => $layout,
        ]);
        foreach (range(1, 8) as $n) {
            $this->guard($client, "Guard $n", 693, 25, $month);
        }
        $this->guard($client, 'Guard 9', 693, 24, $month);
        $this->guard($client, 'Guard 10', 556, 26, $month);

        return app(InvoiceCalculator::class)->generateForClient($client, $month, 2026, 'MCPL/26-27/'.(166 + $month));
    }

    private function html(Invoice $invoice): string
    {
        $service = app(PdfExportService::class);

        return view('pdf.invoice-akashvani', [
            'invoice' => $invoice->loadMissing('client', 'lineItems.employee', 'tenant'),
            'dutySummary' => $service->dutySummary($invoice, true),
            'epfLines' => $service->epfLines($invoice),
            'bonus' => 0,
            'amountInWords' => 'Words',
            'logoDataUri' => null,
        ])->render();
    }

    public function test_the_akashvani_bill_has_the_sample_layout_and_figures(): void
    {
        $invoice = $this->invoice('akashvani');
        $html = $this->html($invoice);

        $this->assertSame('238508.06', (string) $invoice->grand_total, 'exact paise, no round-off');
        foreach (['TAX INVOICE', 'Bill For The month of Aug-2026', 'The Head of the office,', 'GST IN', '21AAAJP0288R1ZR', 'SAC CODE', '998519', 'GEMC-511687712208214',
            '155,232.00', '14,456.00', '169,688.00', 'EPF @ 13% of 15000 of 9', '17,550.00', 'EPF @ 13% of 14456 of 1', '1,879.28',
            'ESIC @ 3.25% ON BASIC WAGES', '5,514.86', 'SUB-TOTAL', '194,632.14', 'SERVICE CHARGES @ 3.85%', '7,493.34', '202,125.48',
            'CGST @ 9 % ON TOTAL WAGES &amp; SERVICE CHARGES', '18,191.29', '238,508.06', 'Ranjita Mohapatra ( Managing Director)'] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        $this->assertStringNotContainsString('ROUNDED OFF', $html);
        $this->assertStringNotContainsString('SGST @ 9 %</td><td class="amount">0.00', $html);
    }

    public function test_the_epf_lines_always_add_up_to_the_invoice_epf(): void
    {
        foreach ([8, 9] as $month) {
            $invoice = $this->invoice('akashvani', $month);
            $total = (float) $invoice->epf_employer + (float) $invoice->edli_cont + (float) $invoice->epf_admin;

            $this->assertEqualsWithDelta($total, array_sum(array_column(app(PdfExportService::class)->epfLines($invoice), 'amount')), 0.02, "month $month");
            $invoice->client->delete();
            Employee::query()->delete();
        }
    }

    public function test_only_akashvani_clients_get_the_akashvani_design_and_it_charges_gst_on_the_service_charge_too(): void
    {
        $invoice = $this->invoice('standard');
        $used = false;
        View::composer('pdf.invoice-akashvani', function () use (&$used) {
            $used = true;
        });

        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->invoice($invoice)->output());
        $this->assertFalse($used);

        $invoice->client->update(['invoice_layout' => 'akashvani']);
        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->invoice($invoice->fresh())->output());
        $this->assertTrue($used);
    }
}
