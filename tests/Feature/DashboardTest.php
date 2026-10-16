<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_accepts_a_month_and_year_filter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard', ['month' => 2, 'year' => 2025]));

        $response->assertOk();
    }

    public function test_dashboard_figures_come_from_the_real_records(): void
    {
        Carbon::setTestNow('2026-10-15 09:00:00');
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit A', 'active' => true]);
        $make = fn (string $code) => Employee::create([
            'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'employee_code' => $code, 'name' => "Guard {$code}",
            'skill_category' => 'skilled', 'rate_per_day' => 500, 'status' => 'active',
        ]);
        $guards = collect(['A', 'B', 'C', 'D', 'E'])->map($make);
        foreach ([['present', 0], ['present', 1], ['absent', 2], ['leave', 3]] as [$status, $i]) {
            Attendance::create(['tenant_id' => $user->tenant_id, 'employee_id' => $guards[$i]->id, 'date' => '2026-10-15', 'status' => $status]);
        }
        // Yesterday's record must not count as today's.
        Attendance::create(['tenant_id' => $user->tenant_id, 'employee_id' => $guards[4]->id, 'date' => '2026-10-14', 'status' => 'present']);

        $months = ['X1' => 6, 'X2' => 7, 'X3' => 8];
        $invoice = fn (string $no, string $status, string $due, float $total) => Invoice::create([
            'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'invoice_no' => $no, 'invoice_date' => '2026-09-01',
            'due_date' => $due, 'month' => $months[$no], 'year' => 2026, 'rate_per_day' => 1, 'no_of_persons' => 1,
            'no_of_duties' => 1, 'base_amount' => 1, 'epf_employer' => 0, 'epf_admin' => 0, 'edli_cont' => 0, 'esi_employer' => 0,
            'taxable_value' => 1, 'service_charge' => 0, 'cgst' => 0, 'sgst' => 0, 'grand_total' => $total, 'status' => $status,
        ]);
        $invoice('X1', 'sent', '2026-10-01', 1000);
        $invoice('X2', 'paid', '2026-10-01', 5000);
        $invoice('X3', 'draft', '2026-12-01', 700);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn ($page) => $page
            ->where('today.expected', 5)
            ->where('today.present', 2)
            ->where('today.absent', 1)
            ->where('today.leave', 1)
            ->where('today.not_marked', 1)
            ->where('today.percent', 40)
            ->where('outstanding.count', 1)
            ->where('outstanding.amount', 1000)
            ->where('outstanding.overdue', 1)
            ->where('outstanding.drafts', 1)
            ->has('recent'));
    }
}
