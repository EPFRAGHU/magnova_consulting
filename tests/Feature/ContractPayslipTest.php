<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\ContractWage;
use App\Models\Employee;
use App\Models\User;
use App\Models\Wage;
use App\Services\ContractPayslip;
use App\Services\ContractWageSheet;
use App\Services\PdfExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractPayslipTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $contract;

    private Client $standard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->tenant->update(['name' => 'Magnova Consulting Pvt. Ltd.', 'address' => 'Balianta, Bhubaneswar']);
        $this->contract = Client::create([
            'tenant_id' => $this->user->tenant_id, 'name' => 'Balmer Lawrie - Jatni', 'active' => true, 'payroll_profile' => 'contract_labour',
            'principal_employer_name' => 'Balmer Lawrie and Co. Ltd.',
        ]);
        $this->standard = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Plain unit', 'active' => true]);
    }

    private function guard(Client $client, string $name, string $skill = 'skilled'): Employee
    {
        static $n = 0;
        $n++;

        return Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'PS-'.$n, 'name' => $name,
            'skill_category' => $skill, 'rate_per_day' => 781, 'status' => 'active', 'designation' => 'FLO', 'uan' => '1000000000'.$n,
        ]);
    }

    /** 20 present days and 2 overtime hours for the month, then the wage sheet saved like the Payroll page does. */
    private function saveWages(int $month = 9, int $year = 2026, int $days = 20): void
    {
        foreach (Employee::where('client_id', $this->contract->id)->get() as $employee) {
            foreach (range(1, $days) as $d) {
                Attendance::create([
                    'tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'date' => sprintf('%04d-%02d-%02d', $year, $month, $d),
                    'status' => 'present', 'ot_hours' => $d === 1 ? 2 : 0,
                ]);
            }
        }

        app(ContractWageSheet::class)->save($this->contract, $month, $year);
    }

    private function filters(Client $client, int $month = 9, int $year = 2026): array
    {
        return ['client_id' => $client->id, 'month' => $month, 'year' => $year];
    }

    public function test_the_payslips_page_lists_the_saved_wage_lines_of_a_contract_labour_unit(): void
    {
        $this->guard($this->contract, 'Zed Unskilled', 'unskilled');
        $this->guard($this->contract, 'Amy Skilled');
        $this->saveWages();

        $this->actingAs($this->user)->get(route('payslips.index', $this->filters($this->contract)))
            ->assertInertia(fn ($page) => $page
                ->where('contract', true)
                ->has('wages', 2)
                ->where('wages.0.employee.name', 'Amy Skilled')
                ->where('wages.1.employee.name', 'Zed Unskilled')
                ->where('wages.0.id', ContractWage::where('employee_id', Employee::where('name', 'Amy Skilled')->value('id'))->value('id'))
                ->where('wages.0.net_salary', (string) ContractWage::first()->figures['net_salary']));
    }

    public function test_a_contract_unit_with_no_saved_wages_shows_an_empty_list(): void
    {
        $this->guard($this->contract, 'Amy');

        $this->actingAs($this->user)->get(route('payslips.index', $this->filters($this->contract)))
            ->assertInertia(fn ($page) => $page->where('contract', true)->has('wages', 0));
    }

    public function test_a_regular_unit_still_lists_its_regular_wages(): void
    {
        $employee = $this->guard($this->standard, 'Regular Rita');
        Wage::create([
            'tenant_id' => $this->user->tenant_id, 'employee_id' => $employee->id, 'month' => 9, 'year' => 2026, 'working_days' => 26, 'rate_per_day' => 600,
            'gross_wages' => 15600, 'bonus' => 0, 'epf_employee' => 1872, 'esic_employee' => 0, 'net_salary' => 13728,
        ]);

        $this->actingAs($this->user)->get(route('payslips.index', $this->filters($this->standard)))
            ->assertInertia(fn ($page) => $page->where('contract', false)->has('wages', 1)->where('wages.0.employee.name', 'Regular Rita'));
    }

    public function test_the_payslip_shows_the_saved_figures_and_the_net_in_words(): void
    {
        $this->guard($this->contract, 'Amy Skilled');
        $this->saveWages();
        $line = ContractWage::with('employee')->first();
        $pdf = app(PdfExportService::class);

        $html = view('pdf.contract-payslips', [
            'slips' => [['line' => $line, 'ytd' => app(ContractPayslip::class)->ytd($line), 'words' => 'Fifteen Thousand']],
            'headings' => app(ContractWageSheet::class)->headings($this->contract),
            'logoDataUri' => null,
        ])->render();

        foreach (['Payslip for the month of September 2026', 'Amy Skilled', 'Earned wages', 'Overtime', 'Leave wages', 'PMJJBY', 'PMSBY', 'PF 12%', 'ESIC 0.75%', 'Gross Earnings', 'Total Deductions', 'Fifteen Thousand', 'Balianta, Bhubaneswar'] as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        $this->assertStringContainsString(number_format($line->figures['earn_wages'], 2), $html);
        $this->assertStringContainsString(number_format($line->figures['net_salary'], 2), $html);
        $this->assertNotNull($pdf);
    }

    public function test_the_year_to_date_adds_the_financial_year_months_of_the_employee(): void
    {
        $this->guard($this->contract, 'Amy');
        $this->saveWages(8, 2026);
        $this->saveWages(9, 2026);
        $this->saveWages(3, 2026);   // previous financial year, must not count
        $sept = ContractWage::where('month', 9)->first();
        $aug = ContractWage::where('month', 8)->first();

        $ytd = app(ContractPayslip::class)->ytd($sept);

        $this->assertEqualsWithDelta($aug->figures['net_salary'] + $sept->figures['net_salary'], $ytd['net_salary'], 0.0001);
        $this->assertEqualsWithDelta($aug->figures['gross'] + $sept->figures['gross'], $ytd['gross'], 0.0001);
    }

    public function test_single_and_bulk_payslip_pdfs_download(): void
    {
        $this->guard($this->contract, 'Amy');
        $this->guard($this->contract, 'Bob');
        $this->saveWages();
        $line = ContractWage::first();

        $single = $this->actingAs($this->user)->get(route('payslips.contract.show', $line));
        $single->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $single->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $single->getContent());

        $bulk = $this->actingAs($this->user)->get(route('payslips.bulk', $this->filters($this->contract)));
        $bulk->assertOk();
        $this->assertStringStartsWith('%PDF', $bulk->getContent());
        $this->assertSame(2, preg_match_all('#/Type\s*/Page\b#', $bulk->getContent()), 'one page per guard');
    }

    public function test_bulk_for_a_contract_unit_without_saved_wages_is_not_found(): void
    {
        $this->guard($this->contract, 'Amy');

        $this->actingAs($this->user)->get(route('payslips.bulk', $this->filters($this->contract)))->assertNotFound();
    }
}
