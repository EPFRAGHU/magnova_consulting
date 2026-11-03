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
use Tests\TestCase;

class InvoiceGemcRefNoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'GEM unit', 'active' => true, 'gemc_no' => 'GEMC-511687723492093']);
    }

    private function invoice(): Invoice
    {
        $employee = Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $this->client->id, 'employee_code' => 'G-1', 'name' => 'Guard', 'gender' => 'male',
            'skill_category' => 'skilled', 'designation' => 'Guard', 'rate_per_day' => 700, 'status' => 'active',
        ]);
        foreach (range(1, 20) as $day) {
            Attendance::create(['tenant_id' => $this->user->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('2026-10-%02d', $day), 'status' => 'present']);
        }
        app(WageCalculator::class)->calculateForEmployee($employee, 10, 2026);

        return app(InvoiceCalculator::class)->generateForClient($this->client, 10, 2026, 'MCPL/26-27/1');
    }

    private function html(Invoice $invoice): string
    {
        return view('pdf.invoice', [
            'invoice' => $invoice->loadMissing('client', 'lineItems.employee', 'tenant'),
            'dutySummary' => app(PdfExportService::class)->dutySummary($invoice),
            'bonus' => 0,
            'amountInWords' => 'Words',
            'logoDataUri' => null,
        ])->render();
    }

    public function test_the_clients_gemc_number_prints_without_typing_it_on_each_invoice(): void
    {
        $invoice = $this->invoice();

        $this->assertNull($invoice->ref_no, 'nothing was typed on the invoice');
        $this->assertStringContainsString('Ref no: GEMC-511687723492093', $this->html($invoice));
    }

    public function test_a_ref_no_typed_on_the_invoice_wins_and_clearing_it_goes_back_to_the_clients(): void
    {
        $invoice = $this->invoice();

        $invoice->update(['ref_no' => 'PO-77/2026']);
        $this->assertStringContainsString('Ref no: PO-77/2026', $this->html($invoice->fresh()));

        $invoice->update(['ref_no' => '']);
        $this->assertStringContainsString('Ref no: GEMC-511687723492093', $this->html($invoice->fresh()));
    }

    public function test_a_change_of_the_clients_gemc_number_reaches_its_invoices(): void
    {
        $invoice = $this->invoice();
        $this->client->update(['gemc_no' => 'GEMC-999']);

        $this->assertStringContainsString('Ref no: GEMC-999', $this->html($invoice->fresh()));
    }

    public function test_without_any_number_the_ref_no_shows_a_dash(): void
    {
        $this->client->update(['gemc_no' => null]);

        $this->assertStringContainsString('Ref no: —', $this->html($this->invoice()));
    }

    public function test_a_contract_labour_invoice_prints_the_same_way_from_its_snapshot(): void
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $this->client->id, 'invoice_no' => 'MCPL/26-27/9', 'invoice_date' => '2026-11-02',
            'month' => 10, 'year' => 2026, 'rate_per_day' => 0, 'no_of_persons' => 1, 'no_of_duties' => 1, 'base_amount' => 1, 'epf_employer' => 0,
            'esi_employer' => 0, 'taxable_value' => 1, 'service_charge' => 0, 'cgst' => 0, 'sgst' => 0, 'grand_total' => 1, 'status' => 'draft',
            'document' => ['ref_no' => null, 'invoice_no' => 'MCPL/26-27/9'],
        ]);

        $this->assertSame('GEMC-511687723492093', $invoice->printableDocument()['ref_no'], 'blank in the snapshot, so the client number');

        $invoice->update(['ref_no' => 'TYPED-1']);
        $this->assertSame('TYPED-1', $invoice->fresh()->printableDocument()['ref_no']);
    }
}
