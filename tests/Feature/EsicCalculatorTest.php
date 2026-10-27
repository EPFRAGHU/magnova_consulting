<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EsicSetting;
use App\Models\Tenant;
use App\Models\Wage;
use App\Services\EsicCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ESIC rules under test (Employees' State Insurance Act, 1948 and its Central Rules):
 *  - coverage ceiling Rs 21,000 a month decides who becomes covered;
 *  - an employee covered during a contribution period (Apr-Sep, Oct-Mar) stays covered to its end
 *    even if wages cross the ceiling, and contributions are then on the actual wages;
 *  - employee share 0.75%, employer share 3.25%.
 */
class EsicCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test']);
    }

    private function employee(array $attributes = []): Employee
    {
        static $n = 0;
        $n++;
        $client = Client::firstOrCreate(['tenant_id' => $this->tenant->id, 'name' => 'Unit A'], ['active' => true]);

        return Employee::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'client_id' => $client->id,
            'employee_code' => 'E-'.$n,
            'name' => "Guard {$n}",
            'skill_category' => 'skilled',
            'rate_per_day' => 800,
            'status' => 'active',
        ], $attributes));
    }

    private function savedWage(Employee $employee, int $month, int $year, float $gross, ?array $snapshot = null): Wage
    {
        return Wage::create([
            'tenant_id' => $this->tenant->id, 'employee_id' => $employee->id, 'month' => $month, 'year' => $year,
            'working_days' => 26, 'rate_per_day' => 800, 'gross_wages' => $gross, 'bonus' => 0,
            'epf_employee' => 0, 'esic_employee' => 0, 'net_salary' => $gross, 'esic_snapshot' => $snapshot,
        ]);
    }

    private function calc(float $wages, int $month, int $year, ?Employee $employee = null, float $bonus = 0.0, ?Wage $saved = null)
    {
        return app(EsicCalculator::class)->calculate($wages, $month, $year, $employee ?? $this->employee(), $bonus, $saved);
    }

    public static function newEmployeeWages(): array
    {
        return [
            'Rs 15,000' => [15000, true, 15000, 112.5, 487.5],
            'Rs 20,000' => [20000, true, 20000, 150, 650],
            'Rs 21,000 is still covered' => [21000, true, 21000, 157.5, 682.5],
            'Rs 22,000 is above the ceiling' => [22000, false, 0, 0, 0],
            'Rs 30,000 is above the ceiling' => [30000, false, 0, 0, 0],
        ];
    }

    #[DataProvider('newEmployeeWages')]
    public function test_new_employee_is_covered_only_when_wages_are_within_the_ceiling(float $wages, bool $covered, float $contributionWages, float $employee, float $employer): void
    {
        $result = $this->calc($wages, 10, 2026);

        $this->assertSame($covered, $result->covered);
        $this->assertEquals($contributionWages, $result->contributionWages);
        $this->assertEquals($employee, $result->employeeAmount);
        $this->assertEquals($employer, $result->employerAmount);
    }

    public static function continuingEmployeeWages(): array
    {
        return [
            'Rs 22,000' => [22000, 22000, 165, 715],
            'Rs 30,000' => [30000, 30000, 225, 975],
        ];
    }

    #[DataProvider('continuingEmployeeWages')]
    public function test_employee_who_crosses_the_ceiling_in_a_period_is_charged_on_the_actual_wages(float $wages, float $contributionWages, float $employeeShare, float $employerShare): void
    {
        $employee = $this->employee();
        $this->savedWage($employee, 10, 2026, 20500);

        $result = $this->calc($wages, 11, 2026, $employee);

        $this->assertTrue($result->covered);
        $this->assertSame('continuing_contribution_period', $result->reason);
        $this->assertEquals($contributionWages, $result->contributionWages, 'not capped at the ceiling');
        $this->assertEquals($employeeShare, $result->employeeAmount);
        $this->assertEquals($employerShare, $result->employerAmount);
    }

    public function test_crossing_the_ceiling_in_the_april_to_september_period_uses_earlier_months_of_that_period(): void
    {
        $employee = $this->employee();
        $this->savedWage($employee, 4, 2027, 20000);

        $this->assertTrue($this->calc(23000, 9, 2027, $employee)->covered);
    }

    public function test_the_contribution_period_across_the_new_year_runs_october_to_march(): void
    {
        $employee = $this->employee();
        $this->savedWage($employee, 11, 2026, 20000);

        $this->assertTrue($this->calc(24000, 2, 2027, $employee)->covered, 'Feb 2027 is still the Oct 2026 - Mar 2027 period');
    }

    public function test_a_new_contribution_period_starts_afresh(): void
    {
        $employee = $this->employee();
        $this->savedWage($employee, 8, 2027, 20000);

        $this->assertTrue($this->calc(22000, 9, 2027, $employee)->covered, 'same period (Apr-Sep)');
        $this->assertFalse($this->calc(22000, 10, 2027, $employee)->covered, 'October starts a new period; wages are above the ceiling');
    }

    public function test_an_employee_who_joins_above_the_ceiling_is_not_covered_even_with_later_months(): void
    {
        $employee = $this->employee();
        $this->savedWage($employee, 10, 2026, 25000);

        $this->assertFalse($this->calc(26000, 11, 2026, $employee)->covered);
    }

    public function test_an_employee_whose_wages_fall_back_within_the_ceiling_is_covered_for_the_rest_of_the_period(): void
    {
        $employee = $this->employee();
        $this->savedWage($employee, 10, 2026, 25000);
        $this->savedWage($employee, 11, 2026, 19000);

        $this->assertTrue($this->calc(23000, 12, 2026, $employee)->covered);
    }

    public function test_earlier_covered_snapshot_counts_as_evidence_even_when_the_wage_was_above_the_ceiling(): void
    {
        $employee = $this->employee();
        $this->savedWage($employee, 10, 2026, 23000, ['covered' => true]);

        $this->assertTrue($this->calc(24000, 11, 2026, $employee)->covered);
    }

    public function test_a_manually_recorded_coverage_start_inside_the_period_counts_as_evidence(): void
    {
        $employee = $this->employee(['esic_covered_from' => '2026-10-05']);

        $this->assertTrue($this->calc(22000, 11, 2026, $employee)->covered);
        $this->assertFalse($this->calc(22000, 4, 2027, $employee)->covered, 'a coverage start in an earlier period does not carry over');
    }

    public function test_employee_marked_not_applicable_has_no_esic_at_any_wage(): void
    {
        $employee = $this->employee(['esic_applicable' => false]);

        foreach ([10000, 15000, 21000] as $wages) {
            $result = $this->calc($wages, 10, 2026, $employee);
            $this->assertFalse($result->covered);
            $this->assertSame('employee_not_applicable', $result->reason);
            $this->assertEquals(0, $result->employeeAmount + $result->employerAmount);
        }
    }

    public function test_establishment_not_registered_means_no_esic_for_anyone(): void
    {
        EsicSetting::create(['tenant_id' => $this->tenant->id, 'effective_from' => '2026-10-01', 'establishment_covered' => false] + EsicSetting::defaults());

        $result = $this->calc(15000, 10, 2026);

        $this->assertFalse($result->covered);
        $this->assertSame('establishment_not_covered', $result->reason);
    }

    public function test_daily_wage_at_or_below_the_threshold_pays_no_employee_share_but_the_employer_still_pays(): void
    {
        $employee = $this->employee(['rate_per_day' => 176]);

        $result = $this->calc(4576, 10, 2026, $employee);

        $this->assertTrue($result->covered);
        $this->assertEquals(0, $result->employeeAmount);
        $this->assertEquals(148.72, $result->employerAmount);
    }

    public function test_capped_mode_is_separate_clearly_labelled_and_caps_the_contribution_wages(): void
    {
        EsicSetting::create(['tenant_id' => $this->tenant->id, 'effective_from' => '2026-10-01', 'calculation_mode' => 'capped'] + EsicSetting::defaults());

        $result = $this->calc(30000, 10, 2026);

        $this->assertTrue($result->covered);
        $this->assertSame('capped', $result->mode);
        $this->assertEquals(21000, $result->contributionWages);
        $this->assertEquals(157.5, $result->employeeAmount);
        $this->assertEquals(682.5, $result->employerAmount);
        $this->assertStringContainsString('NOT statutory', EsicSetting::modeLabel('capped'));
    }

    public function test_statutory_is_the_default_mode_when_nothing_is_configured(): void
    {
        $this->assertSame('statutory', $this->calc(15000, 10, 2026)->mode);
    }

    public function test_rates_ceiling_and_rounding_come_from_the_settings(): void
    {
        EsicSetting::create(['tenant_id' => $this->tenant->id, 'effective_from' => '2026-10-01', 'coverage_ceiling' => 25000, 'employee_rate' => 1, 'employer_rate' => 4, 'rounding' => 'up'] + EsicSetting::defaults());

        $result = $this->calc(22000.5, 10, 2026);

        $this->assertTrue($result->covered, 'the ceiling is read from the settings, not fixed in code');
        $this->assertEquals(221, $result->employeeAmount, '1% of 22,000.5 rounded up');
        $this->assertEquals(881, $result->employerAmount, '4% of 22,000.5 rounded up');
    }

    public function test_settings_apply_from_their_effective_date_and_older_months_keep_the_older_settings(): void
    {
        EsicSetting::create(['tenant_id' => $this->tenant->id, 'effective_from' => '2027-04-01', 'coverage_ceiling' => 30000] + EsicSetting::defaults());

        $this->assertFalse($this->calc(25000, 3, 2027)->covered, 'March 2027 still uses the default Rs 21,000');
        $this->assertTrue($this->calc(25000, 4, 2027)->covered, 'April 2027 uses the new ceiling');
    }

    public function test_the_wage_components_setting_decides_whether_bonus_counts_as_esic_wages(): void
    {
        $this->assertEquals(20000, $this->calc(20000, 10, 2026, null, 1000)->contributionWages, 'bonus excluded by default');

        EsicSetting::create(['tenant_id' => $this->tenant->id, 'effective_from' => '2026-10-01', 'include_bonus' => true] + EsicSetting::defaults());
        app(EsicCalculator::class)->flush();

        $this->assertEquals(21000, $this->calc(20000, 10, 2026, null, 1000)->contributionWages);
    }

    public function test_a_finalised_wage_keeps_its_snapshot_when_the_settings_change_later(): void
    {
        $employee = $this->employee();
        $first = $this->calc(20000, 10, 2026, $employee);
        $wage = $this->savedWage($employee, 10, 2026, 20000, $first->snapshot());

        EsicSetting::create(['tenant_id' => $this->tenant->id, 'effective_from' => '2026-10-01', 'employee_rate' => 5, 'employer_rate' => 10] + EsicSetting::defaults());
        app(EsicCalculator::class)->flush();

        $again = $this->calc(20000, 10, 2026, $employee, 0.0, $wage->fresh());
        $this->assertEquals(150, $again->employeeAmount, 'snapshot wins');
        $this->assertEquals(650, $again->employerAmount);
        $this->assertSame(0.75, $again->snapshot()['employee_rate']);

        $fresh = $this->calc(20000, 10, 2026, $employee);
        $this->assertEquals(1000, $fresh->employeeAmount, 'a brand new calculation sees the new settings');
    }

    public function test_the_snapshot_records_the_rule_decision_and_the_settings_used(): void
    {
        $snapshot = $this->calc(22000, 10, 2026)->snapshot();

        foreach (['mode', 'coverage_ceiling', 'employee_rate', 'employer_rate', 'rounding', 'covered', 'reason', 'contribution_wages', 'employee_amount', 'employer_amount', 'effective_from'] as $key) {
            $this->assertArrayHasKey($key, $snapshot);
        }
        $this->assertFalse($snapshot['covered']);
        $this->assertSame('above_ceiling_not_covered', $snapshot['reason']);
    }
}
