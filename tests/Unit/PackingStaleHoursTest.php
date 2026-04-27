<?php

namespace Tests\Unit;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Mirrors dashboard "hours open" / >24h stale logic for unpacking alerts.
 */
class PackingStaleHoursTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_same_calendar_day_is_not_stale_at_afternoon(): void
    {
        $tz = 'Asia/Manila';
        Carbon::setTestNow(Carbon::parse('2026-04-27 15:00:00', $tz));
        $productionDate = Carbon::parse('2026-04-27', $tz)->startOfDay();
        $hoursOpen = $productionDate->diffInHours(Carbon::now());

        $this->assertLessThan(24, $hoursOpen);
        $this->assertFalse($hoursOpen >= 24);
    }

    public function test_next_calendar_day_exceeds_24_hours_from_production_day_start(): void
    {
        $tz = 'Asia/Manila';
        Carbon::setTestNow(Carbon::parse('2026-04-28 01:00:00', $tz));
        $productionDate = Carbon::parse('2026-04-27', $tz)->startOfDay();
        $hoursOpen = $productionDate->diffInHours(Carbon::now());

        $this->assertGreaterThanOrEqual(24, $hoursOpen);
    }
}
