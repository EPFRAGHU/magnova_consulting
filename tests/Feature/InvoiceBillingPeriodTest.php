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

class InvoiceBillingPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Plain unit', 'active' => true]);
    }

    private function invoice(): Invoice
    {
        $employee = Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $this->client->id, 'employee_code' => 'BP-1', 'name' => 'Guard', 'gender' => 'male',
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

    private function save(Invoice $invoice, array $extra)
    {
        return $this->actingAs($this->user)->put(route('invoices.update', $invoice), $extra + [
            'invoice_no' => $invoice->invoice_no, 'invoice_date' => '2026-11-02', 'status' => 'draft',
        ]);
    }

    public function test_the_billing_period_is_the_calendar_month_until_the_user_types_another(): void
    {
        $this->assertStringContainsString('01-10-2026 to 31-10-2026', $this->html($this->invoice()));
    }

    public function test_a_typed_billing_period_is_saved_and_printed(): void
    {
        $invoice = $this->invoice();

        $this->save($invoice, ['billing_period_start' => '2026-10-05', 'billing_period_end' => '2026-11-04'])->assertSessionHasNoErrors();

        $html = $this->html($invoice->fresh());
        $this->assertStringContainsString('05-10-2026 to 04-11-2026', $html);
        $this->assertStringContainsString('Bill for the month of October-2026', $html, 'the month heading is unchanged');
    }

    public function test_both_dates_are_needed_and_the_end_cannot_be_before_the_start(): void
    {
        $invoice = $this->invoice();

        $this->save($invoice, ['billing_period_start' => '2026-10-05'])->assertSessionHasErrors('billing_period_end');
        $this->save($invoice, ['billing_period_end' => '2026-10-05'])->assertSessionHasErrors('billing_period_start');
        $this->save($invoice, ['billing_period_start' => '2026-10-20', 'billing_period_end' => '2026-10-05'])->assertSessionHasErrors('billing_period_end');
        $this->assertNull($invoice->fresh()->billing_period_start);
    }

    public function test_clearing_both_dates_goes_back_to_the_whole_month(): void
    {
        $invoice = $this->invoice();
        $this->save($invoice, ['billing_period_start' => '2026-10-05', 'billing_period_end' => '2026-11-04']);

        $this->save($invoice, ['billing_period_start' => '', 'billing_period_end' => ''])->assertSessionHasNoErrors();

        $this->assertStringContainsString('01-10-2026 to 31-10-2026', $this->html($invoice->fresh()));
    }

    public function test_regenerating_the_invoice_keeps_the_typed_period(): void
    {
        $invoice = $this->invoice();
        $this->save($invoice, ['billing_period_start' => '2026-10-05', 'billing_period_end' => '2026-11-04']);

        $again = app(InvoiceCalculator::class)->generateForClient($this->client, 10, 2026, $invoice->invoice_no);

        $this->assertSame('2026-10-05', $again->fresh()->billing_period_start->toDateString());
    }

    public function test_a_contract_labour_invoice_prints_the_typed_period_from_its_snapshot(): void
    {
        $invoice = Invoice::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $this->client->id, 'invoice_no' => 'MCPL/26-27/9', 'invoice_date' => '2026-11-02',
            'month' => 10, 'year' => 2026, 'rate_per_day' => 0, 'no_of_persons' => 1, 'no_of_duties' => 1, 'base_amount' => 1, 'epf_employer' => 0,
            'esi_employer' => 0, 'taxable_value' => 1, 'service_charge' => 0, 'cgst' => 0, 'sgst' => 0, 'grand_total' => 1, 'status' => 'draft',
            'document' => ['period_start' => '2026-10-01', 'invoice_no' => 'MCPL/26-27/9'],
        ]);

        $this->assertArrayNotHasKey('billing_period_start', $invoice->printableDocument());

        $this->save($invoice, ['billing_period_start' => '2026-10-05', 'billing_period_end' => '2026-11-04']);

        $document = $invoice->fresh()->printableDocument();
        $this->assertSame('2026-10-05', $document['billing_period_start']);
        $this->assertSame('2026-11-04', $document['billing_period_end']);
    }

    /** @param array<string, mixed> $rule */
    private function rule(array $rule): void
    {
        $this->client->update($rule);
    }

    public function test_the_clients_monthly_period_prints_on_every_invoice(): void
    {
        $this->rule(['billing_from_day' => 26, 'billing_from_month' => 'previous', 'billing_to_day' => 25, 'billing_to_month' => 'same']);

        $this->assertStringContainsString('26-09-2026 to 25-10-2026', $this->html($this->invoice()));
    }

    public function test_the_period_can_run_from_this_month_into_the_next(): void
    {
        $this->rule(['billing_from_day' => 26, 'billing_from_month' => 'same', 'billing_to_day' => 25, 'billing_to_month' => 'next']);

        $this->assertStringContainsString('26-10-2026 to 25-11-2026', $this->html($this->invoice()));
    }

    public function test_a_day_the_month_does_not_have_becomes_its_last_day(): void
    {
        $this->rule(['billing_from_day' => 31, 'billing_from_month' => 'previous', 'billing_to_day' => 30, 'billing_to_month' => 'same']);

        $this->assertSame(['2026-09-30', '2026-10-30'], array_map(fn ($d) => $d->toDateString(), $this->client->billingPeriodFor(10, 2026)));
    }

    public function test_a_period_typed_on_the_invoice_beats_the_clients_monthly_period(): void
    {
        $this->rule(['billing_from_day' => 26, 'billing_from_month' => 'previous', 'billing_to_day' => 25, 'billing_to_month' => 'same']);
        $invoice = $this->invoice();
        $this->save($invoice, ['billing_period_start' => '2026-10-05', 'billing_period_end' => '2026-11-04']);

        $this->assertStringContainsString('05-10-2026 to 04-11-2026', $this->html($invoice->fresh()));
    }

    public function test_a_contract_labour_bill_gets_the_clients_period_too(): void
    {
        $this->rule(['billing_from_day' => 26, 'billing_from_month' => 'previous', 'billing_to_day' => 25, 'billing_to_month' => 'same']);
        $invoice = Invoice::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $this->client->id, 'invoice_no' => 'MCPL/26-27/8', 'invoice_date' => '2026-11-02',
            'month' => 10, 'year' => 2026, 'rate_per_day' => 0, 'no_of_persons' => 1, 'no_of_duties' => 1, 'base_amount' => 1, 'epf_employer' => 0,
            'esi_employer' => 0, 'taxable_value' => 1, 'service_charge' => 0, 'cgst' => 0, 'sgst' => 0, 'grand_total' => 1, 'status' => 'draft',
            'document' => ['period_start' => '2026-10-01'],
        ]);

        $document = $invoice->printableDocument();
        $this->assertSame('2026-09-26', $document['billing_period_start']);
        $this->assertSame('2026-10-25', $document['billing_period_end']);
    }

    public function test_the_client_form_saves_and_checks_the_period(): void
    {
        $this->actingAs($this->user)->put(route('clients.update', $this->client), [
            'name' => 'Plain unit', 'billing_from_day' => 26, 'billing_from_month' => 'previous', 'billing_to_day' => 25, 'billing_to_month' => 'same',
        ])->assertSessionHasNoErrors();
        $this->assertSame(26, $this->client->fresh()->billing_from_day);
        $this->assertSame('previous', $this->client->fresh()->billing_from_month);

        $this->actingAs($this->user)->put(route('clients.update', $this->client), ['name' => 'Plain unit', 'billing_from_day' => 40, 'billing_to_day' => 25])
            ->assertSessionHasErrors('billing_from_day');
        $this->actingAs($this->user)->put(route('clients.update', $this->client), ['name' => 'Plain unit', 'billing_from_day' => 5])
            ->assertSessionHasErrors('billing_to_day');

        $this->actingAs($this->user)->put(route('clients.update', $this->client), ['name' => 'Plain unit', 'billing_from_day' => '', 'billing_to_day' => ''])->assertSessionHasNoErrors();
        $this->assertNull($this->client->fresh()->billing_from_day);
        $this->assertNull($this->client->fresh()->billingPeriodFor(10, 2026));
    }
}
