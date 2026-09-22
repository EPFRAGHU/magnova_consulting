<?php

namespace Tests\Feature\Auth;

use App\Models\LoginOtp;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BrevoSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_otp_request_normalizes_the_number_and_hashes_the_code(): void
    {
        $user = $this->createUser('+919876543210');
        $this->mock(BrevoSmsService::class, function ($mock): void {
            $mock->shouldReceive('sendLoginCode')
                ->once()
                ->with('+919876543210', \Mockery::type('string'))
                ->andReturnTrue();
        });

        $response = $this->post(route('login.otp.request'), [
            'mobile_number' => '098765 43210',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('login_otp.id');
        $response->assertSessionHas('login_otp.mobile', '+91******3210');

        $otp = LoginOtp::query()->latest('id')->firstOrFail();

        $this->assertSame($user->id, $otp->user_id);
        $this->assertSame('+919876543210', $otp->mobile_number);
        $this->assertNotSame('123456', $otp->code_hash);
        $this->assertFalse(Hash::check('123456', $otp->code_hash));
    }

    public function test_brevo_login_sms_uses_the_transactional_sms_payload(): void
    {
        config([
            'services.brevo.sms.enabled' => true,
            'services.brevo.sms.api_key' => 'test-brevo-key',
            'services.brevo.sms.endpoint' => 'https://api.brevo.com/v3/transactionalSMS/send',
            'services.brevo.sms.sender' => 'EPFDASH',
        ]);

        Http::fake([
            'https://api.brevo.com/v3/transactionalSMS/send' => Http::response([], 200),
        ]);

        $this->app->make(BrevoSmsService::class)->sendLoginCode('+919876543210', '482731');

        Http::assertSent(function (HttpRequest $request): bool {
            return $request->url() === 'https://api.brevo.com/v3/transactionalSMS/send'
                && $request->header('accept') === ['application/json']
                && $request->header('api-key') === ['test-brevo-key']
                && $request->header('content-type') === ['application/json']
                && $request->data() === [
                    'sender' => 'EPFDASH',
                    'recipient' => '+919876543210',
                    'content' => 'Magnova Consulting: Your login OTP is 482731. Valid for 5 minutes. Do not share this OTP with anyone.',
                    'type' => 'transactional',
                    'tag' => 'magnova_login_otp',
                    'unicodeEnabled' => false,
                ];
        });
    }

    public function test_unknown_numbers_do_not_create_a_challenge(): void
    {
        $response = $this->post(route('login.otp.request'), [
            'mobile_number' => '+919876543210',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('otp_status', 'If that number is registered, a login code will be sent shortly.');

        $this->assertFalse($this->app['session']->has('login_otp.id'));

        $this->assertDatabaseCount('login_otps', 0);
    }

    public function test_invalid_codes_increment_attempts_and_expire_at_the_limit(): void
    {
        $this->createChallenge();

        $this->post(route('login.otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code')
            ->assertSessionHas('login_otp.id');

        $otp = LoginOtp::query()->latest('id')->firstOrFail();
        $this->assertSame(1, $otp->fresh()->attempts);
        $this->assertNull($otp->fresh()->consumed_at);
    }

    public function test_expired_codes_cannot_be_verified(): void
    {
        $this->createChallenge();
        $otp = LoginOtp::query()->latest('id')->firstOrFail();
        $otp->update(['expires_at' => now()->subMinute()]);

        $this->post(route('login.otp.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code')
            ->assertSessionMissing('login_otp.id');

        $this->assertGuest();
    }

    public function test_maximum_invalid_attempt_consumes_the_code_and_clears_the_challenge(): void
    {
        config(['otp.max_attempts' => 2]);
        $this->createChallenge();

        $this->post(route('login.otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code')
            ->assertSessionHas('login_otp.id');

        $this->post(route('login.otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $otp = LoginOtp::query()->latest('id')->firstOrFail();
        $this->assertSame(2, $otp->fresh()->attempts);
        $this->assertNotNull($otp->fresh()->consumed_at);
        $this->assertFalse($this->app['session']->has('login_otp.id'));
    }

    public function test_valid_codes_authenticate_once_and_are_consumed(): void
    {
        $user = $this->createChallenge();
        $otp = LoginOtp::query()->latest('id')->firstOrFail();
        $otp->update(['code_hash' => Hash::make('123456')]);

        $response = $this->post(route('login.otp.verify'), ['code' => '123456']);

        $response->assertRedirect(config('fortify.home'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($otp->fresh()->consumed_at);
        $this->assertFalse($this->app['session']->has('login_otp.id'));
    }

    public function test_resend_is_blocked_during_the_cooldown(): void
    {
        $this->createChallenge();

        $this->post(route('login.otp.resend'))
            ->assertSessionHasErrors('code');

        $this->assertSame(0, LoginOtp::query()->latest('id')->firstOrFail()->attempts);
    }

    private function createChallenge(): User
    {
        $user = $this->createUser('+919876543210');
        $this->mock(BrevoSmsService::class, function ($mock): void {
            $mock->shouldReceive('sendLoginCode')
                ->once()
                ->andReturnTrue();
        });

        $this->post(route('login.otp.request'), ['mobile_number' => $user->mobile_number]);

        return $user;
    }

    private function createUser(string $mobileNumber): User
    {
        $tenant = Tenant::query()->create([
            'name' => 'Test Tenant',
            'slug' => fake()->unique()->slug(),
        ]);

        return User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'mobile_number' => $mobileNumber,
            'password' => 'password',
        ]);
    }
}
