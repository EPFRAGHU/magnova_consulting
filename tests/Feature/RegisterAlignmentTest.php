<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Wage;
use App\Services\InvoiceCalculator;
use App\Services\PdfExportService;
use App\Services\WageCalculator;
use App\Services\WageRegisterCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterAlignmentTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Client, 1: array<int, Employee>} */
    private function setUpUnit(int $month, int $year, bool $bonusEnabled = true, bool $serviceOnBasic = false, bool $gstExempt = false): array
    {
        $tenant = Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test']);
        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Unit A',
            'active' => true,
            'bonus_enabled' => $bonusEnabled,
            'service_charge_on_basic' => $serviceOnBasic,
            'gst_exempt' => $gstExempt,
        ]);
        $employees = [];

        foreach ([['Alpha', 1008, 26], ['Bravo', 1008, 12], ['Charlie', 918, 22]] as $i => [$name, $rate, $days]) {
            $employee = Employee::create([
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'employee_code' => 'T-'.$i,
                'name' => $name,
                'skill_category' => 'skilled',
                'rate_per_day' => $rate,
                'status' => 'active',
            ]);

            for ($day = 1; $day <= $days; $day++) {
                Attendance::create([
                    'tenant_id' => $tenant->id,
                    'employee_id' => $employee->id,
                    'date' => sprintf('%04d-%02d-%02d', $year, $month, $day),
                    'status' => 'present',
                ]);
            }

            $employees[] = $employee;
        }

        return [$client, $employees];
    }

    public function test_saved_wages_for_september_2026_match_the_register(): void
    {
        [, $employees] = $this->setUpUnit(9, 2026);
        $register = app(WageRegisterCalculator::class);

        foreach ($employees as $employee) {
            $wage = app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);
            $row = $register->row($employee, null, 9, 2026, 1);

            $this->assertEquals($row['monthly_wage'], $wage->gross_wages);
            $this->assertEquals($row['bonus'], $wage->bonus);
            $this->assertEquals($row['epf_employee'], $wage->epf_employee);
            $this->assertEquals($row['esic_employee'], $wage->esic_employee);
            $this->assertEquals($row['net_payable_salary'], $wage->net_salary);
        }

        $first = Wage::where('employee_id', $employees[0]->id)->first();
        $this->assertEquals(23848, $first->net_salary);
        $this->assertEquals(0, $first->bonus);
    }

    public function test_invoice_for_september_2026_equals_the_register_totals(): void
    {
        [$client, $employees] = $this->setUpUnit(9, 2026);
        $register = app(WageRegisterCalculator::class);

        foreach ($employees as $employee) {
            app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);
        }

        $invoice = app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'INV-TEST-1');
        $rows = collect($employees)->map(fn (Employee $e) => $register->row($e, null, 9, 2026, 1));

        $this->assertEquals($rows->sum('monthly_wage'), $invoice->base_amount);
        $this->assertEquals($rows->sum('epf_employer'), $invoice->epf_employer);
        $this->assertEquals($rows->sum('edli_and_epf_admin'), $invoice->epf_admin + $invoice->edli_cont);
        $this->assertEquals($rows->sum('esic_employer'), $invoice->esi_employer);
        $this->assertEquals($rows->sum('total_gross_salary'), $invoice->taxable_value);
        $this->assertEquals($rows->sum('agency_fee'), $invoice->service_charge);
        $this->assertEquals($rows->sum('gst'), $invoice->cgst + $invoice->sgst);
        $this->assertEquals($rows->sum('total_bill_amount'), $invoice->grand_total);

        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->invoice($invoice)->output());
    }

    public function test_service_charge_on_basic_matches_the_register_for_september(): void
    {
        [$client, $employees] = $this->setUpUnit(9, 2026, true, true);
        $register = app(WageRegisterCalculator::class);

        foreach ($employees as $employee) {
            app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);
        }

        $invoice = app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'INV-TEST-BASIC');
        $rows = collect($employees)->map(fn (Employee $e) => $register->row($e, null, 9, 2026, 1));

        $this->assertEquals($rows->sum('agency_fee'), $invoice->service_charge);
        $this->assertEquals($rows->sum(fn ($r) => round($r['monthly_wage'] * 3.85 / 100)), $invoice->service_charge);
        $this->assertEquals($rows->sum('total_bill_amount'), $invoice->grand_total);
        $this->assertEquals(
            $invoice->taxable_value + $invoice->service_charge + $invoice->cgst + $invoice->sgst,
            $invoice->grand_total,
        );
    }

    public function test_a_unit_without_gst_gets_an_invoice_with_zero_cgst_and_sgst(): void
    {
        [$client, $employees] = $this->setUpUnit(9, 2026, true, false, true);
        $register = app(WageRegisterCalculator::class);

        foreach ($employees as $employee) {
            app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);
        }

        $invoice = app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'INV-TEST-NOGST');
        $rows = collect($employees)->map(fn (Employee $e) => $register->row($e, null, 9, 2026, 1));

        $this->assertEquals(0, $invoice->cgst);
        $this->assertEquals(0, $invoice->sgst);
        $this->assertEquals($invoice->taxable_value + $invoice->service_charge, $invoice->grand_total);
        $this->assertEquals($rows->sum('total_bill_amount'), $invoice->grand_total);
        $this->assertEquals(0, $rows->sum('gst'));
        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->invoice($invoice)->output());
    }

    public function test_the_gst_setting_also_applies_to_months_before_september_2026(): void
    {
        [$client, $employees] = $this->setUpUnit(8, 2026, true, false, false);
        foreach ($employees as $employee) {
            app(WageCalculator::class)->calculateForEmployee($employee, 8, 2026);
        }
        $withGst = app(InvoiceCalculator::class)->generateForClient($client, 8, 2026, 'INV-AUG-GST');
        $this->assertGreaterThan(0, (float) $withGst->cgst);

        $client->update(['gst_exempt' => true]);
        $noGst = app(InvoiceCalculator::class)->generateForClient($client->fresh(), 8, 2026, 'INV-AUG-GST');

        $this->assertEquals(0, $noGst->cgst);
        $this->assertEquals(0, $noGst->sgst);
        $this->assertEquals($noGst->taxable_value + $noGst->service_charge, $noGst->grand_total);
    }

    public function test_service_charge_basis_also_applies_to_months_before_september_2026(): void
    {
        [$gross, $grossEmployees] = $this->setUpUnit(8, 2026, true, false);
        foreach ($grossEmployees as $employee) {
            app(WageCalculator::class)->calculateForEmployee($employee, 8, 2026);
        }
        $onGross = app(InvoiceCalculator::class)->generateForClient($gross, 8, 2026, 'INV-AUG-GROSS');

        $gross->update(['service_charge_on_basic' => true]);
        $onBasic = app(InvoiceCalculator::class)->generateForClient($gross->fresh(), 8, 2026, 'INV-AUG-GROSS');

        $this->assertEquals(round($onGross->taxable_value * 3.85 / 100, 2), $onGross->service_charge);
        $this->assertEquals(round($onBasic->base_amount * 3.85 / 100, 2), $onBasic->service_charge);
        $this->assertLessThan($onGross->service_charge, $onBasic->service_charge);
    }

    public function test_unit_with_bonus_off_has_no_bonus_in_wages_or_invoice(): void
    {
        [$client, $employees] = $this->setUpUnit(9, 2026, false);

        foreach ($employees as $employee) {
            $wage = app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);
            $this->assertEquals(0, $wage->bonus);
            $this->assertEquals($wage->gross_wages - $wage->epf_employee - $wage->esic_employee, $wage->net_salary);
        }

        $invoice = app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'INV-TEST-OFF');

        $this->assertEquals(
            $invoice->base_amount + $invoice->epf_employer + $invoice->epf_admin + $invoice->edli_cont + $invoice->esi_employer,
            $invoice->taxable_value,
        );
        $this->assertStringNotContainsString('Bonus @', $this->pdfText($invoice));
    }

    public function test_recalculation_applies_the_setting_to_saved_months_but_leaves_sent_invoices(): void
    {
        [$client, $employees] = $this->setUpUnit(9, 2026, true);

        foreach ($employees as $employee) {
            app(WageCalculator::class)->calculateForEmployee($employee, 9, 2026);
        }

        $invoice = app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'INV-TEST-DRAFT');
        $withBonus = $invoice->grand_total;
        $this->assertGreaterThan(0, Wage::sum('bonus'));

        $client->update(['bonus_enabled' => false]);
        $this->artisan('wages:apply-bonus-setting')->assertSuccessful();

        $this->assertEquals(0, Wage::sum('bonus'));
        $this->assertLessThan($withBonus, $invoice->fresh()->grand_total);

        $client->update(['bonus_enabled' => true]);
        $sent = $invoice->fresh();
        $sent->update(['status' => 'sent']);
        $sentTotal = $sent->grand_total;

        $client->update(['bonus_enabled' => false]);
        $this->artisan('wages:apply-bonus-setting')->assertSuccessful();

        $this->assertEquals($sentTotal, $sent->fresh()->grand_total);
    }

    private function pdfText(Invoice $invoice): string
    {
        $html = view('pdf.invoice', [
            'invoice' => $invoice->loadMissing('client', 'lineItems.employee', 'tenant'),
            'dutySummary' => collect(),
            'bonus' => 0,
            'amountInWords' => '',
            'logoDataUri' => null,
        ])->render();

        return $html;
    }

    public function test_august_2026_wages_keep_the_earlier_logic(): void
    {
        [, $employees] = $this->setUpUnit(8, 2026);

        $wage = app(WageCalculator::class)->calculateForEmployee($employees[0], 8, 2026);

        $this->assertEquals(26208, $wage->gross_wages);
        $this->assertEquals(1749.3, $wage->bonus);
        $this->assertEquals(1800, $wage->epf_employee);
    }
}
