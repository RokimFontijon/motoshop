<?php

namespace Tests\Feature;

use App\Models\JobOrder;
use App\Models\JobOrderPart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

/** Job orders: validation, ownership rules, and the stock that the database trigger keeps correct. */
class JobOrderTest extends TestCase
{
    use CreatesShopData;
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Creating
    // ------------------------------------------------------------------

    public function test_a_mechanic_creates_a_job_order_that_belongs_to_them(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $other = $this->makeUser('mechanic');
        $oil = $this->makeServiceType();
        $motorcycle = $this->makeMotorcycle(['current_odometer' => 1000]);

        // The cheater tries to assign the job to somebody else. The controller ignores it.
        $form = $this->jobOrderForm($motorcycle, ['service_type_ids' => [$oil->id], 'mechanic_id' => $other->id]);

        $response = $this->actingAs($mechanic)->post('/job-orders', $form);

        $jobOrder = JobOrder::firstOrFail();
        $response->assertRedirect("/job-orders/{$jobOrder->id}");
        $this->assertSame($mechanic->id, $jobOrder->mechanic_id);
        $this->assertSame(sprintf('JO-%05d', $jobOrder->id), $jobOrder->job_number);
        $this->assertSame([$oil->id], $jobOrder->serviceTypes->pluck('id')->all());
    }

    public function test_an_admin_can_assign_the_job_order_to_a_mechanic(): void
    {
        $admin = $this->makeUser('admin');
        $mechanic = $this->makeUser('mechanic');
        $motorcycle = $this->makeMotorcycle();

        $this->actingAs($admin)->post('/job-orders', $this->jobOrderForm($motorcycle, ['mechanic_id' => $mechanic->id]));

        $this->assertSame($mechanic->id, JobOrder::firstOrFail()->mechanic_id);
    }

    public function test_the_motorcycle_odometer_follows_the_newest_reading(): void
    {
        $motorcycle = $this->makeMotorcycle(['current_odometer' => 1000]);

        $this->actingAs($this->makeUser('mechanic'))
            ->post('/job-orders', $this->jobOrderForm($motorcycle, ['odometer_at_service' => 1600]));

        $this->assertSame(1600, $motorcycle->fresh()->current_odometer);
    }

    public function test_the_odometer_cannot_go_backwards(): void
    {
        $motorcycle = $this->makeMotorcycle(['current_odometer' => 8000]);
        $this->makeJobOrder($motorcycle, ['odometer_at_service' => 8000], [], 10);

        $this->actingAs($this->makeUser('mechanic'))
            ->post('/job-orders', $this->jobOrderForm($motorcycle, ['odometer_at_service' => 7000]))
            ->assertSessionHasErrors('odometer_at_service');

        $this->assertSame(1, JobOrder::count());
    }

    public function test_a_job_order_is_validated(): void
    {
        $motorcycle = $this->makeMotorcycle();
        $this->actingAs($this->makeUser('mechanic'));

        $this->post('/job-orders', [])->assertSessionHasErrors(['motorcycle_id', 'service_date', 'odometer_at_service', 'status', 'labor_cost']);

        $this->post('/job-orders', $this->jobOrderForm($motorcycle, ['service_date' => now()->addDays(3)->toDateString()]))
            ->assertSessionHasErrors('service_date');
        $this->post('/job-orders', $this->jobOrderForm($motorcycle, ['status' => 'finished-ish']))
            ->assertSessionHasErrors('status');
        $this->post('/job-orders', $this->jobOrderForm($motorcycle, ['labor_cost' => -5]))
            ->assertSessionHasErrors('labor_cost');
        $this->post('/job-orders', $this->jobOrderForm($motorcycle, ['odometer_at_service' => -1]))
            ->assertSessionHasErrors('odometer_at_service');
        $this->post('/job-orders', $this->jobOrderForm($motorcycle, ['motorcycle_id' => 99999]))
            ->assertSessionHasErrors('motorcycle_id');
        $this->post('/job-orders', $this->jobOrderForm($motorcycle, ['service_type_ids' => [99999]]))
            ->assertSessionHasErrors('service_type_ids.0');

        $this->assertSame(0, JobOrder::count());
    }

    // ------------------------------------------------------------------
    // Who may change a job order
    // ------------------------------------------------------------------

    public function test_a_mechanic_cannot_open_or_change_another_mechanics_job_order(): void
    {
        $owner = $this->makeUser('mechanic');
        $intruder = $this->makeUser('mechanic');
        $part = $this->makePart();
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $owner->id, 'status' => 'ongoing']);

        $this->actingAs($intruder);

        $this->get("/job-orders/{$jobOrder->id}/edit")->assertForbidden();
        $this->put("/job-orders/{$jobOrder->id}", $this->jobOrderForm($jobOrder->motorcycle, ['labor_cost' => 1]))->assertForbidden();
        $this->post("/job-orders/{$jobOrder->id}/parts", ['part_id' => $part->id, 'quantity' => 1])->assertForbidden();

        $this->assertSame('100.00', $jobOrder->fresh()->labor_cost);
        $this->assertSame(10, $part->fresh()->stock_qty);
    }

    public function test_but_a_mechanic_can_still_read_it(): void
    {
        $owner = $this->makeUser('mechanic');
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $owner->id]);

        $this->actingAs($this->makeUser('mechanic'))->get("/job-orders/{$jobOrder->id}")->assertOk()->assertSee($jobOrder->job_number);
    }

    public function test_a_mechanic_cannot_change_a_released_job_order_but_an_admin_can(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $mechanic->id, 'status' => 'released']);

        $this->actingAs($mechanic)->get("/job-orders/{$jobOrder->id}/edit")->assertForbidden();

        $this->actingAs($this->makeUser('admin'))->get("/job-orders/{$jobOrder->id}/edit")->assertOk();
    }

    public function test_a_mechanic_can_update_their_own_job_order_but_not_reassign_it(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $other = $this->makeUser('mechanic');
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $mechanic->id, 'status' => 'ongoing'], [], 1);

        $this->actingAs($mechanic)->put("/job-orders/{$jobOrder->id}", $this->jobOrderForm($jobOrder->motorcycle, [
            'status' => 'completed',
            'labor_cost' => 250,
            'odometer_at_service' => $jobOrder->odometer_at_service,
            'service_date' => $jobOrder->service_date->toDateString(),
            'mechanic_id' => $other->id,
        ]))->assertRedirect("/job-orders/{$jobOrder->id}");

        $fresh = $jobOrder->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertSame('250.00', $fresh->labor_cost);
        $this->assertSame($mechanic->id, $fresh->mechanic_id);
    }

    // ------------------------------------------------------------------
    // Parts and stock (the trigger does the stock work)
    // ------------------------------------------------------------------

    public function test_adding_a_part_lowers_the_stock_and_copies_the_price(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $part = $this->makePart('OIL-1L', 10, 280);
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $mechanic->id, 'status' => 'ongoing']);

        $this->actingAs($mechanic)->post("/job-orders/{$jobOrder->id}/parts", ['part_id' => $part->id, 'quantity' => 3])
            ->assertSessionHas('status');

        $this->assertSame(7, $part->fresh()->stock_qty);
        $line = JobOrderPart::firstOrFail();
        $this->assertSame(3, $line->quantity);
        $this->assertSame('280.00', $line->unit_price);

        // The shop raises the price later. The old job order keeps the old price.
        $part->update(['unit_price' => 350]);
        $this->assertSame('280.00', $line->fresh()->unit_price);
    }

    public function test_adding_the_same_part_twice_makes_one_line(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $part = $this->makePart('OIL-1L', 10);
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $mechanic->id, 'status' => 'ongoing']);

        $this->actingAs($mechanic);
        $this->post("/job-orders/{$jobOrder->id}/parts", ['part_id' => $part->id, 'quantity' => 2]);
        $this->post("/job-orders/{$jobOrder->id}/parts", ['part_id' => $part->id, 'quantity' => 3]);

        $this->assertSame(1, JobOrderPart::count());
        $this->assertSame(5, JobOrderPart::firstOrFail()->quantity);
        $this->assertSame(5, $part->fresh()->stock_qty);
    }

    public function test_more_than_the_stock_is_refused(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $part = $this->makePart('OIL-1L', 4);
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $mechanic->id, 'status' => 'ongoing']);

        $this->actingAs($mechanic)->post("/job-orders/{$jobOrder->id}/parts", ['part_id' => $part->id, 'quantity' => 5])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(4, $part->fresh()->stock_qty);
        $this->assertSame(0, JobOrderPart::count());
    }

    public function test_the_database_itself_refuses_to_take_more_than_the_stock(): void
    {
        // Even if the PHP check is skipped (a bug, another program), the trigger protects the stock.
        $part = $this->makePart('OIL-1L', 2);
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle());

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('Not enough stock');

        $jobOrder->partLines()->create(['part_id' => $part->id, 'quantity' => 3, 'unit_price' => 100]);
    }

    public function test_part_quantity_is_validated(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $part = $this->makePart();
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $mechanic->id, 'status' => 'ongoing']);

        $this->actingAs($mechanic);
        $this->post("/job-orders/{$jobOrder->id}/parts", ['part_id' => $part->id, 'quantity' => 0])->assertSessionHasErrors('quantity');
        $this->post("/job-orders/{$jobOrder->id}/parts", ['part_id' => $part->id, 'quantity' => 'abc'])->assertSessionHasErrors('quantity');
        $this->post("/job-orders/{$jobOrder->id}/parts", ['quantity' => 1])->assertSessionHasErrors('part_id');
    }

    public function test_removing_a_part_gives_it_back_to_stock(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $part = $this->makePart('OIL-1L', 10);
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $mechanic->id, 'status' => 'ongoing']);

        $this->actingAs($mechanic)->post("/job-orders/{$jobOrder->id}/parts", ['part_id' => $part->id, 'quantity' => 4]);
        $this->assertSame(6, $part->fresh()->stock_qty);

        $line = JobOrderPart::firstOrFail();
        $this->delete("/job-orders/{$jobOrder->id}/parts/{$line->id}")->assertSessionHas('status');

        $this->assertSame(10, $part->fresh()->stock_qty);
        $this->assertSame(0, JobOrderPart::count());
    }

    public function test_a_part_line_cannot_be_removed_through_the_wrong_job_order(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $part = $this->makePart();
        $motorcycle = $this->makeMotorcycle();
        $mine = $this->makeJobOrder($motorcycle, ['mechanic_id' => $mechanic->id, 'status' => 'ongoing']);
        $other = $this->makeJobOrder($motorcycle, ['mechanic_id' => $mechanic->id, 'status' => 'ongoing']);
        $line = $other->partLines()->create(['part_id' => $part->id, 'quantity' => 1, 'unit_price' => 280]);

        $this->actingAs($mechanic)->delete("/job-orders/{$mine->id}/parts/{$line->id}")->assertNotFound();

        $this->assertDatabaseHas('job_order_parts', ['id' => $line->id]);
    }

    public function test_deleting_a_job_order_returns_its_parts_to_stock(): void
    {
        $part = $this->makePart('OIL-1L', 10);
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle());
        $jobOrder->partLines()->create(['part_id' => $part->id, 'quantity' => 6, 'unit_price' => 280]);
        $this->assertSame(4, $part->fresh()->stock_qty);

        $this->actingAs($this->makeUser('admin'))->delete("/job-orders/{$jobOrder->id}")->assertRedirect('/job-orders');

        $this->assertSame(10, $part->fresh()->stock_qty);
        $this->assertDatabaseMissing('job_orders', ['id' => $jobOrder->id]);
    }

    // ------------------------------------------------------------------
    // Totals
    // ------------------------------------------------------------------

    public function test_the_total_is_labor_plus_parts_and_matches_the_database_view(): void
    {
        $part = $this->makePart('OIL-1L', 10);
        $other = $this->makePart('SPK-STD', 10, 180);
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['labor_cost' => 150]);
        $jobOrder->partLines()->create(['part_id' => $part->id, 'quantity' => 2, 'unit_price' => 280]);   // 560
        $jobOrder->partLines()->create(['part_id' => $other->id, 'quantity' => 1, 'unit_price' => 180]);  // 180

        $this->assertEqualsWithDelta(890.0, $jobOrder->fresh('partLines')->grandTotal(), 0.001);
        $this->assertEquals(890.00, \Illuminate\Support\Facades\DB::table('v_job_order_totals')->where('job_order_id', $jobOrder->id)->value('grand_total'));
    }
}
