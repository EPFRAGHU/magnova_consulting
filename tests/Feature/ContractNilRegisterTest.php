<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Services\ContractNilRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractNilRegisterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $contract;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->tenant->update(['name' => 'Magnova Consulting Pvt. Ltd.']);
        $this->contract = Client::create([
            'tenant_id' => $this->user->tenant_id, 'name' => 'Balmer Lawrie - Jatni', 'active' => true, 'payroll_profile' => 'contract_labour',
            'principal_employer_name' => 'Balmer Lawrie and Co. Ltd.', 'principal_employer_address' => 'Jatni, Khordha',
            'contract_start' => '2026-09-01', 'contract_end' => '2029-08-30', 'register_contractor_address' => 'Sisupalgarh, Bhubaneswar - 751020',
        ]);
    }

    private function guard(string $name, string $status = 'active', ?Client $client = null): Employee
    {
        static $n = 0;
        $n++;

        return Employee::create([
            'tenant_id' => $this->user->tenant_id, 'client_id' => ($client ?? $this->contract)->id, 'employee_code' => 'NR-'.$n, 'name' => $name,
            'skill_category' => 'skilled', 'rate_per_day' => 781, 'status' => $status, 'designation' => 'FLO',
        ]);
    }

    private function html(string $kind): string
    {
        return app(ContractNilRegister::class)->view($kind, $this->contract, 9, 2026)->render();
    }

    public function test_the_fines_register_has_the_clients_headings_columns_and_a_nil_line_per_workman(): void
    {
        $this->guard('RAMESH MOHAPATRA');
        $this->guard('ARUN SAINI');

        $html = $this->html('fines');

        foreach ([
            'CONTRACTOR NAME - MAGNOVA CONSULTING PVT. LTD', 'SISUPALGARH', 'PRINCIPAL EMPLOYER- BALMER LAWRIE AND CO. LTD.', 'Register of Fines',
            'Contract Period - 01.09.2026 To 30.08.2029', 'Month - September,2026', 'Name of workman', "Father&#039;s/ Husband&#039;s name",
            'Act/Omission for which fine imposed', 'Date of offence', 'Whether workman showed cause against fine', 'Wage periods and wages payable',
            'Amount of fine imposed', 'Date on which fine realized', 'Remarks', 'RAMESH MOHAPATRA', 'ARUN SAINI',
        ] as $text) {
            $this->assertStringContainsStringIgnoringCase($text, $html, $text);
        }
        $this->assertSame(2, substr_count($html, '>NIL<'));
        $this->assertStringContainsString('<td>12</td>', $html, '12 numbered columns');
        $this->assertStringNotContainsString('<td>13</td>', $html);
    }

    public function test_the_deductions_register_has_its_own_title_and_thirteen_columns(): void
    {
        $this->guard('RAMESH MOHAPATRA');

        $html = $this->html('deductions');

        foreach ([
            'Register of deductions for damage or loss', 'Particulars of damage or loss', 'Date of damage or loss', 'Amount of deduction imposed',
            'No. of installments', 'Date of recovery', 'First installment', 'Last installment', 'Remarks',
        ] as $text) {
            $this->assertStringContainsStringIgnoringCase($text, $html, $text);
        }
        $this->assertStringNotContainsString('Register of Fines', $html);
        $this->assertStringContainsString('<td>13</td>', $html);
        $this->assertSame(1, substr_count($html, '>NIL<'));
    }

    public function test_only_active_workmen_of_the_unit_are_listed_in_register_order(): void
    {
        $this->guard('FIRST');
        $this->guard('LEFT', 'left');
        $this->guard('OTHER UNIT', 'active', Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Plain', 'active' => true]));
        $this->guard('SECOND');

        $workmen = app(ContractNilRegister::class)->workmen($this->contract);

        $this->assertSame([['serial' => 1, 'name' => 'FIRST'], ['serial' => 2, 'name' => 'SECOND']], $workmen);
    }

    public function test_both_pdfs_download_and_a_regular_unit_or_unknown_kind_is_refused(): void
    {
        $this->guard('RAMESH MOHAPATRA');
        $query = ['client_id' => $this->contract->id, 'month' => 9, 'year' => 2026];

        foreach (['fines', 'deductions'] as $kind) {
            $response = $this->actingAs($this->user)->get(route('registers.contract.nil', ['kind' => $kind] + $query));
            $response->assertOk();
            $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }

        $plain = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Plain', 'active' => true]);
        $this->actingAs($this->user)->get(route('registers.contract.nil', ['kind' => 'fines', 'client_id' => $plain->id, 'month' => 9, 'year' => 2026]))->assertStatus(422);
        $this->actingAs($this->user)->get('/registers/contract/other?'.http_build_query($query))->assertNotFound();
    }

    public function test_a_full_unit_of_24_workmen_fits_on_one_page_per_register(): void
    {
        foreach (range(1, 24) as $i) {
            $this->guard("WORKMAN NUMBER {$i}");
        }

        foreach (['fines', 'deductions'] as $kind) {
            $pdf = app(\App\Services\PdfExportService::class)->contractLabourNilRegister($kind, $this->contract, 9, 2026)->output();

            $this->assertLessThanOrEqual(2, preg_match_all('#/Type\s*/Page\b#', $pdf), $kind);
        }
    }
}
