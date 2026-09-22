<?php

return [
    'length' => 6,
    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 5),
    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
    'request_limit' => (int) env('OTP_REQUEST_LIMIT', 3),
    'verify_limit' => (int) env('OTP_VERIFY_LIMIT', 5),
];
