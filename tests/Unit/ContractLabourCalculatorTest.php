<?php

namespace Tests\Unit;

use App\Services\ContractLabourCalculator;
use Tests\TestCase;

/**
 * The contract-labour calculator must reproduce the client's own Wages sheet (Balmer Lawrie, September 2026)
 * figure for figure. The expected values in the fixture are the cached results of that workbook.
 */
class ContractLabourCalculatorTest extends TestCase
{
    private const MONEY = [
        'earn_wages', 'ot_amount', 'bonus', 'leave_wages', 'pmjjby', 'pmsby', 'gross', 'pf_employee', 'esic_employee',
        'total_deduction', 'net_salary', 'pf_employer', 'esic_employer', 'ppe', 'lwf', 'total_contribution', 'total_ctc',
        'service_charge', 'gross_amount', 'gst', 'amount_incl_gst',
    ];

    private function sheet(): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/contract_labour_sep_2026.json')), true);
    }

    public function test_every_row_of_the_clients_september_sheet_is_reproduced(): void
    {
        $sheet = $this->sheet();
        $calculator = new ContractLabourCalculator;

        foreach ($sheet['rows'] as $i => $row) {
            $figures = $calculator->calculate($row['rate'], $row['paid_days'], $row['ot_hours'], $sheet['month'], $sheet['year']);

            foreach (self::MONEY as $key) {
                $this->assertEqualsWithDelta($row[$key], $figures[$key], 0.0001, 'row '.($i + 1)." ({$row['category']}): {$key}");
            }
        }
    }

    public function test_the_column_totals_of_the_sheet_are_reproduced(): void
    {
        $sheet = $this->sheet();
        $calculator = new ContractLabourCalculator;
        $totals = [];

        foreach ($sheet['rows'] as $row) {
            foreach ($calculator->calculate($row['rate'], $row['paid_days'], $row['ot_hours'], $sheet['month'], $sheet['year']) as $key => $value) {
                $totals[$key] = ($totals[$key] ?? 0) + $value;
            }
        }

        foreach ($sheet['totals'] as $key => $expected) {
            $this->assertEqualsWithDelta($expected, $totals[$key], 0.001, "total {$key}");
        }
    }

    public function test_a_worked_row_matches_the_hand_calculation(): void
    {
        // FLO-Skilled: Rs 781 a day, 26 days, 38 overtime hours.
        $f = (new ContractLabourCalculator)->calculate(781, 26, 38, 9, 2026);

        $this->assertEqualsWithDelta(20306, $f['earn_wages'], 0.0001);
        $this->assertEqualsWithDelta(7419.5, $f['ot_amount'], 0.0001, 'overtime is paid at double the hourly rate');
        $this->assertEqualsWithDelta(1691.4898, $f['bonus'], 0.0001, 'bonus 8.33% of earned wages, no ceiling');
        $this->assertEqualsWithDelta(976.25, $f['leave_wages'], 0.0001);
        $this->assertSame(2097.0, $f['pf_employee'], 'PF is rounded to the rupee, on the blended EPF ceiling');
        $this->assertSame(2272.0, $f['pf_employer']);
        $this->assertEqualsWithDelta(207.94125, $f['esic_employee'], 0.0001, 'ESIC on earned wages plus overtime, no ceiling');
        $this->assertEqualsWithDelta(901.07875, $f['esic_employer'], 0.0001);
        $this->assertEqualsWithDelta(0, $f['ppe'], 0.0001, 'PPE is switched off, as in the sheet');
        $this->assertEqualsWithDelta(5, $f['lwf'], 0.0001);
    }

    public function test_zero_days_gives_zero_everywhere(): void
    {
        foreach ((new ContractLabourCalculator)->calculate(781, 0, 0, 9, 2026) as $key => $value) {
            $this->assertEquals(0, $value, $key);
        }
    }

    public function test_rates_can_be_overridden_per_client_without_touching_the_defaults(): void
    {
        $default = (new ContractLabourCalculator)->calculate(650, 25, 10, 10, 2026);
        $custom = (new ContractLabourCalculator)->calculate(650, 25, 10, 10, 2026, ['bonus_pct' => 0, 'service_charge_pct' => 5, 'ppe_per_month' => 750, 'ppe_enabled' => true]);

        $this->assertGreaterThan(0, $default['bonus']);
        $this->assertSame(0.0, $custom['bonus']);
        $this->assertEqualsWithDelta((750 / 26) * 25, $custom['ppe'], 0.0001);
        $this->assertSame(0.0, $default['ppe']);
        $this->assertNotEquals($default['service_charge'], $custom['service_charge']);
    }

    public function test_an_employee_not_covered_by_esic_or_pf_pays_none_of_that_scheme(): void
    {
        $f = (new ContractLabourCalculator)->calculate(781, 26, 10, 9, 2026, [], esicApplicable: false, pfApplicable: false);

        $this->assertSame(0.0, $f['esic_employee']);
        $this->assertSame(0.0, $f['esic_employer']);
        $this->assertSame(0.0, $f['pf_employee']);
        $this->assertSame(0.0, $f['pf_employer']);
        $this->assertEqualsWithDelta($f['gross'], $f['net_salary'], 0.0001);
    }

    public function test_the_epf_ceiling_follows_the_month(): void
    {
        $calc = new ContractLabourCalculator;

        // August 2026: ceiling 15,000 all month. October 2026: 25,000 all month.
        $this->assertSame(1800.0, $calc->calculate(1000, 26, 0, 8, 2026)['pf_employee'], '12% of Rs 15,000');
        $this->assertSame(3000.0, $calc->calculate(1000, 26, 0, 10, 2026)['pf_employee'], '12% of Rs 26,000 capped at 25,000');
    }
}
