<?php

namespace Tests\Unit;

use App\Support\WageMonth;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WageMonthTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public static function dates(): array
    {
        return [
            'october 2026 gives september 2026' => ['2026-10-14', 9, 2026],
            'first day of the month' => ['2026-10-01', 9, 2026],
            'january rolls back to december of last year' => ['2027-01-05', 12, 2026],
            'march 31 does not overflow february' => ['2027-03-31', 2, 2027],
            'last day of october' => ['2026-10-31', 9, 2026],
        ];
    }

    #[DataProvider('dates')]
    public function test_default_wage_month_is_the_previous_calendar_month(string $today, int $month, int $year): void
    {
        Carbon::setTestNow($today);

        $this->assertSame($month, WageMonth::month());
        $this->assertSame($year, WageMonth::year());
    }
}
