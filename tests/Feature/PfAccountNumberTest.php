<?php

namespace Tests\Feature;

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

class PfAccountNumberTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function guard(?string $memberId): Employee
    {
        static $n = 0;
        $n++;
        $client = Client::firstOrCreate(
            ['tenant_id' => $this->user->tenant_id, 'name' => 'Unit'],
            ['active' => true, 'payroll_profile' => 'contract_labour', 'principal_employer_name' => 'Balmer Lawrie'],
        );

        return Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'PF-'.$n, 'name' => "Guard {$n}",
            'skill_category' => 'skilled', 'rate_per_day' => 781, 'status' => 'active', 'epf_member_id' => $memberId,
        ]);
    }

    private function setCode(?string $code): void
    {
        $this->user->tenant->update(['pf_establishment_code' => $code]);
    }

    public function test_the_member_id_is_added_to_the_establishment_code_without_a_separator(): void
    {
        $this->setCode('ORBBS2012397000');

        $this->assertSame('ORBBS20123970000001234', $this->guard('0001234')->fresh()->pfAccountNumber());
        $this->assertSame('ORBBS20123970000001234', $this->guard('1234')->fresh()->pfAccountNumber(), 'short IDs are padded to 7 digits');
    }

    public function test_a_full_number_or_another_format_is_printed_as_typed(): void
    {
        $this->setCode('ORBBS2012397000');

        $this->assertSame('ORBBS20123970000001234', $this->guard('ORBBS20123970000001234')->fresh()->pfAccountNumber());
        $this->assertSame('ORBBS2012397000', $this->guard('ORBBS2012397000')->fresh()->pfAccountNumber(), 'only the code was saved: shown as saved');
        $this->assertSame('OR/BBS/0012345/000/001', $this->guard('OR/BBS/0012345/000/001')->fresh()->pfAccountNumber());
    }

    public function test_without_a_code_or_member_id_nothing_is_invented(): void
    {
        $this->assertSame('0001234', $this->guard('0001234')->fresh()->pfAccountNumber(), 'no establishment code saved yet');

        $this->setCode('ORBBS2012397000');
        $this->assertNull($this->guard(null)->fresh()->pfAccountNumber());
        $this->assertNull($this->guard('  ')->fresh()->pfAccountNumber());
    }

    public function test_both_payslip_layouts_print_the_full_number(): void
    {
        $this->setCode('ORBBS2012397000');
        $employee = $this->guard('0001234');
        $contract = Client::where('name', 'Unit')->first();

        // Contract-labour payslip
        ContractWage::create([
            'tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'client_id' => $contract->id, 'month' => 9, 'year' => 2026,
            'category' => 'FLO - Skilled', 'rate_per_day' => 781, 'paid_days' => 20, 'ot_hours' => 0,
            'figures' => array_fill_keys(ContractPayslip::FIGURES, 1.0), 'parameters' => [], 'calculated_at' => now(),
        ]);
        $line = ContractWage::with('employee')->first();
        $html = view('pdf.contract-payslips', [
            'slips' => [['line' => $line, 'ytd' => app(ContractPayslip::class)->ytd($line), 'words' => 'One']],
            'headings' => app(ContractWageSheet::class)->headings($contract),
            'logoDataUri' => null,
        ])->render();
        $this->assertStringContainsString('ORBBS20123970000001234', $html);

        // Regular payslip
        $wage = Wage::create([
            'tenant_id' => $employee->tenant_id, 'employee_id' => $employee->id, 'month' => 9, 'year' => 2026, 'working_days' => 20, 'rate_per_day' => 781,
            'gross_wages' => 15620, 'bonus' => 0, 'epf_employee' => 1874.4, 'esic_employee' => 0, 'net_salary' => 13745.6,
        ]);
        $pdf = app(PdfExportService::class)->payslip($wage)->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_the_code_is_saved_uppercased_in_establishment_settings_and_checked(): void
    {
        $this->actingAs($this->user)->patch(route('establishment.update'), ['name' => 'Magnova', 'pf_establishment_code' => 'orbbs2012397000'])
            ->assertSessionHasNoErrors();
        $this->assertSame('ORBBS2012397000', $this->user->tenant->fresh()->pf_establishment_code);

        $this->actingAs($this->user)->patch(route('establishment.update'), ['name' => 'Magnova', 'pf_establishment_code' => 'OR/BBS 123'])
            ->assertSessionHasErrors('pf_establishment_code');

        $this->actingAs($this->user)->get(route('establishment.edit'))
            ->assertInertia(fn ($page) => $page->where('establishment.pf_establishment_code', 'ORBBS2012397000'));
    }
}
