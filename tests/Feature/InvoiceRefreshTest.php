<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Wage;
use App\Services\InvoiceCalculator;
use App\Services\WageCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceRefreshTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Client, 2: Invoice} */
    private function unitWithDraftInvoice(bool $bonus = true): array
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit A', 'active' => true, 'bonus_enabled' => $bonus]);
        $employee = Employee::create([
            'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'R-1', 'name' => 'Guard',
            'skill_category' => 'skilled', 'rate_per_day' => 500, 'status' => 'active',
        ]);
        foreach (range(1, 22) as $day) {
            Attendance::create([
                'tenant_id' => $user->tenant_id, 'employee_id' => $employee->id,
                'date' => sprintf('2026-09-%02d', $day), 'status' => 'present',
            ]);
        }
        app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);
        $invoice = app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'MCPL/26-27/50');

        return [$user, $client, $invoice];
    }

    private function save(User $user, Client $client, array $changes = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->put(route('clients.update', $client), array_merge([
            'name' => $client->name,
            'active' => 1,
            'bonus_enabled' => (int) $client->bonus_enabled,
            'service_charge_on_basic' => (int) $client->service_charge_on_basic,
            'gst_exempt' => (int) $client->gst_exempt,
        ], $changes));
    }

    public function test_saving_a_changed_billing_setting_asks_about_the_draft_invoices(): void
    {
        [$user, $client] = $this->unitWithDraftInvoice();

        $this->save($user, $client, ['gst_exempt' => 1])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('regenerate', ['client_id' => $client->id, 'name' => 'Unit A', 'count' => 1]);
    }

    public function test_no_question_when_no_billing_setting_changed(): void
    {
        [$user, $client] = $this->unitWithDraftInvoice();

        $this->save($user, $client, ['name' => 'Unit A renamed'])->assertSessionMissing('regenerate');
    }

    public function test_no_question_when_the_unit_has_no_draft_invoice(): void
    {
        [$user, $client, $invoice] = $this->unitWithDraftInvoice();
        $invoice->update(['status' => 'sent']);

        $this->save($user, $client, ['gst_exempt' => 1])->assertSessionMissing('regenerate');

        $other = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit B', 'active' => true]);
        $this->save($user, $other, ['bonus_enabled' => 1])->assertSessionMissing('regenerate');
    }

    public function test_the_clients_page_receives_the_question(): void
    {
        [$user] = $this->unitWithDraftInvoice();

        $this->actingAs($user)->withSession(['regenerate' => ['client_id' => 7, 'name' => 'Unit A', 'count' => 2]])
            ->get(route('clients.index'))
            ->assertInertia(fn ($page) => $page->where('regenerate.count', 2)->where('regenerate.name', 'Unit A'));

        $this->flushSession();
        $this->actingAs($user)->get(route('clients.index'))
            ->assertInertia(fn ($page) => $page->where('regenerate', null));
    }

    public function test_yes_regenerates_draft_invoices_keeps_numbers_and_leaves_sent_ones_alone(): void
    {
        [$user, $client, $draft] = $this->unitWithDraftInvoice(true);
        $this->assertGreaterThan(0, (float) $draft->cgst);
        $this->assertGreaterThan(0, (float) Wage::sum('bonus'));

        $sent = Invoice::create([
            'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'invoice_no' => 'MCPL/26-27/49',
            'invoice_date' => '2026-09-01', 'month' => 8, 'year' => 2026, 'rate_per_day' => 500, 'no_of_persons' => 1,
            'no_of_duties' => 1, 'base_amount' => 500, 'epf_employer' => 0, 'epf_admin' => 0, 'edli_cont' => 0,
            'esi_employer' => 0, 'taxable_value' => 500, 'service_charge' => 0, 'cgst' => 45, 'sgst' => 45,
            'grand_total' => 590, 'status' => 'sent',
        ]);

        $client->update(['bonus_enabled' => false, 'gst_exempt' => true]);

        $this->actingAs($user)->post(route('clients.regenerate-invoices', $client))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('clients.index'));

        $draft->refresh();
        $this->assertSame('MCPL/26-27/50', $draft->invoice_no);
        $this->assertEquals(0, $draft->cgst);
        $this->assertEquals(0, $draft->sgst);
        $this->assertEquals($draft->taxable_value + $draft->service_charge, $draft->grand_total);
        $this->assertEquals(0, Wage::sum('bonus'), 'saved wages follow the bonus setting');

        $sent->refresh();
        $this->assertEquals(45, $sent->cgst);
        $this->assertEquals(590, $sent->grand_total);
    }

    public function test_regenerating_skips_an_invoice_whose_wages_are_gone(): void
    {
        [$user, $client, $draft] = $this->unitWithDraftInvoice();
        $before = $draft->grand_total;
        Wage::query()->delete();
        $client->update(['gst_exempt' => true]);

        $this->actingAs($user)->post(route('clients.regenerate-invoices', $client))->assertSessionHasNoErrors();

        $this->assertEquals($before, $draft->fresh()->grand_total, 'an invoice is never zeroed because wages are missing');
    }
}
