<?php

namespace Tests\Unit\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateNewUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_mobile_numbers_return_validation_errors(): void
    {
        $this->expectException(ValidationException::class);

        (new CreateNewUser)->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'mobile_number' => '12345',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    }

    public function test_duplicate_mobile_numbers_return_validation_errors(): void
    {
        $tenant = $this->createTenant();
        User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'mobile_number' => '+919876543210',
            'password' => 'password',
        ]);

        $this->expectException(ValidationException::class);

        (new CreateNewUser)->create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'mobile_number' => '09876543210',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    }

    private function createTenant(): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Test Tenant',
            'slug' => fake()->unique()->slug(),
        ]);
    }
}
