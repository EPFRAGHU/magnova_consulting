<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestLoginOtpRequest;
use App\Http\Requests\Auth\VerifyLoginOtpRequest;
use App\Models\LoginOtp;
use App\Models\User;
use App\Services\BrevoSmsService;
use App\Services\PhoneNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginOtpController extends Controller
{
    public function __construct(
        private readonly PhoneNumberService $phoneNumbers,
        private readonly BrevoSmsService $sms,
    ) {}

    public function request(RequestLoginOtpRequest $request): RedirectResponse
    {
        $request->session()->forget(['login_otp.id', 'login_otp.mobile']);

        $mobileNumber = $this->phoneNumbers->normalize($request->string('mobile_number')->toString());
        $key = 'otp-request|'.$mobileNumber.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, (int) config('otp.request_limit', 3))) {
            throw ValidationException::withMessages([
                'mobile_number' => 'Please wait before requesting another code.',
            ]);
        }

        RateLimiter::hit($key, 60);

        $user = User::query()->where('mobile_number', $mobileNumber)->first();

        if (! $user) {
            return back()->with('otp_status', 'If that number is registered, a login code will be sent shortly.');
        }

        LoginOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', 'login')
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);
        $otp = LoginOtp::query()->create([
            'user_id' => $user->id,
            'mobile_number' => $mobileNumber,
            'purpose' => 'login',
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'max_attempts' => (int) config('otp.max_attempts', 5),
            'expires_at' => now()->addMinutes((int) config('otp.expiry_minutes', 10)),
            'last_sent_at' => now(),
        ]);

        if (! $this->sms->sendLoginCode($mobileNumber, $code)) {
            $otp->delete();

            return back()->with('otp_status', 'If that number is registered, a login code will be sent shortly.');
        }

        $request->session()->put([
            'login_otp.id' => $otp->id,
            'login_otp.mobile' => $this->phoneNumbers->mask($mobileNumber),
        ]);

        return back()->with('otp_status', 'If that number is registered, a login code will be sent shortly.');
    }

    public function verify(VerifyLoginOtpRequest $request): RedirectResponse
    {
        $key = 'otp-verify|'.$request->session()->getId().'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, (int) config('otp.verify_limit', 5))) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Please request a new code.',
            ]);
        }

        RateLimiter::hit($key, 60);

        $result = DB::transaction(function () use ($request): array {
            $id = $request->session()->get('login_otp.id');
            $otp = $id ? LoginOtp::query()->lockForUpdate()->find($id) : null;

            if (! $otp || $otp->consumed_at || $otp->expires_at->isPast() || $otp->attempts >= $otp->max_attempts) {
                return [
                    'error' => 'This code is invalid or expired. Please request a new code.',
                    'clear_challenge' => true,
                ];
            }

            $otp->increment('attempts');
            $otp->refresh();

            if (! Hash::check($request->string('code')->toString(), $otp->code_hash)) {
                if ($otp->attempts >= $otp->max_attempts) {
                    $otp->forceFill(['consumed_at' => now()])->save();

                    return [
                        'error' => 'This code is invalid or expired. Please request a new code.',
                        'clear_challenge' => true,
                    ];
                }

                return ['error' => 'This code is invalid or expired. Please try again.'];
            }

            $otp->forceFill(['consumed_at' => now()])->save();

            return ['user' => $otp->user];
        });

        if (isset($result['error'])) {
            if ($result['clear_challenge'] ?? false) {
                $request->session()->forget(['login_otp.id', 'login_otp.mobile']);
            }

            throw ValidationException::withMessages(['code' => $result['error']]);
        }

        /** @var User $user */
        $user = $result['user'];

        $request->session()->forget(['login_otp.id', 'login_otp.mobile']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $otpId = $request->session()->get('login_otp.id');
        $otp = $otpId ? LoginOtp::query()->find($otpId) : null;

        if (! $otp || $otp->consumed_at || $otp->expires_at->isPast()) {
            $request->session()->forget(['login_otp.id', 'login_otp.mobile']);

            throw ValidationException::withMessages([
                'code' => 'This code is invalid or expired. Please request a new code.',
            ]);
        }

        if ($otp->last_sent_at->addSeconds((int) config('otp.resend_cooldown_seconds', 60))->isFuture()) {
            throw ValidationException::withMessages([
                'code' => 'Please wait before requesting another code.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        if (! $this->sms->sendLoginCode($otp->mobile_number, $code)) {
            throw ValidationException::withMessages([
                'code' => 'We could not send a code right now. Please try again later.',
            ]);
        }

        $otp->forceFill([
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('otp.expiry_minutes', 10)),
            'last_sent_at' => now(),
        ])->save();

        return back()->with('otp_status', 'If that number is registered, a login code will be sent shortly.');
    }
}
