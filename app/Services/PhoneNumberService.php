<?php

namespace App\Services;

use InvalidArgumentException;

class PhoneNumberService
{
    public function normalize(string $number): string
    {
        $number = trim($number);
        $number = preg_replace('/[\s().-]+/', '', $number) ?? $number;

        if (str_starts_with($number, '+91')) {
            $digits = substr($number, 3);
        } elseif (str_starts_with($number, '0091')) {
            $digits = substr($number, 4);
        } elseif (str_starts_with($number, '91') && strlen($number) === 12) {
            $digits = substr($number, 2);
        } elseif (str_starts_with($number, '0') && strlen($number) === 11) {
            $digits = substr($number, 1);
        } else {
            $digits = $number;
        }

        if (! preg_match('/^[6-9][0-9]{9}$/', $digits)) {
            throw new InvalidArgumentException('The mobile number must be a valid Indian mobile number.');
        }

        return '+91'.$digits;
    }

    public function mask(string $number): string
    {
        $normalized = $this->normalize($number);

        return substr($normalized, 0, 3).'******'.substr($normalized, -4);
    }
}
