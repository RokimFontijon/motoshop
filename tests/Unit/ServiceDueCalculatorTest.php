<?php

namespace Tests\Unit;

use App\Services\ServiceDueCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the service due date rules. No database needed:
 *     php artisan test --testsuite=Unit
 */
class ServiceDueCalculatorTest extends TestCase
{
    private function calculator(): ServiceDueCalculator
    {
        return new ServiceDueCalculator(20.0, 14);
    }

    public function test_average_km_per_day_uses_first_and_last_visit(): void
    {
        $visits = [
            ['date' => '2026-01-01', 'odometer' => 1000, 'services' => []],
            ['date' => '2026-03-02', 'odometer' => 2200, 'services' => []],
        ];

        $result = $this->calculator()->averageKmPerDay($visits);

        // 1200 km in 60 days = 20 km per day
        $this->assertSame('history', $result['source']);
        $this->assertEqualsWithDelta(20.0, $result['km_per_day'], 0.0001);
    }

    public function test_average_km_per_day_falls_back_to_default_with_one_visit(): void
    {
        $visits = [['date' => '2026-01-01', 'odometer' => 1000, 'services' => []]];

        $result = $this->calculator()->averageKmPerDay($visits);

        $this->assertSame('default', $result['source']);
        $this->assertEqualsWithDelta(20.0, $result['km_per_day'], 0.0001);
    }

    public function test_mileage_date_comes_first_for_a_heavy_rider(): void
    {
        $last = ['date' => '2026-01-01', 'odometer' => 10000];
        $latest = ['date' => '2026-02-01', 'odometer' => 11000];

        // due at 13,000 km, 2,000 km left at 50 km/day = 40 days after Feb 1
        $result = $this->calculator()->calculate($last, $latest, 50.0, 3000, 6, new DateTimeImmutable('2026-03-01'));

        $this->assertSame(13000, $result['due_odometer']);
        $this->assertSame(2000, $result['km_remaining']);
        $this->assertSame('2026-03-13', $result['mileage_date']);
        $this->assertSame('2026-07-01', $result['time_date']);
        $this->assertSame('mileage', $result['basis']);
        $this->assertSame('2026-03-13', $result['next_due']);
        $this->assertSame(12, $result['days_left']);
        $this->assertSame('due_soon', $result['status']);
    }

    public function test_time_date_comes_first_for_a_light_rider(): void
    {
        $last = ['date' => '2026-01-31', 'odometer' => 10000];

        $result = $this->calculator()->calculate($last, $last, 10.0, 10000, 2, new DateTimeImmutable('2026-02-01'));

        $this->assertSame('time', $result['basis']);
        $this->assertSame('2026-03-31', $result['next_due']);
        $this->assertSame('ok', $result['status']);
    }

    public function test_months_are_added_without_overflowing_into_the_next_month(): void
    {
        $last = ['date' => '2026-11-30', 'odometer' => 5000];

        $result = $this->calculator()->calculate($last, $last, 5.0, 100000, 3, new DateTimeImmutable('2026-12-01'));

        // Nov 30 + 3 months would be "Feb 30", so it is clamped to Feb 28.
        $this->assertSame('2027-02-28', $result['time_date']);
    }

    public function test_a_service_past_its_due_date_is_overdue(): void
    {
        $last = ['date' => '2026-01-01', 'odometer' => 1000];

        $result = $this->calculator()->calculate($last, $last, 20.0, 3000, 3, new DateTimeImmutable('2026-04-11'));

        // time due = 2026-04-01, today = 2026-04-11
        $this->assertSame('2026-04-01', $result['next_due']);
        $this->assertSame(-10, $result['days_left']);
        $this->assertSame('overdue', $result['status']);
    }

    public function test_status_boundaries(): void
    {
        $calc = $this->calculator();

        $this->assertSame('overdue', $calc->statusFor(-1));
        $this->assertSame('due_soon', $calc->statusFor(0));
        $this->assertSame('due_soon', $calc->statusFor(14));
        $this->assertSame('ok', $calc->statusFor(15));
    }

    public function test_backtest_compares_estimated_and_actual_dates(): void
    {
        $visits = [
            ['date' => '2026-01-01', 'odometer' => 1000, 'services' => [1]],
            ['date' => '2026-02-01', 'odometer' => 2000, 'services' => [2]],
            ['date' => '2026-04-11', 'odometer' => 3000, 'services' => [1]],
        ];
        $intervals = [1 => ['km' => 3000, 'months' => 3]];

        $calc = $this->calculator();
        $rows = $calc->backtest($visits, $intervals);

        // Estimate made on Jan 1 (default 20 km/day): mileage date May 31, time date Apr 1 -> Apr 1.
        $this->assertCount(1, $rows);
        $this->assertSame('2026-04-01', $rows[0]['estimated_due']);
        $this->assertSame('2026-04-11', $rows[0]['actual_date']);
        $this->assertSame(10, $rows[0]['diff_days']);
        $this->assertEqualsWithDelta(10.0, $calc->meanAbsoluteDays($rows), 0.0001);
        $this->assertNull($calc->meanAbsoluteDays([]));
    }
}
