<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\Wage;
use App\Services\InvoiceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Wages saved before the ESIC update must keep exactly the figures they were saved with. */
class EsicBackfillTest extends TestCase
{
    use RefreshDatabase;

    private function wage(Tenant $tenant, Client $client, int $month, int $year, float $gross, float $esicEmployee, ?array $snapshot = null): Wage
    {
        static $n = 0;
        $n++;
        $employee = Employee::create([
            'tenant_id' => $tenant->id, 'client_id' => $client->id, 'employee_code' => 'B-'.$n, 'name' => "Guard {$n}",
            'skill_category' => 'skilled', 'rate_per_day' => 1000, 'status' => 'active',
        ]);

        return Wage::create([
            'tenant_id' => $tenant->id, 'employee_id' => $employee->id, 'month' => $month, 'year' => $year, 'working_days' => 22,
            'rate_per_day' => 1000, 'gross_wages' => $gross, 'bonus' => 0, 'epf_employee' => 0, 'esic_employee' => $esicEmployee,
            'net_salary' => $gross - $esicEmployee, 'esic_snapshot' => $snapshot,
        ]);
    }

    private function runBackfill(): void
    {
        (require database_path('migrations/2026_10_17_000003_backfill_esic_snapshots_for_saved_wages.php'))->up();
    }

    public function test_saved_wages_under_the_current_rules_get_a_snapshot_that_freezes_their_old_figures(): void
    {
        $tenant = Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test']);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Unit A', 'active' => true]);
        $within = $this->wage($tenant, $client, 9, 2026, 20000, 150);
        $above = $this->wage($tenant, $client, 9, 2026, 26208, 0);

        $this->runBackfill();

        $within = $within->fresh()->esic_snapshot;
        $this->assertSame('legacy_cutoff', $within['mode']);
        $this->assertTrue($within['covered']);
        $this->assertSame('saved_before_esic_update', $within['reason']);
        $this->assertEquals(150, $within['employee_amount'], 'the saved figure is kept, not recalculated');
        $this->assertEquals(650, $within['employer_amount']);

        $above = $above->fresh()->esic_snapshot;
        $this->assertFalse($above['covered']);
        $this->assertEquals(0, $above['employee_amount'] + $above['employer_amount']);
    }

    public function test_wages_from_months_before_the_current_rules_and_existing_snapshots_are_left_alone(): void
    {
        $tenant = Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test']);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Unit A', 'active' => true]);
        $old = $this->wage($tenant, $client, 8, 2026, 26000, 157.5);
        $mine = $this->wage($tenant, $client, 10, 2026, 20000, 150, ['covered' => true, 'mode' => 'statutory', 'marker' => 'mine']);

        $this->runBackfill();

        $this->assertNull($old->fresh()->esic_snapshot);
        $this->assertSame('mine', $mine->fresh()->esic_snapshot['marker']);
    }

    public function test_regenerating_an_old_invoice_after_the_update_gives_the_same_figures(): void
    {
        $tenant = Tenant::create(['name' => 'Magnova Test', 'slug' => 'magnova-test']);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Unit A', 'active' => true]);
        $this->wage($tenant, $client, 9, 2026, 26208, 0);   // above the ceiling, saved with no ESIC under the old rule
        $this->runBackfill();

        $invoice = app(InvoiceCalculator::class)->generateForClient($client, 9, 2026, 'INV-OLD-1');

        $this->assertEquals(0, $invoice->esi_employer, 'history is not rewritten by the new rules');
    }
}
