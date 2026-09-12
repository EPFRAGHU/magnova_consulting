<?php

namespace App\Services;

class NumberToWordsService
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    /**
     * Convert a rupee amount to words using the Indian numbering system
     * (Crore/Lakh/Thousand/Hundred). Rounds to the nearest whole rupee first —
     * verified against Magnova's real documents, which never spell out paise.
     */
    public function amountToWords(float $amount): string
    {
        return $this->numberToWords((int) round($amount));
    }

    public function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        $lakh = intdiv($number, 100000);
        $number %= 100000;
        $thousand = intdiv($number, 1000);
        $number %= 1000;
        $hundred = intdiv($number, 100);
        $rest = $number % 100;

        $parts = [];

        if ($crore > 0) {
            $parts[] = $this->twoDigits($crore).' Crore';
        }
        if ($lakh > 0) {
            $parts[] = $this->twoDigits($lakh).' Lakh';
        }
        if ($thousand > 0) {
            $parts[] = $this->twoDigits($thousand).' Thousand';
        }
        if ($hundred > 0) {
            $parts[] = self::ONES[$hundred].' Hundred';
        }
        if ($rest > 0) {
            $parts[] = $this->twoDigits($rest);
        }

        return implode(' ', $parts);
    }

    private function twoDigits(int $n): string
    {
        if ($n < 20) {
            return self::ONES[$n];
        }

        $tens = intdiv($n, 10);
        $ones = $n % 10;

        return trim(self::TENS[$tens].' '.self::ONES[$ones]);
    }
}
