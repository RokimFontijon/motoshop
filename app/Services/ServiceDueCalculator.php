<?php

namespace App\Services;

use DateTimeImmutable;

/**
 * Estimates when a motorcycle is next due for a service.
 *
 * This class uses plain PHP arrays and dates only (no database, no AI).
 * It is a rule-based estimate, not machine learning:
 *
 *   1. Mileage due   = odometer at last service + interval_km
 *   2. Time due      = date of last service + interval_months
 *   3. Average km/day = (last odometer - first odometer) / days between the
 *                       first and last recorded visit of that motorcycle
 *   4. Mileage date  = date of latest visit + (km remaining / average km per day)
 *   5. Next due date = the EARLIER of the mileage date and the time due date
 *
 * A "visit" is an array: ['date' => 'Y-m-d', 'odometer' => int, 'services' => [service_type_id, ...]]
 */
class ServiceDueCalculator
{
    /** Used when a motorcycle has fewer than 2 visits, so its real usage is unknown. */
    public const DEFAULT_KM_PER_DAY = 20.0;

    /** A service is "due soon" when it is due within this many days. */
    public const DUE_SOON_DAYS = 14;

    public function __construct(
        private float $defaultKmPerDay = self::DEFAULT_KM_PER_DAY,
        private int $dueSoonDays = self::DUE_SOON_DAYS,
    ) {
    }

    /**
     * Average kilometers per day, from the first to the last visit.
     *
     * @return array{km_per_day: float, source: string} source is 'history' or 'default'
     */
    public function averageKmPerDay(array $visits): array
    {
        $visits = $this->sortVisits($visits);

        if (count($visits) >= 2) {
            $first = $visits[0];
            $last = $visits[count($visits) - 1];

            $days = $this->daysBetween($this->parse($first['date']), $this->parse($last['date']));
            $km = $last['odometer'] - $first['odometer'];

            if ($days >= 1 && $km > 0) {
                return ['km_per_day' => $km / $days, 'source' => 'history'];
            }
        }

        return ['km_per_day' => $this->defaultKmPerDay, 'source' => 'default'];
    }

    /**
     * Next due date of ONE service type on ONE motorcycle.
     *
     * @param array $lastService  visit where this service was last done ['date', 'odometer']
     * @param array $latestVisit  most recent visit of the motorcycle ['date', 'odometer']
     */
    public function calculate(
        array $lastService,
        array $latestVisit,
        float $kmPerDay,
        int $intervalKm,
        int $intervalMonths,
        DateTimeImmutable $today,
    ): array {
        $kmPerDay = max($kmPerDay, 0.1); // never divide by zero

        // Mileage rule
        $dueOdometer = $lastService['odometer'] + $intervalKm;
        $kmRemaining = max(0, $dueOdometer - $latestVisit['odometer']);
        $daysByMileage = (int) ceil($kmRemaining / $kmPerDay);
        $mileageDate = $this->parse($latestVisit['date'])->modify("+{$daysByMileage} days");

        // Time rule
        $timeDate = $this->addMonths($this->parse($lastService['date']), $intervalMonths);

        // Whichever comes first
        if ($mileageDate < $timeDate) {
            $nextDue = $mileageDate;
            $basis = 'mileage';
        } else {
            $nextDue = $timeDate;
            $basis = 'time';
        }

        $daysLeft = $this->daysBetween($today, $nextDue);

        return [
            'due_odometer' => $dueOdometer,
            'km_remaining' => $kmRemaining,
            'mileage_date' => $mileageDate->format('Y-m-d'),
            'time_date' => $timeDate->format('Y-m-d'),
            'next_due' => $nextDue->format('Y-m-d'),
            'basis' => $basis,
            'days_left' => $daysLeft,
            'status' => $this->statusFor($daysLeft),
        ];
    }

    public function statusFor(int $daysLeft): string
    {
        if ($daysLeft < 0) {
            return 'overdue';
        }

        return $daysLeft <= $this->dueSoonDays ? 'due_soon' : 'ok';
    }

    /**
     * "Estimated vs actual": for each pair of consecutive times the same
     * service was done, estimate the next due date using only the data known
     * at the first visit, then compare with the date the customer really came.
     *
     * diff_days > 0  the customer came LATER than estimated
     * diff_days < 0  the customer came EARLIER than estimated
     *
     * @param array $intervals [service_type_id => ['km' => int, 'months' => int]]
     */
    public function backtest(array $visits, array $intervals): array
    {
        $visits = $this->sortVisits($visits);
        $rows = [];

        foreach ($intervals as $serviceTypeId => $interval) {
            // Positions of the visits where this service was done.
            $positions = [];
            foreach ($visits as $index => $visit) {
                if (in_array($serviceTypeId, $visit['services'], true)) {
                    $positions[] = $index;
                }
            }

            for ($p = 0; $p < count($positions) - 1; $p++) {
                $from = $positions[$p];
                $to = $positions[$p + 1];

                $knownVisits = array_slice($visits, 0, $from + 1); // no peeking at the future
                $kmPerDay = $this->averageKmPerDay($knownVisits)['km_per_day'];

                $estimate = $this->calculate(
                    $visits[$from],
                    $visits[$from],
                    $kmPerDay,
                    $interval['km'],
                    $interval['months'],
                    $this->parse($visits[$from]['date']),
                );

                $actual = $visits[$to]['date'];

                $rows[] = [
                    'service_type_id' => $serviceTypeId,
                    'serviced_on' => $visits[$from]['date'],
                    'estimated_due' => $estimate['next_due'],
                    'actual_date' => $actual,
                    'diff_days' => $this->daysBetween($this->parse($estimate['next_due']), $this->parse($actual)),
                    'basis' => $estimate['basis'],
                ];
            }
        }

        return $rows;
    }

    /** Mean absolute difference in days between estimated and actual dates. */
    public function meanAbsoluteDays(array $backtestRows): ?float
    {
        if (count($backtestRows) === 0) {
            return null;
        }

        $sum = 0;
        foreach ($backtestRows as $row) {
            $sum += abs($row['diff_days']);
        }

        return $sum / count($backtestRows);
    }

    // ----------------------------------------------------------------
    // Small helpers
    // ----------------------------------------------------------------

    private function sortVisits(array $visits): array
    {
        usort($visits, function ($a, $b) {
            return [$a['date'], $a['odometer']] <=> [$b['date'], $b['odometer']];
        });

        return array_values($visits);
    }

    private function parse(string $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date);
    }

    /** Whole days from $from to $to (negative when $to is earlier). */
    private function daysBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $from->diff($to)->format('%r%a');
    }

    /** Adds months and keeps the day inside the month (Nov 30 + 3 months = Feb 28). */
    private function addMonths(DateTimeImmutable $date, int $months): DateTimeImmutable
    {
        $monthIndex = (int) $date->format('n') - 1 + $months;
        $year = (int) $date->format('Y') + intdiv($monthIndex, 12);
        $month = $monthIndex % 12 + 1;

        $firstOfMonth = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $day = min((int) $date->format('j'), (int) $firstOfMonth->format('t'));

        return $firstOfMonth->setDate($year, $month, $day);
    }
}
