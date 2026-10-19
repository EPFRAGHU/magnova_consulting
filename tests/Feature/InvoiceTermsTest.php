<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\InvoiceCalculator;
use App\Services\PdfExportService;
use App\Services\WageCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTermsTest extends TestCase
{
    use RefreshDatabase;

    private function runCleanup(): void
    {
        (require database_path('migrations/2026_10_17_000005_trim_invoice_terms_and_conditions.php'))->up();
    }

    private function invoiceFor(Tenant $tenant): Invoice
    {
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Unit A', 'active' => true]);
        foreach ([['Alpha', 1008, 26], ['Bravo', 1008, 12], ['Charlie', 918, 22]] as $i => [$name, $rate, $days]) {
            $employee = Employee::create([
                'tenant_id' => $tenant->id, 'client_id' => $client->id, 'employee_code' => 'T-'.$i, 'name' => $name,
                'skill_category' => 'skilled', 'rate_per_day' => $rate, 'status' => 'active',
            ]);
            foreach (range(1, $days) as $day) {
                Attendance::create(['tenant_id' => $tenant->id, 'employee_id' => $employee->id, 'date' => sprintf('2026-09-%02d', $day), 'status' => 'present']);
            }
            app(WageCalculator::class)->calculateForEmployee($employee->fresh('client'), 9, 2026);
        }

        return app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'MCPL/26-27/1');
    }

    private function terms(Tenant $tenant): string
    {
        return $this->invoiceHtml($tenant);
    }

    private function invoiceHtml(Tenant $tenant): string
    {
        $invoice = $this->invoiceFor($tenant)->load('client', 'tenant');

        return view('pdf.invoice', [
            'invoice' => $invoice, 'dutySummary' => collect(), 'bonus' => 0, 'amountInWords' => 'Zero', 'logoDataUri' => null,
        ])->render();
    }

    public function test_the_default_terms_keep_only_the_jurisdiction_point(): void
    {
        $html = $this->terms(Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test']));

        $this->assertStringContainsString('E &amp; O.E', $html);
        $this->assertStringContainsString('Subject to &#039;Bhubaneswar&#039; jurisdiction only.', $html);
        $this->assertStringNotContainsString('Total payment due in 30 days', $html);
        $this->assertStringNotContainsString('Interest @ 18% p.a.', $html);
    }

    public function test_saved_terms_lose_points_1_and_2_and_the_last_point_becomes_1(): void
    {
        $oldDefault = "E & O.E\n1. Total payment due in 30 days.\n2. Interest @ 18% p.a. will be charged if payment is delayed beyond terms.\n3. Subject to 'Bhubaneswar' jurisdiction only.";
        $tenant = Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test', 'terms_and_conditions' => $oldDefault]);

        $this->runCleanup();

        $this->assertSame("E & O.E\n1. Subject to 'Bhubaneswar' jurisdiction only.", $tenant->fresh()->terms_and_conditions);
        $this->runCleanup();
        $this->assertSame("E & O.E\n1. Subject to 'Bhubaneswar' jurisdiction only.", $tenant->fresh()->terms_and_conditions, 'running it again changes nothing');
    }

    public function test_the_cleanup_keeps_other_lines_of_a_customised_text_and_ignores_empty_terms(): void
    {
        $custom = Tenant::create(['name' => 'Custom', 'slug' => 'custom', 'terms_and_conditions' => "E & O.E\n1. Total payment due in 15 days.\n2. Goods once sold will not be taken back.\n3. Subject to 'Bhubaneswar' jurisdiction only."]);
        $empty = Tenant::create(['name' => 'Empty', 'slug' => 'empty']);

        $this->runCleanup();

        $this->assertSame("E & O.E\n1. Total payment due in 15 days.\n2. Goods once sold will not be taken back.\n3. Subject to 'Bhubaneswar' jurisdiction only.", $custom->fresh()->terms_and_conditions, 'only the two points the user removed are touched');
        $this->assertNull($empty->fresh()->terms_and_conditions);
    }

    public function test_the_invoice_still_fits_on_one_page_with_room_for_a_signature(): void
    {
        $tenant = Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test']);
        $invoice = $this->invoiceFor($tenant);

        $pdf = app(PdfExportService::class)->invoice($invoice)->output();

        $this->assertSame(1, preg_match_all('#/Type\s*/Page\b#', $pdf), 'a normal invoice stays on one page');
    }
}
