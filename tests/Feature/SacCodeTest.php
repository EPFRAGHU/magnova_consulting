<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DesignationSacCode;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\User;
use App\Services\PdfExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SacCodeTest extends TestCase
{
    use RefreshDatabase;

    private function employee(User $user, Client $client, string $name, ?string $designation, ?string $gender = null, int $rate = 500): Employee
    {
        return Employee::create([
            'tenant_id' => $user->tenant_id,
            'client_id' => $client->id,
            'employee_code' => 'E-'.$name,
            'name' => $name,
            'gender' => $gender,
            'designation' => $designation,
            'skill_category' => 'unskilled',
            'rate_per_day' => $rate,
            'status' => 'active',
        ]);
    }

    private function invoiceFor(User $user, Client $client, array $employees): Invoice
    {
        $invoice = Invoice::create([
            'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'invoice_no' => 'MCPL/26-27/'.uniqid(),
            'invoice_date' => '2026-10-01', 'month' => 9, 'year' => 2026, 'rate_per_day' => 500, 'no_of_persons' => count($employees),
            'no_of_duties' => 26, 'base_amount' => 13000, 'epf_employer' => 0, 'epf_admin' => 0, 'edli_cont' => 0,
            'esi_employer' => 0, 'taxable_value' => 13000, 'service_charge' => 0, 'cgst' => 0, 'sgst' => 0,
            'grand_total' => 13000, 'status' => 'draft',
        ]);

        foreach ($employees as $employee) {
            InvoiceLineItem::create([
                'invoice_id' => $invoice->id, 'employee_id' => $employee->id,
                'working_days' => 26, 'rate_per_day' => $employee->rate_per_day, 'wage_amount' => 26 * $employee->rate_per_day,
            ]);
        }

        return $invoice;
    }

    public function test_each_invoice_row_gets_the_sac_code_set_for_its_designation(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit A', 'active' => true]);
        $guard = $this->employee($user, $client, 'Guard', 'Security Guard', 'male', 700);
        $sweeper = $this->employee($user, $client, 'Sweeper', 'Sweeper', null, 500);
        $gardener = $this->employee($user, $client, 'Gardener', 'Gardener', null, 450);
        DesignationSacCode::create(['tenant_id' => $user->tenant_id, 'designation' => 'Security Guard', 'sac_code' => '111111']);
        DesignationSacCode::create(['tenant_id' => $user->tenant_id, 'designation' => 'sweeper', 'sac_code' => '222222']);

        $rows = app(PdfExportService::class)->dutySummary($this->invoiceFor($user, $client, [$guard, $sweeper, $gardener]))->keyBy('rank');

        $this->assertSame('111111', $rows['Security Guard Male']['sac']);
        $this->assertSame('222222', $rows['Sweeper']['sac']);
        $this->assertSame(config('magnova.hsn_sac_code'), $rows['Gardener']['sac']);
    }

    public function test_the_default_code_is_used_when_nothing_is_set(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit A', 'active' => true]);
        $guard = $this->employee($user, $client, 'Guard', 'Security Guard', 'female');

        $rows = app(PdfExportService::class)->dutySummary($this->invoiceFor($user, $client, [$guard]));

        $this->assertSame('998525', $rows->first()['sac']);
    }

    public function test_settings_page_lists_designations_in_use_with_saved_codes(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit A', 'active' => true]);
        $this->employee($user, $client, 'A', 'Security Guard');
        $this->employee($user, $client, 'B', 'Sweeper');
        DesignationSacCode::create(['tenant_id' => $user->tenant_id, 'designation' => 'Sweeper', 'sac_code' => '222222']);

        $this->actingAs($user)->get(route('sac-codes.edit'))->assertInertia(fn ($page) => $page
            ->component('settings/sac-codes')
            ->where('default_sac', '998525')
            ->has('rows', 2)
            ->where('rows.0.designation', 'Security Guard')
            ->where('rows.0.sac_code', '')
            ->where('rows.1.designation', 'Sweeper')
            ->where('rows.1.sac_code', '222222'));
    }

    public function test_codes_can_be_saved_changed_and_cleared(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('sac-codes.update'), ['rows' => [
            ['designation' => 'Security Guard', 'sac_code' => '111111'],
            ['designation' => 'Sweeper', 'sac_code' => '222222'],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(2, DesignationSacCode::count());

        $this->actingAs($user)->patch(route('sac-codes.update'), ['rows' => [
            ['designation' => 'Security Guard', 'sac_code' => '333333'],
            ['designation' => 'Sweeper', 'sac_code' => ''],
        ]])->assertSessionHasNoErrors();

        $this->assertSame('333333', DesignationSacCode::where('designation', 'Security Guard')->value('sac_code'));
        $this->assertSame(0, DesignationSacCode::where('designation', 'Sweeper')->count());
    }

    public function test_a_sac_code_must_be_six_digits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('sac-codes.update'), ['rows' => [
            ['designation' => 'Sweeper', 'sac_code' => '12AB'],
        ]])->assertSessionHasErrors('rows.0.sac_code');
        $this->assertSame(0, DesignationSacCode::count());
    }
}
