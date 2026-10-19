<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSkillOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_are_ordered_by_skill_then_name(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit', 'active' => true]);

        foreach ([['Zed', 'unskilled'], ['Amy', 'semi_skilled'], ['Bob', 'high_skilled'], ['Cat', 'skilled'], ['Abe', 'unskilled'], ['Dan', 'skilled']] as $i => [$name, $skill]) {
            Employee::create([
                'tenant_id' => $user->tenant_id, 'client_id' => $client->id, 'employee_code' => 'SK-'.$i, 'name' => $name,
                'skill_category' => $skill, 'rate_per_day' => 500, 'status' => 'active',
            ]);
        }

        $this->assertSame(
            ['Bob', 'Cat', 'Dan', 'Amy', 'Abe', 'Zed'],
            Employee::query()->orderBySkill()->pluck('name')->all(),
        );
        $this->assertCount(4, Employee::SKILL_ORDER, 'the ordering SQL has one WHEN per skill');
    }
}
