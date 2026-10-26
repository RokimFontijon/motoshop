<?php

namespace Tests\Feature;

use App\Services\ServiceDueReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

/** The service reminders: data from the database goes through the calculator and onto the pages. */
class ServiceReminderTest extends TestCase
{
    use CreatesShopData;
    use RefreshDatabase;

    public function test_a_service_past_its_time_limit_is_overdue(): void
    {
        $oil = $this->makeServiceType('Change Oil');
        $motorcycle = $this->makeMotorcycle(['current_odometer' => 1100]);
        $this->makeInterval($motorcycle, $oil, 3000, 3);
        // Oil changed 120 days ago; the 3 month limit is about 30 days past.
        $this->makeJobOrder($motorcycle, ['odometer_at_service' => 1000, 'status' => 'released'], [$oil], 120);

        $rows = app(ServiceDueReport::class)->dueRows();

        $this->assertCount(1, $rows);
        $this->assertSame('Change Oil', $rows[0]['service']);
        $this->assertSame('overdue', $rows[0]['status']);
        $this->assertSame('time', $rows[0]['basis']);
        $this->assertLessThan(0, $rows[0]['days_left']);
    }

    public function test_a_recent_service_is_ok(): void
    {
        $oil = $this->makeServiceType('Change Oil');
        $motorcycle = $this->makeMotorcycle();
        $this->makeInterval($motorcycle, $oil, 3000, 3);
        $this->makeJobOrder($motorcycle, ['status' => 'released'], [$oil], 5);

        $rows = app(ServiceDueReport::class)->dueRows($motorcycle->id);

        $this->assertSame('ok', $rows[0]['status']);
    }

    public function test_a_heavy_rider_is_due_by_mileage_before_the_time_limit(): void
    {
        $oil = $this->makeServiceType('Change Oil');
        $motorcycle = $this->makeMotorcycle(['current_odometer' => 3000]);
        $this->makeInterval($motorcycle, $oil, 3000, 12);

        // 2,000 km in 20 days = 100 km/day. At 3,000 km the next oil change (at 4,000 km) is 10 days away.
        $this->makeJobOrder($motorcycle, ['odometer_at_service' => 1000, 'status' => 'released'], [$oil], 20);
        $this->makeJobOrder($motorcycle, ['odometer_at_service' => 3000, 'status' => 'released'], [], 0);

        // The last oil change was at 1,000 km, so it is due at 4,000 km: 1,000 km left = 10 days.
        $row = app(ServiceDueReport::class)->dueRows($motorcycle->id)[0];

        $this->assertSame('mileage', $row['basis']);
        $this->assertSame(4000, $row['due_odometer']);
        $this->assertEqualsWithDelta(100.0, $row['km_per_day'], 0.01);
        $this->assertSame(10, $row['days_left']);
        $this->assertSame('due_soon', $row['status']);
    }

    public function test_unfinished_job_orders_do_not_count_as_service_history(): void
    {
        $oil = $this->makeServiceType('Change Oil');
        $motorcycle = $this->makeMotorcycle();
        $this->makeInterval($motorcycle, $oil, 3000, 3);
        $this->makeJobOrder($motorcycle, ['status' => 'ongoing'], [$oil], 1);
        $this->makeJobOrder($motorcycle, ['status' => 'pending'], [$oil], 0);

        $this->assertSame([], app(ServiceDueReport::class)->dueRows());
    }

    public function test_a_service_that_was_never_done_is_not_listed(): void
    {
        $oil = $this->makeServiceType('Change Oil');
        $brakes = $this->makeServiceType('Brake Pad Replacement');
        $motorcycle = $this->makeMotorcycle();
        $this->makeInterval($motorcycle, $oil, 3000, 3);
        $this->makeInterval($motorcycle, $brakes, 10000, 12);
        $this->makeJobOrder($motorcycle, ['status' => 'released'], [$oil], 10);

        $services = array_column(app(ServiceDueReport::class)->dueRows(), 'service');

        $this->assertSame(['Change Oil'], $services);
    }

    public function test_the_dashboard_counts_overdue_and_due_soon_services(): void
    {
        $oil = $this->makeServiceType('Change Oil');

        $late = $this->makeMotorcycle(['plate_number' => 'LATE 111']);
        $this->makeInterval($late, $oil, 3000, 3);
        $this->makeJobOrder($late, ['status' => 'released'], [$oil], 150);

        $soon = $this->makeMotorcycle(['plate_number' => 'SOON 222']);
        $this->makeJobOrder($soon, ['status' => 'released'], [$oil], 80);   // 3 months = ~90 days: due in ~10 days

        $fine = $this->makeMotorcycle(['plate_number' => 'FINE 333']);
        $this->makeJobOrder($fine, ['status' => 'released'], [$oil], 2);

        $response = $this->actingAs($this->makeUser('admin'))->get('/dashboard')->assertOk();

        $response->assertSee('LATE 111')->assertSee('Overdue');
        $response->assertSee('SOON 222')->assertSee('Due soon');
        $response->assertDontSee('FINE 333');
    }

    public function test_the_dashboard_lists_open_job_orders_and_low_stock(): void
    {
        $this->makePart('LOW-1', 1, 100, 5);
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['status' => 'ongoing']);

        $this->actingAs($this->makeUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($jobOrder->job_number)
            ->assertSee('Part LOW-1');
    }

    public function test_the_accuracy_report_compares_estimated_and_actual_dates(): void
    {
        $oil = $this->makeServiceType('Change Oil');
        $motorcycle = $this->makeMotorcycle();
        $this->makeInterval($motorcycle, $oil, 3000, 3);

        // Oil changed 120 days ago and again 20 days ago. Estimated next: 3 months after the first one.
        $this->makeJobOrder($motorcycle, ['odometer_at_service' => 1000, 'status' => 'released'], [$oil], 120);
        $this->makeJobOrder($motorcycle, ['odometer_at_service' => 2200, 'status' => 'released'], [$oil], 20);

        $accuracy = app(ServiceDueReport::class)->accuracy();

        $this->assertCount(1, $accuracy['rows']);
        $this->assertNotNull($accuracy['mean_abs_days']);

        $this->actingAs($this->makeUser())->get('/reports')->assertOk()->assertSee('Estimated vs actual');
    }

    public function test_a_motorcycle_with_no_service_history_shows_a_friendly_message(): void
    {
        $motorcycle = $this->makeMotorcycle();

        $this->actingAs($this->makeUser())
            ->get("/motorcycles/{$motorcycle->id}")
            ->assertOk()
            ->assertSee('nothing to estimate');
    }
}
