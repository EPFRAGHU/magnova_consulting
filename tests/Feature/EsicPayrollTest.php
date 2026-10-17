<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EsicSetting;
use App\Models\Tenant;
use App\Models\Wage;
use App\Services\EsicCalculator;
use App\Services\InvoiceCalculator;
use App\Services\WageCalculator;
use App\Services\WageRegisterCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** ESIC through the whole payroll chain: wages, register, payslip figures and invoice. */
class EsicPayrollTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test']);
        $this->client = Client::create(['tenant_id' => $this->tenant->id, 'name' => 'Unit A', 'active' => true]);
    }

    private function employee(float $rate, array $attributes = []): Employee
    {
        static $n = 0;
        $n++;

        return Employee::create(array_merge([
            'tenant_id' => $this->tenant->id, 'client_id' => $this->client->id, 'employee_code' => 'P-'.$n,
            'name' => "Guard {$n}", 'skill_category' => 'skilled', 'rate_per_day' => $rate, 'status' => 'active',
        ], $attributes));
    }

    private function work(Employee $employee, int $month, int $year, int $days): void
    {
        for ($day = 1; $day <= $days; $day++) {
            Attendance::create([
                'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id,
                'date' => sprintf('%04d-%02d-%02d', $year, $month, $day), 'status' => 'present',
            ]);
        }
    }

    private function save(Employee $employee, int $month, int $year): Wage
    {
        return app(WageCalculator::class)->calculateForEmployee($employee->fresh('client'), $month, $year);
    }

    public function test_the_fix_an_employee_who_crossed_the_ceiling_in_the_period_still_pays_esic_on_actual_wages(): void
    {
        $employee = $this->employee(1000);
        $this->work($employee, 10, 2026, 20);   // Rs 20,000: covered
        $this->work($employee, 11, 2026, 22);   // Rs 22,000: crossed the ceiling
        $this->save($employee, 10, 2026);

        $wage = $this->save($employee, 11, 2026);

        $this->assertEquals(165, $wage->esic_employee, '0.75% of Rs 22,000, not zero');
        $this->assertTrue($wage->esic_snapshot['covered']);
        $this->assertSame('continuing_contribution_period', $wage->esic_snapshot['reason']);
        $this->assertEquals(22000, $wage->esic_snapshot['contribution_wages']);
        $this->assertEquals(715, $wage->esic_snapshot['employer_amount']);
        $this->assertEquals($wage->gross_wages - $wage->epf_employee - $wage->esic_employee + $wage->bonus, $wage->net_salary);
    }

    public function test_a_new_employee_above_the_ceiling_still_pays_no_esic(): void
    {
        $employee = $this->employee(1000);
        $this->work($employee, 11, 2026, 22);

        $wage = $this->save($employee, 11, 2026);

        $this->assertEquals(0, $wage->esic_employee);
        $this->assertFalse($wage->esic_snapshot['covered']);
    }

    public function test_an_employee_not_applicable_for_esic_pays_none_and_is_recorded_so(): void
    {
        $employee = $this->employee(700, ['esic_applicable' => false]);
        $this->work($employee, 10, 2026, 20);

        $wage = $this->save($employee, 10, 2026);

        $this->assertEquals(0, $wage->esic_employee);
        $this->assertSame('employee_not_applicable', $wage->esic_snapshot['reason']);
    }

    public function test_register_invoice_and_wages_agree_for_a_continuing_employee(): void
    {
        $employee = $this->employee(1000);
        $this->work($employee, 10, 2026, 20);
        $this->work($employee, 11, 2026, 22);
        $this->save($employee, 10, 2026);
        $wage = $this->save($employee, 11, 2026);

        $row = app(WageRegisterCalculator::class)->row($employee->fresh('client'), $wage, 11, 2026, 1);
        $invoice = app(InvoiceCalculator::class)->generateForClient($this->client, 11, 2026, 'INV-ESIC-1');

        $this->assertEquals($wage->esic_employee, $row['esic_employee']);
        $this->assertEquals(715, $row['esic_employer']);
        $this->assertEquals(715, $invoice->esi_employer);
        $this->assertEquals($row['total_gross_salary'], $invoice->taxable_value);
    }

    public function test_changing_the_settings_later_does_not_rewrite_a_saved_wage_or_its_invoice(): void
    {
        $employee = $this->employee(1000);
        $this->work($employee, 11, 2026, 20);
        $wage = $this->save($employee, 11, 2026);
        $invoice = app(InvoiceCalculator::class)->generateForClient($this->client, 11, 2026, 'INV-ESIC-2');
        $before = [$wage->esic_employee, $invoice->esi_employer, $invoice->grand_total];

        EsicSetting::create(['tenant_id' => $this->tenant->id, 'effective_from' => '2026-11-01', 'employee_rate' => 2, 'employer_rate' => 6] + EsicSetting::defaults());
        app(EsicCalculator::class)->flush();

        $regenerated = app(InvoiceCalculator::class)->generateForClient($this->client, 11, 2026, 'INV-ESIC-2');
        $row = app(WageRegisterCalculator::class)->row($employee->fresh('client'), $wage->fresh(), 11, 2026, 1);

        $this->assertEquals($before, [$wage->fresh()->esic_employee, $regenerated->esi_employer, $regenerated->grand_total]);
        $this->assertEquals($before[0], $row['esic_employee'], 'the register shows the finalised figure');

        // Saving the wages again is the explicit way to apply the new rules.
        $this->assertEquals(400, $this->save($employee, 11, 2026)->esic_employee);
    }

    public function test_months_before_the_current_rules_keep_the_old_calculation(): void
    {
        $employee = $this->employee(1000);
        $this->work($employee, 8, 2026, 26);

        $wage = $this->save($employee, 8, 2026);

        $this->assertEquals(157.5, $wage->esic_employee, 'August 2026 keeps MIN(wage, 21,000) x 0.75%');
        $this->assertNull($wage->esic_snapshot);
    }

    public function test_the_register_heading_no_longer_claims_esic_is_nil_above_the_ceiling(): void
    {
        $headings = app(WageRegisterCalculator::class)->headings(11, 2026, false, false, $this->tenant->id);

        $this->assertStringNotContainsString('nil if Monthly Wage above', $headings['esic']);
        $this->assertStringContainsString('3.25%', $headings['esic']);
        $this->assertStringContainsString('0.75%', $headings['esic_individual']);
        $this->assertStringContainsString('nil if Monthly Wage above Rs.21,000', $headings['bonus'], 'bonus keeps its own ceiling');
    }

    public function test_the_bonus_ceiling_is_independent_of_the_esic_ceiling(): void
    {
        EsicSetting::create(['tenant_id' => $this->tenant->id, 'effective_from' => '2026-10-01', 'coverage_ceiling' => 30000] + EsicSetting::defaults());
        $this->client->update(['bonus_enabled' => true]);
        $employee = $this->employee(1000);
        $this->work($employee, 11, 2026, 25);   // Rs 25,000: above the bonus ceiling, within the new ESIC ceiling

        $wage = $this->save($employee->fresh(), 11, 2026);

        $this->assertEquals(0, $wage->bonus);
        $this->assertEquals(188, $wage->esic_employee);
    }
}
