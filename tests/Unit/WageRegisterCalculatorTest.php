<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Employee;
use App\Services\WageRegisterCalculator;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WageRegisterCalculatorTest extends TestCase
{
    private function row(float $days, float $rate, int $month, int $year, bool $bonusEnabled = true, bool $serviceOnBasic = false, bool $gstExempt = false): array
    {
        $calculator = Mockery::mock(WageRegisterCalculator::class)->makePartial();
        $calculator->shouldReceive('workingDays')->andReturn($days);

        $employee = Employee::make(['rate_per_day' => $rate, 'skill_category' => 'skilled', 'name' => 'Test']);
        $employee->setRelation('client', new Client([
            'bonus_enabled' => $bonusEnabled,
            'service_charge_on_basic' => $serviceOnBasic,
            'gst_exempt' => $gstExempt,
        ]));

        return $calculator->row($employee, null, $month, $year, 1);
    }

    /**
     * Exact figures, to the paisa and not rounded to the rupee. Formulas follow REGISTER WAGES SEP 26 - revised.xlsx,
     * e.g. 12 x 1008: EPF 12% of 12,096 = 1,451.52, bonus 8.33% = 1,007.60, ESIC 3.25% of 12,096 = 393.12,
     * gross 15,069.20, GST 18% = 2,712.456 -> 2,712.46, agency 3.85% = 580.16.
     */
    public static function septemberCases(): array
    {
        return [
            'above 21,000 (26 x 1008)' => [26, 1008, [
                'monthly_wage' => 26208, 'epf_employer' => 2360, 'edli_and_epf_admin' => 196.67,
                'esic_employer' => 0, 'bonus' => 0, 'total_gross_salary' => 28764.67, 'gst' => 5177.64,
                'agency_fee' => 1107.44, 'total_bill_amount' => 35049.75, 'epf_employee' => 2360,
                'esic_employee' => 0, 'net_payable_salary' => 23848,
            ]],
            'below both ceilings (12 x 1008)' => [12, 1008, [
                'monthly_wage' => 12096, 'epf_employer' => 1451.52, 'edli_and_epf_admin' => 120.96,
                'esic_employer' => 393.12, 'bonus' => 1007.6, 'total_gross_salary' => 15069.2, 'gst' => 2712.46,
                'agency_fee' => 580.16, 'total_bill_amount' => 18361.82, 'epf_employee' => 1451.52,
                'esic_employee' => 90.72, 'net_payable_salary' => 11561.36,
            ]],
            'between 15,000 and 21,000 (22 x 918)' => [22, 918, [
                'monthly_wage' => 20196, 'epf_employer' => 2090.98, 'edli_and_epf_admin' => 174.25,
                'esic_employer' => 656.37, 'bonus' => 1682.33, 'total_gross_salary' => 24799.93, 'gst' => 4463.99,
                'agency_fee' => 954.8, 'total_bill_amount' => 30218.72, 'epf_employee' => 2090.98,
                'esic_employee' => 151.47, 'net_payable_salary' => 19635.88,
            ]],
            'single day (1 x 918)' => [1, 918, [
                'monthly_wage' => 918, 'epf_employer' => 110.16, 'edli_and_epf_admin' => 9.18,
                'esic_employer' => 29.84, 'bonus' => 76.47, 'total_gross_salary' => 1143.65, 'gst' => 205.86,
                'agency_fee' => 44.03, 'total_bill_amount' => 1393.54, 'epf_employee' => 110.16,
                'esic_employee' => 6.89, 'net_payable_salary' => 877.42,
            ]],
        ];
    }

    #[DataProvider('septemberCases')]
    public function test_september_2026_follows_the_revised_register(float $days, float $rate, array $expected): void
    {
        $row = $this->row($days, $rate, 9, 2026);

        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $row[$key], "september 2026: {$key}");
        }
    }

    public function test_september_blends_the_epf_ceiling_around_17_september(): void
    {
        $segments = (new WageRegisterCalculator)->ceilingSegments(9, 2026);

        $this->assertSame([
            ['ceiling' => 15000.0, 'from' => 1, 'to' => 16, 'days' => 16],
            ['ceiling' => 25000.0, 'from' => 17, 'to' => 30, 'days' => 14],
        ], $segments);
    }

    public function test_october_onwards_uses_the_higher_ceiling_all_month(): void
    {
        $calculator = new WageRegisterCalculator;

        $this->assertCount(1, $calculator->ceilingSegments(10, 2026));
        $this->assertStringContainsString('ceiling Rs.25,000', $calculator->headings(10, 2026)['epf']);
    }

    public function test_unit_with_bonus_off_gets_no_bonus_and_totals_follow(): void
    {
        $on = $this->row(12, 1008, 9, 2026, true);
        $off = $this->row(12, 1008, 9, 2026, false);

        $this->assertEquals(1007.6, $on['bonus']);
        $this->assertEquals(0, $off['bonus']);
        $this->assertEqualsWithDelta($on['net_payable_salary'] - 1007.6, $off['net_payable_salary'], 0.0001);
        $this->assertEqualsWithDelta($on['total_gross_salary'] - 1007.6, $off['total_gross_salary'], 0.0001);
        $this->assertEquals($on['epf_employee'], $off['epf_employee']);
        $this->assertEquals($on['esic_employer'], $off['esic_employer']);
    }

    public function test_service_charge_is_on_gross_by_default_and_on_basic_when_the_unit_says_so(): void
    {
        $gross = $this->row(12, 1008, 9, 2026, true, false);
        $basic = $this->row(12, 1008, 9, 2026, true, true);

        $this->assertEquals(580.16, $gross['agency_fee']);
        $this->assertEquals(round(12096 * 3.85 / 100, 2), $basic['agency_fee']);
        $this->assertEquals(465.7, $basic['agency_fee']);
        $this->assertEquals($gross['total_gross_salary'], $basic['total_gross_salary']);
        $this->assertEquals($gross['gst'], $basic['gst']);
        $this->assertEquals(
            $basic['total_gross_salary'] + $basic['gst'] + $basic['agency_fee'],
            $basic['total_bill_amount'],
        );
    }

    public function test_a_unit_without_gst_has_zero_gst_and_the_bill_is_gross_plus_agency_fee(): void
    {
        $charged = $this->row(12, 1008, 9, 2026);
        $exempt = $this->row(12, 1008, 9, 2026, true, false, true);

        $this->assertEquals(2712.46, $charged['gst']);
        $this->assertEquals(0, $exempt['gst']);
        $this->assertEquals($charged['total_gross_salary'], $exempt['total_gross_salary']);
        $this->assertEquals($charged['agency_fee'], $exempt['agency_fee']);
        $this->assertEquals($exempt['total_gross_salary'] + $exempt['agency_fee'], $exempt['total_bill_amount']);
        $this->assertEqualsWithDelta($charged['total_bill_amount'] - 2712.46, $exempt['total_bill_amount'], 0.0001);
        $this->assertEquals($charged['net_payable_salary'], $exempt['net_payable_salary']);
    }

    public function test_gst_heading_follows_the_unit(): void
    {
        $calculator = new WageRegisterCalculator;

        $this->assertSame('GST @ 18 % (K*0.18)', $calculator->headings(9, 2026, false, false)['gst']);
        $this->assertSame('GST (not charged to this unit)', $calculator->headings(9, 2026, false, true)['gst']);
        $this->assertStringContainsString('nil for units without GST', $calculator->headings(9, 2026, null, null)['gst']);
        $this->assertArrayHasKey('gst', $calculator->headings(8, 2026, false, true));
    }

    public function test_agency_heading_follows_the_unit_basis(): void
    {
        $calculator = new WageRegisterCalculator;

        $this->assertStringContainsString('Total Gross Salary (K*0.0385)', $calculator->headings(9, 2026, false)['agency']);
        $this->assertStringContainsString('Monthly Wage (F*0.0385)', $calculator->headings(9, 2026, true)['agency']);
        $this->assertStringContainsString('as set for the unit', $calculator->headings(9, 2026, null)['agency']);
        $this->assertArrayHasKey('agency', $calculator->headings(8, 2026, true));
    }

    public function test_bonus_off_also_applies_to_earlier_months_when_recalculated(): void
    {
        $this->assertEquals(0, $this->row(26, 1008, 8, 2026, false)['bonus']);
    }

    public function test_august_2026_keeps_the_earlier_logic(): void
    {
        $row = $this->row(26, 1008, 8, 2026);

        $this->assertEquals(1800, $row['epf_employer']);
        $this->assertEquals(1749.3, $row['bonus']);
        $this->assertEquals(682.5, $row['esic_employer']);
    }
}
