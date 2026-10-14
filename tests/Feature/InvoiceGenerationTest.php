<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WageCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class InvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function setUpUser(): array
    {
        $tenant = Tenant::create(['name' => 'Magnova Consulting Pvt Ltd', 'slug' => 'magnova-gen']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'email' => 'owner@example.test',
            'password' => 'secret-pass-123',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Unit A', 'active' => true]);

        return [$tenant, $user, $client];
    }

    public function test_generating_without_saved_wages_returns_a_visible_error(): void
    {
        [, $user, $client] = $this->setUpUser();

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'client_id' => $client->id,
            'month' => 9,
            'year' => 2026,
        ]);

        $response->assertSessionHasErrors(['month' => 'No wage records found for this client and month. Save wages first.']);
        $this->assertSame(0, Invoice::count());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function saveSeptemberWages(Tenant $tenant, Client $client): void
    {
        $employee = Employee::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'employee_code' => 'G-1',
            'name' => 'Guard One',
            'skill_category' => 'skilled',
            'rate_per_day' => 918,
            'status' => 'active',
        ]);
        foreach (range(1, 22) as $day) {
            Attendance::create([
                'tenant_id' => $tenant->id,
                'employee_id' => $employee->id,
                'date' => sprintf('2026-09-%02d', $day),
                'status' => 'present',
            ]);
        }
        app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);
    }

    private function generate(User $user, Client $client, ?string $number): TestResponse
    {
        return $this->actingAs($user)->post(route('invoices.store'), array_filter([
            'client_id' => $client->id,
            'month' => 9,
            'year' => 2026,
            'invoice_number' => $number,
        ], fn ($v) => $v !== null));
    }

    public function test_generating_with_saved_wages_uses_the_number_the_user_typed(): void
    {
        Carbon::setTestNow('2026-10-14');
        [$tenant, $user, $client] = $this->setUpUser();
        $this->saveSeptemberWages($tenant, $client);

        $response = $this->generate($user, $client, '61');

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertSame('MCPL/26-27/61', $invoice->invoice_no);
        $response->assertRedirect(route('invoices.show', $invoice));
    }

    public function test_a_new_invoice_needs_a_number(): void
    {
        [$tenant, $user, $client] = $this->setUpUser();
        $this->saveSeptemberWages($tenant, $client);

        $this->generate($user, $client, null)->assertSessionHasErrors(['invoice_number' => 'Enter the invoice number.']);
        $this->assertSame(0, Invoice::count());
    }

    public function test_an_invoice_number_cannot_be_used_twice(): void
    {
        Carbon::setTestNow('2026-10-14');
        [$tenant, $user, $client] = $this->setUpUser();
        $other = Client::create(['tenant_id' => $tenant->id, 'name' => 'Unit B', 'active' => true]);
        $this->saveSeptemberWages($tenant, $client);
        Invoice::create([
            'tenant_id' => $tenant->id, 'client_id' => $other->id, 'invoice_no' => 'MCPL/26-27/61',
            'invoice_date' => '2026-10-01', 'month' => 8, 'year' => 2026, 'rate_per_day' => 1, 'no_of_persons' => 1,
            'no_of_duties' => 1, 'base_amount' => 1, 'epf_employer' => 0, 'epf_admin' => 0, 'edli_cont' => 0,
            'esi_employer' => 0, 'taxable_value' => 1, 'service_charge' => 0, 'cgst' => 0, 'sgst' => 0,
            'grand_total' => 1, 'status' => 'draft',
        ]);

        $this->generate($user, $client, '61')->assertSessionHasErrors('invoice_number');
        $this->assertSame(1, Invoice::count());
    }

    public function test_regenerating_an_existing_invoice_keeps_its_number_without_asking(): void
    {
        Carbon::setTestNow('2026-10-14');
        [$tenant, $user, $client] = $this->setUpUser();
        $this->saveSeptemberWages($tenant, $client);
        $this->generate($user, $client, '7');

        $this->generate($user, $client, null)->assertSessionHasNoErrors();

        $this->assertSame(1, Invoice::count());
        $this->assertSame('MCPL/26-27/7', Invoice::first()->invoice_no);
    }

    public function test_invoice_number_accepts_only_letters_digits_and_hyphens(): void
    {
        [$tenant, $user, $client] = $this->setUpUser();
        $this->saveSeptemberWages($tenant, $client);

        $this->generate($user, $client, '6/1')->assertSessionHasErrors('invoice_number');
    }

    public function test_the_financial_year_in_the_prefix_changes_on_1_april(): void
    {
        $name = 'Magnova Consulting Pvt. Ltd.';

        $this->assertSame('MCPL/26-27/', Invoice::numberPrefix($name, Carbon::parse('2026-04-01')));
        $this->assertSame('MCPL/26-27/', Invoice::numberPrefix($name, Carbon::parse('2027-03-31')));
        $this->assertSame('MCPL/27-28/', Invoice::numberPrefix($name, Carbon::parse('2027-04-01')));
        $this->assertSame('MCPL/25-26/', Invoice::numberPrefix($name, Carbon::parse('2026-03-31')));
        $this->assertSame('MCPL/26-27/', Invoice::numberPrefix('M/s Magnova Consulting Pvt. Ltd.', Carbon::parse('2026-10-14')));
    }

    public function test_the_invoices_page_gets_the_fixed_part_of_the_number(): void
    {
        Carbon::setTestNow('2027-04-02');
        [, $user] = $this->setUpUser();

        $this->actingAs($user)->get(route('invoices.index'))
            ->assertInertia(fn ($page) => $page->where('invoice_prefix', 'MCPL/27-28/'));
    }
}
