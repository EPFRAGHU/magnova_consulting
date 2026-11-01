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

class RegularInvoiceLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(): Invoice
    {
        $user = User::factory()->create();
        $user->tenant->update(['name' => 'Magnova Consulting Pvt. Ltd.']);
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Plain unit', 'active' => true]);

        // Created lowest rank first, so the order on the invoice must come from the skill, not from creation order.
        foreach ([['Helper', 'unskilled'], ['Security Guard', 'semi_skilled'], ['Gunman', 'skilled'], ['Supervisor', 'high_skilled']] as $i => [$designation, $skill]) {
            $employee = Employee::create([
                'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'L-'.$i, 'name' => "Worker {$i}", 'gender' => 'male',
                'skill_category' => $skill, 'designation' => $designation, 'rate_per_day' => 700, 'status' => 'active',
            ]);
            foreach (range(1, 20) as $day) {
                Attendance::create(['tenant_id' => $user->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('2026-09-%02d', $day), 'status' => 'present']);
            }
            app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);
        }

        return app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'MCPL/26-27/1');
    }

    public function test_particulars_run_from_the_highest_rank_to_the_lowest(): void
    {
        $summary = app(PdfExportService::class)->dutySummary($this->invoice());

        $this->assertSame(['Supervisor', 'Gunman', 'Security Guard Male', 'Helper'], $summary->pluck('rank')->all());
    }

    public function test_the_sac_column_has_the_code_only_and_the_letterhead_has_the_cin(): void
    {
        $invoice = $this->invoice();
        $service = app(PdfExportService::class);

        $html = view('pdf.invoice', [
            'invoice' => $invoice->loadMissing('client', 'lineItems.employee', 'tenant'),
            'dutySummary' => $service->dutySummary($invoice),
            'bonus' => 0,
            'amountInWords' => 'Words',
            'logoDataUri' => null,
        ])->render();

        $this->assertStringContainsString('CIN NO: U7499OR2019PTC030507', $html);
        $this->assertStringContainsString('998525', $html);
        $this->assertStringNotContainsString('Guard services', $html, 'no SAC description under the code');
        $this->assertStringNotContainsString('font-size:7px', $html);
    }

    public function test_the_invoice_box_fits_on_one_page(): void
    {
        $pdf = app(PdfExportService::class)->invoice($this->invoice())->output();

        $this->assertSame(1, preg_match_all('#/Type\s*/Page\b#', $pdf));
    }
}
