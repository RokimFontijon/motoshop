<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

/** Smoke test: every page renders without an error, for both roles. */
class PagesTest extends TestCase
{
    use CreatesShopData;
    use RefreshDatabase;

    private function sampleData(): array
    {
        $mechanic = $this->makeUser('mechanic');
        $oil = $this->makeServiceType('Change Oil');
        $motorcycle = $this->makeMotorcycle();
        $this->makeInterval($motorcycle, $oil, 3000, 3);
        $part = $this->makePart('OIL-1L', 10);
        $first = $this->makeJobOrder($motorcycle, ['mechanic_id' => $mechanic->id, 'odometer_at_service' => 1000], [$oil], 100);
        $second = $this->makeJobOrder($motorcycle, ['mechanic_id' => $mechanic->id, 'odometer_at_service' => 2500, 'status' => 'completed'], [$oil], 30);
        $second->partLines()->create(['part_id' => $part->id, 'quantity' => 2, 'unit_price' => 280]);

        return [$motorcycle, $second, $part, $mechanic];
    }

    public function test_every_page_loads_for_an_admin(): void
    {
        [$motorcycle, $jobOrder, $part] = $this->sampleData();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin);

        $urls = [
            '/dashboard', '/reports',
            '/customers', '/customers/create', "/customers/{$motorcycle->customer_id}", "/customers/{$motorcycle->customer_id}/edit",
            '/motorcycles', '/motorcycles/create', "/motorcycles/{$motorcycle->id}", "/motorcycles/{$motorcycle->id}/edit",
            '/job-orders', '/job-orders/create', "/job-orders/create?motorcycle_id={$motorcycle->id}",
            "/job-orders/{$jobOrder->id}", "/job-orders/{$jobOrder->id}/edit",
            '/parts', '/parts?low=1', '/parts/create', "/parts/{$part->id}/edit",
            '/users', '/users/create', "/users/{$admin->id}/edit",
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_every_page_a_mechanic_may_use_loads(): void
    {
        [$motorcycle, $jobOrder, , $mechanic] = $this->sampleData();

        $this->actingAs($mechanic);

        $urls = [
            '/dashboard', '/reports', '/customers', '/customers/create', "/customers/{$motorcycle->customer_id}",
            '/motorcycles', "/motorcycles/{$motorcycle->id}", '/job-orders', '/job-orders/create',
            "/job-orders/{$jobOrder->id}", "/job-orders/{$jobOrder->id}/edit", '/parts',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_searching_and_filtering_work(): void
    {
        [$motorcycle, $jobOrder] = $this->sampleData();

        $this->actingAs($this->makeUser('admin'));

        $this->get('/customers?q=Customer')->assertOk()->assertSee($motorcycle->customer->full_name);
        $this->get('/customers?q=zzzzzz')->assertOk()->assertSee('No customers found');
        $this->get('/motorcycles?q='.urlencode($motorcycle->plate_number))->assertOk()->assertSee($motorcycle->plate_number);
        $this->get('/job-orders?status=completed')->assertOk()->assertSee($jobOrder->job_number);
        $this->get('/job-orders?status=pending')->assertOk()->assertSee('No job orders found');
        $this->get('/parts?q=OIL')->assertOk()->assertSee('Part OIL-1L');
    }

    public function test_the_job_order_list_shows_the_total_calculated_by_the_database_view(): void
    {
        [, $jobOrder] = $this->sampleData();

        // labor 100 + 2 parts x 280 = 660
        $this->actingAs($this->makeUser('admin'))
            ->get('/job-orders')
            ->assertSee('660.00');
    }

    public function test_an_unknown_page_gives_a_404(): void
    {
        $this->actingAs($this->makeUser())->get('/customers/999999')->assertNotFound();
    }
}
