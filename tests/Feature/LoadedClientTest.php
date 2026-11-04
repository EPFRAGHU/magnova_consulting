<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoadedClientTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $alpha;

    private Client $beta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->alpha = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Alpha unit', 'active' => true]);
        $this->beta = Client::create(['tenant_id' => $this->user->tenant_id, 'name' => 'Beta unit', 'active' => true]);

        foreach ([[$this->alpha, 'Alpha guard'], [$this->beta, 'Beta guard']] as [$client, $name]) {
            Employee::create([
                'tenant_id' => $this->user->tenant_id, 'client_id' => $client->id, 'employee_code' => strtoupper(substr($name, 0, 2)), 'name' => $name,
                'gender' => 'male', 'skill_category' => 'skilled', 'designation' => 'Guard', 'rate_per_day' => 700, 'status' => 'active',
            ]);
            Invoice::create([
                'tenant_id' => $this->user->tenant_id, 'client_id' => $client->id, 'invoice_no' => 'INV-'.$client->id, 'invoice_date' => '2026-11-02', 'month' => 10, 'year' => 2026,
                'rate_per_day' => 700, 'no_of_persons' => 1, 'no_of_duties' => 1, 'base_amount' => 1, 'epf_employer' => 0, 'esi_employer' => 0,
                'taxable_value' => 1, 'service_charge' => 0, 'cgst' => 0, 'sgst' => 0, 'grand_total' => 1, 'status' => 'draft',
            ]);
        }
    }

    private function names(string $route, string $key = 'employees'): array
    {
        return collect($this->actingAs($this->user)->get(route($route, ['month' => 10, 'year' => 2026]))->viewData('page')['props'][$key])
            ->map(fn ($row) => $row['name'] ?? $row['invoice_no'])->all();
    }

    public function test_the_first_client_by_name_is_loaded_until_the_user_picks_another(): void
    {
        $this->assertSame(['Alpha guard'], $this->names('employees.index'));
        $this->assertSame($this->alpha->id, $this->actingAs($this->user)->get(route('clients.index'))->viewData('page')['props']['loaded_client_id']);
    }

    public function test_switching_a_client_on_makes_every_menu_show_only_that_client(): void
    {
        $this->actingAs($this->user)->post(route('clients.load', $this->beta))->assertRedirect();

        $this->assertSame($this->beta->id, $this->user->fresh()->loaded_client_id);
        $this->assertSame(['Beta guard'], $this->names('employees.index'));
        $this->assertSame(['INV-'.$this->beta->id], $this->names('invoices.index', 'invoices'));
        $this->assertSame(['Beta guard'], $this->names('attendance.index'));
        $this->assertSame(['Beta guard'], $this->names('wages.index', 'rows'));
        $this->assertSame($this->beta->id, $this->actingAs($this->user)->get(route('payslips.index'))->viewData('page')['props']['client_id']);
    }

    public function test_a_client_id_in_the_address_cannot_show_another_client(): void
    {
        $this->loadClient($this->user, $this->beta);

        $page = $this->actingAs($this->user)->get(route('employees.index', ['client_id' => $this->alpha->id]))->viewData('page')['props'];

        $this->assertSame(['Beta guard'], collect($page['employees'])->pluck('name')->all());
        $this->assertSame($this->beta->id, $page['client_id']);
    }

    public function test_an_archived_loaded_client_falls_back_to_the_first_active_one(): void
    {
        $this->loadClient($this->user, $this->beta);
        $this->beta->delete();

        $this->assertSame($this->alpha->id, $this->user->fresh()->loadedClient()->id);
    }

    public function test_every_page_carries_the_loaded_clients_name_and_address_for_the_header(): void
    {
        $this->beta->update(['unit_label' => 'Site B', 'address' => "Plot 5\nBhubaneswar"]);
        $this->loadClient($this->user, $this->beta);

        $this->actingAs($this->user)->get(route('employees.index'))
            ->assertInertia(fn ($page) => $page
                ->where('loadedClient.name', 'Beta unit')
                ->where('loadedClient.unit_label', 'Site B')
                ->where('loadedClient.address', "Plot 5\nBhubaneswar"));
    }

    public function test_the_choice_is_per_user(): void
    {
        $other = User::factory()->create(['tenant_id' => $this->user->tenant_id]);
        $this->loadClient($this->user, $this->beta);

        $this->assertSame($this->alpha->id, $other->loadedClient()->id);
    }
}
