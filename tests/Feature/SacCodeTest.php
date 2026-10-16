<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\DesignationSacCode;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\User;
use App\Services\DesignationCatalog;
use App\Services\PdfExportService;
use App\Services\WageRegisterCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function listed(User $user, string $designation, ?string $code = null, ?string $description = null): DesignationSacCode
    {
        return DesignationSacCode::create([
            'tenant_id' => $user->tenant_id, 'designation' => $designation, 'sac_code' => $code, 'sac_description' => $description,
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

    private function unit(User $user): Client
    {
        return Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit A', 'active' => true]);
    }

    public function test_each_invoice_row_gets_the_sac_code_and_description_of_its_designation(): void
    {
        $user = User::factory()->create();
        $client = $this->unit($user);
        $guard = $this->employee($user, $client, 'Guard', 'Security Guard', 'male', 700);
        $sweeper = $this->employee($user, $client, 'Sweeper', 'Sweeper', null, 500);
        $gardener = $this->employee($user, $client, 'Gardener', 'Gardener', null, 450);
        $this->listed($user, 'Security Guard', '998525');
        $this->listed($user, 'Sweeper', '998533', 'Housekeeping as the contract words it');
        $this->listed($user, 'Gardener');

        $rows = app(PdfExportService::class)->dutySummary($this->invoiceFor($user, $client, [$guard, $sweeper, $gardener]))->keyBy('rank');

        $this->assertSame('998525', $rows['Security Guard Male']['sac']);
        $this->assertSame('Guard services', $rows['Security Guard Male']['sac_description']);
        $this->assertSame('998533', $rows['Sweeper']['sac']);
        $this->assertSame('Housekeeping as the contract words it', $rows['Sweeper']['sac_description']);
        $this->assertSame(config('magnova.hsn_sac_code'), $rows['Gardener']['sac']);
    }

    public function test_the_catalog_gives_the_same_answer_everywhere(): void
    {
        $user = User::factory()->create();
        $this->listed($user, 'Sweeper', '998533');
        $catalog = app(DesignationCatalog::class);

        $this->assertSame('998533', $catalog->lookup($user->tenant_id, 'sweeper')['sac_code']);
        $this->assertSame('General cleaning services (housekeeping)', $catalog->lookup($user->tenant_id, ' SWEEPER ')['sac_description']);
        $this->assertSame('998525', $catalog->lookup($user->tenant_id, 'Unknown role')['sac_code']);
        $this->assertFalse($catalog->lookup($user->tenant_id, 'Unknown role')['in_list']);
        $this->assertSame('998525', $catalog->lookup($user->tenant_id, null)['sac_code']);
    }

    public function test_the_register_of_wages_does_not_carry_sac_codes(): void
    {
        $user = User::factory()->create();
        $client = $this->unit($user);
        $sweeper = $this->employee($user, $client, 'Sweeper', 'Sweeper');
        $this->listed($user, 'Sweeper', '998533');

        $row = app(WageRegisterCalculator::class)->row($sweeper, null, 9, 2026, 1);

        $this->assertSame('Sweeper', $row['nature_of_deployment']);
        $this->assertArrayNotHasKey('sac_code', $row);
        $this->assertArrayNotHasKey('sac_description', $row);
    }

    public function test_settings_page_lists_the_master_list_with_descriptions_and_usage(): void
    {
        $user = User::factory()->create();
        $client = $this->unit($user);
        $this->employee($user, $client, 'B', 'Sweeper');
        $this->listed($user, 'Security Guard');
        $this->listed($user, 'Sweeper', '998533');

        $this->actingAs($user)->get(route('sac-codes.edit'))->assertInertia(fn ($page) => $page
            ->component('settings/sac-codes')
            ->where('default_sac', '998525')
            ->has('catalog')
            ->has('rows', 2)
            ->where('rows.0.designation', 'Security Guard')
            ->where('rows.0.sac_code', '')
            ->where('rows.0.in_use', false)
            ->where('rows.1.designation', 'Sweeper')
            ->where('rows.1.sac_code', '998533')
            ->where('rows.1.sac_description', 'General cleaning services (housekeeping)')
            ->where('rows.1.in_use', true));
    }

    public function test_designations_can_be_added_changed_and_removed(): void
    {
        $user = User::factory()->create();
        $client = $this->unit($user);

        $this->actingAs($user)->patch(route('sac-codes.update'), ['rows' => [
            ['designation' => 'Security Guard', 'sac_code' => '998525', 'sac_description' => ''],
            ['designation' => 'Cook', 'sac_code' => '', 'sac_description' => ''],
            ['designation' => 'Sweeper', 'sac_code' => '998533', 'sac_description' => 'Cleaning'],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(3, DesignationSacCode::count());
        $this->assertNull(DesignationSacCode::where('designation', 'Cook')->value('sac_code'));

        $this->employee($user, $client, 'Used', 'Sweeper');

        $this->actingAs($user)->patch(route('sac-codes.update'), ['rows' => [
            ['designation' => 'Security Guard', 'sac_code' => '111111', 'sac_description' => ''],
        ]])->assertSessionHasNoErrors();

        $this->assertSame('111111', DesignationSacCode::where('designation', 'Security Guard')->value('sac_code'));
        $this->assertSame(0, DesignationSacCode::where('designation', 'Cook')->count(), 'unused designation removed');
        $this->assertSame(1, DesignationSacCode::where('designation', 'Sweeper')->count(), 'designation in use is kept');
    }

    public function test_a_sac_code_must_be_six_digits_and_names_cannot_repeat(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('sac-codes.update'), ['rows' => [
            ['designation' => 'Sweeper', 'sac_code' => '12AB'],
        ]])->assertSessionHasErrors('rows.0.sac_code');

        $this->actingAs($user)->patch(route('sac-codes.update'), ['rows' => [
            ['designation' => 'Sweeper', 'sac_code' => '998533'],
            ['designation' => 'sweeper', 'sac_code' => '998533'],
        ]])->assertSessionHasErrors('rows');

        $this->assertSame(0, DesignationSacCode::count());
    }

    public function test_an_employee_must_pick_a_designation_from_the_list_and_it_is_stored_exactly(): void
    {
        $user = User::factory()->create();
        $client = $this->unit($user);
        $this->listed($user, 'Security Guard', '998525');
        $payload = ['client_id' => $client->id, 'name' => 'New Hire', 'skill_category' => 'unskilled', 'rate_per_day' => 500, 'status' => 'active'];

        $this->actingAs($user)->post(route('employees.store'), $payload + ['designation' => 'Security Gaurd'])
            ->assertSessionHasErrors('designation');
        $this->assertSame(0, Employee::count());

        $this->actingAs($user)->post(route('employees.store'), $payload + ['designation' => 'security guard'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Security Guard', Employee::first()->designation);
    }

    public function test_an_existing_employee_keeps_a_designation_that_is_not_in_the_list(): void
    {
        $user = User::factory()->create();
        $client = $this->unit($user);
        $employee = $this->employee($user, $client, 'Old', 'Legacy Role');

        $this->actingAs($user)->put(route('employees.update', $employee), [
            'client_id' => $client->id, 'name' => 'Old', 'skill_category' => 'unskilled', 'rate_per_day' => 600,
            'status' => 'active', 'designation' => 'Legacy Role',
        ])->assertSessionHasNoErrors();

        $this->assertSame('600.00', $employee->fresh()->rate_per_day);
    }

    public function test_the_employee_form_shows_sac_but_other_screens_do_not(): void
    {
        $user = User::factory()->create();
        $client = $this->unit($user);
        $this->employee($user, $client, 'Sweeper', 'Sweeper');
        $this->listed($user, 'Sweeper', '998533');

        $this->actingAs($user)->get(route('employees.create'))->assertInertia(fn ($page) => $page
            ->has('designations', 1)
            ->where('designations.0.designation', 'Sweeper')
            ->where('designations.0.sac_code', '998533')
            ->where('designations.0.sac_description', 'General cleaning services (housekeeping)'));

        $this->actingAs($user)->get(route('employees.index'))->assertInertia(fn ($page) => $page
            ->where('employees.0.designation', 'Sweeper')
            ->missing('employees.0.sac'));

        $this->actingAs($user)->get(route('attendance.index', ['client_id' => $client->id, 'month' => 9, 'year' => 2026]))
            ->assertInertia(fn ($page) => $page
                ->where('employees.0.designation', 'Sweeper')
                ->missing('employees.0.sac'));
    }

    public function test_the_migration_adds_every_designation_already_on_an_employee_to_the_list(): void
    {
        $user = User::factory()->create();
        $client = $this->unit($user);
        $this->employee($user, $client, 'A', 'Sweeper');
        $this->employee($user, $client, 'B', 'Sweeper');
        $this->employee($user, $client, 'C', 'Gardener');
        $this->employee($user, $client, 'D', null);
        $this->listed($user, 'Gardener', '998597');

        (require database_path('migrations/2026_10_16_000000_make_designations_a_master_list.php'))->up();

        $this->assertSame(['Gardener', 'Sweeper'], DB::table('designation_sac_codes')->orderBy('designation')->pluck('designation')->all());
        $this->assertSame('998597', DB::table('designation_sac_codes')->where('designation', 'Gardener')->value('sac_code'));
        $this->assertNull(DB::table('designation_sac_codes')->where('designation', 'Sweeper')->value('sac_code'));
    }

    public function test_attendance_sheet_and_register_pdfs_still_render(): void
    {
        $user = User::factory()->create();
        $client = $this->unit($user);
        $employee = $this->employee($user, $client, 'Sweeper', 'Sweeper');
        $this->listed($user, 'Sweeper', '998533');
        Attendance::create(['tenant_id' => $user->tenant_id, 'employee_id' => $employee->id, 'date' => '2026-09-01', 'status' => 'present']);

        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->attendanceSheet($client, 9, 2026)->output());
        $this->assertStringStartsWith('%PDF', app(PdfExportService::class)->wageRegister($client, 9, 2026)->output());
    }
}
