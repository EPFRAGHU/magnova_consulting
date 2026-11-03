<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Services\WageRegisterCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WageRegisterSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_register_of_wages_pdf_has_a_signature_column_after_the_last_amount(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Plain unit', 'active' => true]);
        $employee = Employee::create([
            'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'SG-1', 'name' => 'Guard', 'gender' => 'male',
            'skill_category' => 'skilled', 'designation' => 'Guard', 'rate_per_day' => 700, 'status' => 'active',
        ]);
        $calculator = app(WageRegisterCalculator::class);

        $html = view('pdf.wage-register', [
            'client' => $client,
            'rows' => collect([$calculator->row($employee, null, 10, 2026, 1)]),
            'headings' => $calculator->headings(10, 2026, false, false, $user->tenant_id, null, $client),
            'month' => 10,
            'year' => 2026,
        ])->render();

        $this->assertMatchesRegularExpression('~Net Payable Salary of the Guard[^<]*</th>\s*<th class="col-signature">Signature</th>~', $html);
        $this->assertStringContainsString('<td class="col-signature"></td>', $html);
    }
}
