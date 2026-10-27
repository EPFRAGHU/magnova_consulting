<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ContractWageSheet;
use App\Services\InvoiceRefresher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContractInvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $contract;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-05 10:00:00');
        config(['magnova.contract_labour.pf_round_digits' => 0]);   // the sheet numbers below are the client's workbook, which rounds PF
        $this->user = User::factory()->create();
        $this->user->tenant->update(['name' => 'Magnova Consulting Pvt. Ltd.', 'gstin' => '21AAFCM1234B1Z5', 'pan' => 'AAFCM1234B']);
        $this->contract = Client::create([
            'tenant_id' => $this->user->tenant_id, 'name' => 'Balmer Lawrie - Jatni', 'active' => true, 'payroll_profile' => 'contract_labour',
            'principal_employer_name' => 'Balmer Lawrie and Company Limited', 'principal_employer_address' => 'Jatni, Khordha, Odisha',
            'principal_employer_gstin' => '21AABCB0984E1Z7',
        ]);
    }

    private function septemberSheet(): void
    {
        $sheet = json_decode(file_get_contents(base_path('tests/Fixtures/contract_labour_sep_2026.json')), true);
        $designations = ['FLO - Skilled' => ['FLO', 'skilled'], 'Picker - SS' => ['Picker', 'semi_skilled'], 'Loader - UnS' => ['Loader', 'unskilled']];

        foreach ($sheet['rows'] as $i => $row) {
            $key = trim(preg_replace('/\s*-\s*/', ' - ', $row['category']));
            [$designation, $skill] = $designations[$key];
            $employee = Employee::create([
                'tenant_id' => $this->user->tenant_id, 'client_id' => $this->contract->id, 'employee_code' => 'CI-'.$i, 'name' => "Worker {$i}",
                'skill_category' => $skill, 'designation' => $designation, 'rate_per_day' => $row['rate'], 'status' => 'active',
            ]);
            $whole = (int) floor($row['paid_days']);
            $statuses = array_merge(array_fill(0, max(0, $whole - 1), 'present'), $whole >= 1 ? ['holiday'] : [], $row['paid_days'] - $whole > 0 ? ['half_day'] : []);
            foreach ($statuses as $d => $status) {
                Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('2026-09-%02d', $d + 1), 'status' => $status, 'ot_hours' => $d === 0 ? $row['ot_hours'] : 0]);
            }
            if ($row['ot_hours'] > 0 && $statuses === []) {
                Attendance::create(['tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'off', 'ot_hours' => $row['ot_hours']]);
            }
        }

        app(ContractWageSheet::class)->save($this->contract, 9, 2026);
    }

    private function generate(array $extra = [])
    {
        return $this->actingAs($this->user)->post(route('invoices.store'), array_merge([
            'client_id' => $this->contract->id, 'month' => 9, 'year' => 2026, 'invoice_number' => '12',
        ], $extra));
    }

    private function prefix(): string
    {
        return Invoice::numberPrefix('Magnova Consulting Pvt. Ltd.', now());
    }

    public function test_the_invoice_is_built_from_the_saved_wage_sheet(): void
    {
        $this->septemberSheet();

        $response = $this->generate()->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame($this->prefix().'12', $invoice->invoice_no);
        $this->assertSame('draft', $invoice->status);
        $this->assertEqualsWithDelta(510822.16, (float) $invoice->taxable_value, 0.001);
        $this->assertEqualsWithDelta(45974.00, (float) $invoice->cgst, 0.001);
        $this->assertEqualsWithDelta(45973.99, (float) $invoice->sgst, 0.001);
        $this->assertEquals(602770.15, (float) $invoice->grand_total);
        $this->assertSame(24, (int) $invoice->no_of_persons);
        $this->assertEquals(584, (float) $invoice->no_of_duties);
        $this->assertSame('2026-10-05', $invoice->invoice_date->toDateString());
        $this->assertSame('2026-11-04', $invoice->due_date->toDateString());
    }

    public function test_by_default_the_invoice_is_exact_to_the_paisa_with_no_round_off(): void
    {
        config(['magnova.contract_labour.pf_round_digits' => null]);
        $this->septemberSheet();

        $this->generate()->assertSessionHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertEqualsWithDelta(510823.12, (float) $invoice->taxable_value, 0.001);
        $this->assertEqualsWithDelta($invoice->taxable_value + $invoice->cgst + $invoice->sgst, (float) $invoice->grand_total, 0.001);
        $this->assertEqualsWithDelta(602771.28, (float) $invoice->grand_total, 0.001);
        $this->assertEquals(0, $invoice->document['round_off']);
    }

    public function test_the_snapshot_holds_the_category_lines_and_the_tax_split(): void
    {
        $this->septemberSheet();
        $this->generate();

        $document = Invoice::first()->document;

        $this->assertSame(['FLO - Skilled', 'Picker - Semi-Skilled', 'Loader - Unskilled'], array_column($document['lines'], 'particulars'));
        $this->assertSame([3, 4, 17], array_column($document['lines'], 'persons'));
        $this->assertSame('intra', $document['tax_mode']);
        $this->assertEquals(602770.15, $document['total']);
        $this->assertSame($this->prefix().'12', $document['invoice_no']);
    }

    public function test_an_invoice_to_another_state_is_stored_as_igst(): void
    {
        $this->contract->update(['principal_employer_gstin' => '36AABCB0984E1Z7']);
        $this->septemberSheet();

        $this->generate()->assertSessionHasNoErrors();

        $invoice = Invoice::first();
        $this->assertEqualsWithDelta(91947.99, (float) $invoice->igst, 0.001);
        $this->assertEquals(0, (float) $invoice->cgst + (float) $invoice->sgst);
        $this->assertEquals(602770.15, (float) $invoice->grand_total);
    }

    public function test_without_saved_wages_there_is_no_invoice(): void
    {
        $this->generate()->assertSessionHasErrors('month');

        $this->assertSame(0, Invoice::count());
    }

    public function test_changed_attendance_after_saving_must_be_saved_again_first(): void
    {
        $this->septemberSheet();
        Attendance::whereDate('date', '2026-09-02')->first()->update(['status' => 'absent']);

        $this->generate()->assertSessionHasErrors('month');

        $this->assertSame(0, Invoice::count());
    }

    public function test_a_new_invoice_needs_a_number_and_a_used_number_is_refused(): void
    {
        $this->septemberSheet();

        $this->generate(['invoice_number' => ''])->assertSessionHasErrors('invoice_number');

        Invoice::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $this->contract->id, 'invoice_no' => $this->prefix().'12', 'invoice_date' => '2026-09-01',
            'month' => 8, 'year' => 2026, 'rate_per_day' => 0, 'no_of_persons' => 1, 'no_of_duties' => 1, 'base_amount' => 1, 'epf_employer' => 0,
            'esi_employer' => 0, 'taxable_value' => 1, 'service_charge' => 0, 'cgst' => 0, 'sgst' => 0, 'grand_total' => 1, 'status' => 'draft',
        ]);
        $this->generate()->assertSessionHasErrors('invoice_number');
    }

    public function test_generating_again_updates_a_draft_and_keeps_its_number(): void
    {
        $this->septemberSheet();
        $this->generate();
        $first = Invoice::first();

        // Attendance changes, wages are saved again, and the invoice is regenerated.
        Attendance::whereDate('date', '2026-09-02')->first()->update(['status' => 'absent']);
        app(ContractWageSheet::class)->save($this->contract, 9, 2026);
        $this->generate(['invoice_number' => '99'])->assertSessionHasNoErrors();

        $this->assertSame(1, Invoice::count());
        $second = Invoice::first();
        $this->assertSame($first->invoice_no, $second->invoice_no, 'the number is kept');
        $this->assertSame($first->id, $second->id);
        $this->assertLessThan((float) $first->grand_total, (float) $second->grand_total);
    }

    public function test_a_sent_invoice_is_never_regenerated(): void
    {
        $this->septemberSheet();
        $this->generate();
        Invoice::first()->update(['status' => 'sent']);
        $before = Invoice::first()->grand_total;

        $this->generate()->assertSessionHasErrors('month');

        $this->assertEquals($before, Invoice::first()->grand_total);
    }

    public function test_the_pdf_uses_the_saved_snapshot_even_after_the_wages_change(): void
    {
        $this->septemberSheet();
        $this->generate();
        $invoice = Invoice::first();

        Attendance::whereDate('date', '2026-09-02')->first()->update(['status' => 'absent']);
        app(ContractWageSheet::class)->save($this->contract, 9, 2026);

        $response = $this->actingAs($this->user)->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertEquals(602770.15, (float) Invoice::first()->grand_total, 'the stored invoice is untouched');
    }

    public function test_the_other_invoice_tools_leave_a_contract_invoice_alone(): void
    {
        $this->septemberSheet();
        $this->generate();
        $before = Invoice::first()->only(['taxable_value', 'grand_total', 'document']);

        $this->artisan('wages:apply-bonus-setting')->assertSuccessful();
        app(InvoiceRefresher::class)->refreshDrafts($this->contract);

        $this->assertEquals($before, Invoice::first()->only(['taxable_value', 'grand_total', 'document']));
        $this->assertSame(0, app(InvoiceRefresher::class)->draftInvoiceCount($this->contract));
    }

    public function test_the_wages_page_offers_the_invoice_button_with_the_number_prefix(): void
    {
        $this->septemberSheet();
        $url = route('wages.index', ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026]);

        $this->actingAs($this->user)->get($url)
            ->assertInertia(fn ($page) => $page->where('invoice', null)->where('invoice_prefix', $this->prefix())->where('can_invoice', true));

        $this->generate();

        $this->actingAs($this->user)->get($url)
            ->assertInertia(fn ($page) => $page->where('invoice.invoice_no', $this->prefix().'12')->where('invoice.status', 'draft'));
    }

    public function test_regular_units_still_generate_their_invoices_the_old_way(): void
    {
        $standard = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Plain unit', 'active' => true]);

        $this->actingAs($this->user)->post(route('invoices.store'), ['client_id' => $standard->id, 'month' => 9, 'year' => 2026, 'invoice_number' => '5'])
            ->assertSessionHasErrors('month');   // the usual "no wage records found" message

        $this->assertSame(0, Invoice::count());
    }
}
