<?php

namespace App\Services;

use App\Models\Motorcycle;
use DateTimeImmutable;
use Illuminate\Support\Collection;

/**
 * Connects the database (motorcycles, job orders, service intervals) to the
 * ServiceDueCalculator, which only knows about plain arrays.
 */
class ServiceDueReport
{
    public function __construct(private ServiceDueCalculator $calculator)
    {
    }

    /**
     * One row per (motorcycle, service type) that has been done before, sorted
     * so the most urgent is first. Pass a motorcycle id to get only its rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dueRows(?int $motorcycleId = null, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        $rows = [];

        foreach ($this->motorcycles($motorcycleId) as $motorcycle) {
            $visits = $this->visitsOf($motorcycle);
            if (count($visits) === 0) {
                continue; // never serviced here: nothing to base an estimate on
            }

            $usage = $this->calculator->averageKmPerDay($visits);
            $latest = $this->latestVisit($visits);

            foreach ($motorcycle->motorcycleModel->serviceIntervals as $interval) {
                $lastService = $this->lastVisitWith($visits, $interval->service_type_id);
                if ($lastService === null) {
                    continue; // this service was never done on this motorcycle
                }

                $rows[] = [
                    'motorcycle' => $motorcycle,
                    'service' => $interval->serviceType->name,
                    'km_per_day' => $usage['km_per_day'],
                    'usage_source' => $usage['source'],
                    'last_serviced_on' => $lastService['date'],
                    'last_serviced_km' => $lastService['odometer'],
                ] + $this->calculator->calculate(
                    $lastService,
                    $latest,
                    $usage['km_per_day'],
                    $interval->interval_km,
                    $interval->interval_months,
                    $today,
                );
            }
        }

        usort($rows, fn ($a, $b) => $a['days_left'] <=> $b['days_left']);

        return $rows;
    }

    /**
     * Estimated vs actual dates for every repeated service (objective 5 of the study).
     *
     * @return array{rows: array<int, array<string, mixed>>, mean_abs_days: float|null}
     */
    public function accuracy(): array
    {
        $rows = [];

        foreach ($this->motorcycles() as $motorcycle) {
            $intervals = [];
            $names = [];
            foreach ($motorcycle->motorcycleModel->serviceIntervals as $interval) {
                $intervals[$interval->service_type_id] = ['km' => $interval->interval_km, 'months' => $interval->interval_months];
                $names[$interval->service_type_id] = $interval->serviceType->name;
            }

            foreach ($this->calculator->backtest($this->visitsOf($motorcycle), $intervals) as $row) {
                $rows[] = $row + [
                    'plate' => $motorcycle->plate_number,
                    'service' => $names[$row['service_type_id']],
                ];
            }
        }

        usort($rows, fn ($a, $b) => strcmp($b['actual_date'], $a['actual_date']));

        return ['rows' => $rows, 'mean_abs_days' => $this->calculator->meanAbsoluteDays($rows)];
    }

    // ----------------------------------------------------------------

    private function motorcycles(?int $motorcycleId = null): Collection
    {
        return Motorcycle::query()
            ->when($motorcycleId, fn ($query) => $query->whereKey($motorcycleId))
            ->with([
                'customer',
                'motorcycleModel.serviceIntervals.serviceType',
                // Only finished work counts as history.
                'jobOrders' => fn ($query) => $query->finished()->with('serviceTypes'),
            ])
            ->get();
    }

    /** Turns a motorcycle's job orders into the plain "visit" arrays the calculator expects. */
    private function visitsOf(Motorcycle $motorcycle): array
    {
        return $motorcycle->jobOrders
            ->map(fn ($jobOrder) => [
                'date' => $jobOrder->service_date->format('Y-m-d'),
                'odometer' => (int) $jobOrder->odometer_at_service,
                'services' => $jobOrder->serviceTypes->pluck('id')->map(fn ($id) => (int) $id)->all(),
            ])
            ->sortBy(fn ($visit) => $visit['date'].sprintf('%07d', $visit['odometer']))
            ->values()
            ->all();
    }

    private function latestVisit(array $visits): array
    {
        return $visits[count($visits) - 1];
    }

    /** The most recent visit in which the given service type was done, or null. */
    private function lastVisitWith(array $visits, int $serviceTypeId): ?array
    {
        for ($i = count($visits) - 1; $i >= 0; $i--) {
            if (in_array($serviceTypeId, $visits[$i]['services'], true)) {
                return $visits[$i];
            }
        }

        return null;
    }
}
