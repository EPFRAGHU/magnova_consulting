<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** EPFO wage month: wages are processed in the month after they are earned, so the default is the previous calendar month. */
class WageMonth
{
    public static function default(): CarbonInterface
    {
        return now()->startOfMonth()->subMonthNoOverflow();
    }

    public static function month(): int
    {
        return (int) self::default()->format('n');
    }

    public static function year(): int
    {
        return (int) self::default()->format('Y');
    }
}
