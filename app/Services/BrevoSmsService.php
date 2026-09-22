<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class BrevoSmsService
{
    public function sendLoginCode(string $mobileNumber, string $code): bool
    {
        if (! config('services.brevo.sms.enabled') || ! config('services.brevo.sms.api_key')) {
            return false;
        }

        try {
            return Http::withHeaders([
                'api-key' => config('services.brevo.sms.api_key'),
                'accept' => 'application/json',
            ])->asJson()
                ->timeout((int) config('services.brevo.sms.timeout', 10))
                ->post(config('services.brevo.sms.endpoint'), [
                    'sender' => config('services.brevo.sms.sender'),
                    'recipient' => $mobileNumber,
                    'content' => "Magnova Consulting: Your login OTP is {$code}. Valid for 5 minutes. Do not share this OTP with anyone.",
                    'type' => 'transactional',
                    'tag' => 'magnova_login_otp',
                    'unicodeEnabled' => false,
                ])->successful();
        } catch (Throwable) {
            return false;
        }
    }
}
