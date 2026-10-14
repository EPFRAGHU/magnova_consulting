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
    private function row(float $days, float $rate, int $month, int $year, bool $bonusEnabled = true, bool $serviceOnBasic = false): array
    {
        $calculator = Mockery::mock(WageRegisterCalculator::class)->makePartial();
        $calculator->shouldReceive('workingDays')->andReturn($days);

        $employee = Employee::make(['rate_per_day' => $rate, 'skill_category' => 'skilled', 'name' => 'Test']);
        $employee->setRelation('client', new Client([
            'bonus_enabled' => $bonusEnabled,
            'service_charge_on_basic' => $serviceOnBasic,
        ]));

        return $calculator->row($employee, null, $month, $year, 1);
    }

    /** Expected figures follow the formulas in REGISTER WAGES SEP 26 - revised.xlsx. */
    public static function septemberCases(): array
    {
        return [
            'above 21,000 (26 x 1008)' => [26, 1008, [
                'monthly_wage' => 26208, 'epf_employer' => 2360, 'edli_and_epf_admin' => 197,
                'esic_employer' => 0, 'bonus' => 0, 'total_gross_salary' => 28765, 'gst' => 5178,
                'agency_fee' => 1107, 'total_bill_amount' => 35050, 'epf_employee' => 2360,
                'esic_employee' => 0, 'net_payable_salary' => 23848,
            ]],
            'below both ceilings (12 x 1008)' => [12, 1008, [
                'monthly_wage' => 12096, 'epf_employer' => 1452, 'edli_and_epf_admin' => 121,
                'esic_employer' => 393, 'bonus' => 1008, 'total_gross_salary' => 15070, 'gst' => 2713,
                'agency_fee' => 580, 'total_bill_amount' => 18363, 'epf_employee' => 1452,
                'esic_employee' => 91, 'net_payable_salary' => 11561,
            ]],
            'between 15,000 and 21,000 (22 x 918)' => [22, 918, [
                'monthly_wage' => 20196, 'epf_employer' => 2091, 'edli_and_epf_admin' => 174,
                'esic_employer' => 656, 'bonus' => 1682, 'total_gross_salary' => 24799, 'gst' => 4464,
                'agency_fee' => 955, 'total_bill_amount' => 30218, 'epf_employee' => 2091,
                'esic_employee' => 151, 'net_payable_salary' => 19636,
            ]],
            'single day (1 x 918)' => [1, 918, [
                'monthly_wage' => 918, 'epf_employer' => 110, 'edli_and_epf_admin' => 9,
                'esic_employer' => 30, 'bonus' => 76, 'total_gross_salary' => 1143, 'gst' => 206,
                'agency_fee' => 44, 'total_bill_amount' => 1393, 'epf_employee' => 110,
                'esic_employee' => 7, 'net_payable_salary' => 877,
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

        $this->assertEquals(1008, $on['bonus']);
        $this->assertEquals(0, $off['bonus']);
        $this->assertEquals($on['net_payable_salary'] - 1008, $off['net_payable_salary']);
        $this->assertEquals($on['total_gross_salary'] - 1008, $off['total_gross_salary']);
        $this->assertEquals($on['epf_employee'], $off['epf_employee']);
        $this->assertEquals($on['esic_employer'], $off['esic_employer']);
    }

    public function test_service_charge_is_on_gross_by_default_and_on_basic_when_the_unit_says_so(): void
    {
        $gross = $this->row(12, 1008, 9, 2026, true, false);
        $basic = $this->row(12, 1008, 9, 2026, true, true);

        $this->assertEquals(580, $gross['agency_fee']);
        $this->assertEquals(round(12096 * 3.85 / 100), $basic['agency_fee']);
        $this->assertEquals(466, $basic['agency_fee']);
        $this->assertEquals($gross['total_gross_salary'], $basic['total_gross_salary']);
        $this->assertEquals($gross['gst'], $basic['gst']);
        $this->assertEquals(
            $basic['total_gross_salary'] + $basic['gst'] + $basic['agency_fee'],
            $basic['total_bill_amount'],
        );
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
