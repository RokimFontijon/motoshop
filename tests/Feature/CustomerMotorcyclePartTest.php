<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Motorcycle;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

/** CRUD, validation and mass assignment for customers, motorcycles and parts. */
class CustomerMotorcyclePartTest extends TestCase
{
    use CreatesShopData;
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Customers
    // ------------------------------------------------------------------

    public function test_a_customer_can_be_added_edited_and_deleted(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        $this->post('/customers', ['full_name' => 'Juan Dela Cruz', 'phone' => '0917 123 4567', 'address' => 'Calauan'])
            ->assertRedirect();
        $customer = Customer::firstOrFail();
        $this->assertSame('Juan Dela Cruz', $customer->full_name);

        $this->put("/customers/{$customer->id}", ['full_name' => 'Juan D. Cruz'])->assertRedirect("/customers/{$customer->id}");
        $this->assertSame('Juan D. Cruz', $customer->fresh()->full_name);

        $this->delete("/customers/{$customer->id}")->assertRedirect('/customers');
        $this->assertSame(0, Customer::count());
    }

    public function test_a_customer_needs_a_name(): void
    {
        $this->actingAs($this->makeUser());

        $this->post('/customers', ['full_name' => '', 'phone' => '0917'])->assertSessionHasErrors('full_name');
        $this->post('/customers', ['full_name' => str_repeat('a', 121)])->assertSessionHasErrors('full_name');

        $this->assertSame(0, Customer::count());
    }

    public function test_a_customer_with_motorcycles_cannot_be_deleted(): void
    {
        $motorcycle = $this->makeMotorcycle();

        $this->actingAs($this->makeUser('admin'))
            ->delete("/customers/{$motorcycle->customer_id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('customers', ['id' => $motorcycle->customer_id]);
    }

    public function test_mass_assignment_ignores_columns_that_are_not_fillable(): void
    {
        // "id" is not in $fillable, so Laravel silently ignores it.
        $customer = new Customer(['id' => 999, 'full_name' => 'Maria']);

        $this->assertNull($customer->id);
        $this->assertSame('Maria', $customer->full_name);
    }

    // ------------------------------------------------------------------
    // Motorcycles
    // ------------------------------------------------------------------

    public function test_a_motorcycle_can_be_added_and_the_plate_is_cleaned(): void
    {
        $motorcycle = $this->makeMotorcycle();
        $this->actingAs($this->makeUser());

        $this->post('/motorcycles', [
            'customer_id' => $motorcycle->customer_id,
            'motorcycle_model_id' => $motorcycle->motorcycle_model_id,
            'plate_number' => '  nbx 4821 ',
            'year' => 2021,
            'color' => 'Blue',
            'current_odometer' => 500,
        ])->assertRedirect();

        $this->assertDatabaseHas('motorcycles', ['plate_number' => 'NBX 4821']);
    }

    public function test_a_motorcycle_is_validated(): void
    {
        $existing = $this->makeMotorcycle(['plate_number' => 'ABC 1234']);
        $this->actingAs($this->makeUser());

        $valid = [
            'customer_id' => $existing->customer_id,
            'motorcycle_model_id' => $existing->motorcycle_model_id,
            'plate_number' => 'XYZ 9999',
            'current_odometer' => 10,
        ];

        $this->post('/motorcycles', array_merge($valid, ['plate_number' => 'abc 1234']))->assertSessionHasErrors('plate_number');
        $this->post('/motorcycles', array_merge($valid, ['plate_number' => '']))->assertSessionHasErrors('plate_number');
        $this->post('/motorcycles', array_merge($valid, ['customer_id' => 9999]))->assertSessionHasErrors('customer_id');
        $this->post('/motorcycles', array_merge($valid, ['motorcycle_model_id' => null]))->assertSessionHasErrors('motorcycle_model_id');
        $this->post('/motorcycles', array_merge($valid, ['current_odometer' => -1]))->assertSessionHasErrors('current_odometer');
        $this->post('/motorcycles', array_merge($valid, ['year' => 1900]))->assertSessionHasErrors('year');

        $this->assertSame(1, Motorcycle::count());
    }

    public function test_a_motorcycle_may_keep_its_own_plate_when_edited(): void
    {
        $motorcycle = $this->makeMotorcycle(['plate_number' => 'ABC 1234']);
        $this->actingAs($this->makeUser());

        $this->put("/motorcycles/{$motorcycle->id}", [
            'customer_id' => $motorcycle->customer_id,
            'motorcycle_model_id' => $motorcycle->motorcycle_model_id,
            'plate_number' => 'ABC 1234',
            'current_odometer' => 2000,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2000, $motorcycle->fresh()->current_odometer);
    }

    public function test_a_motorcycle_with_job_orders_cannot_be_deleted(): void
    {
        $motorcycle = $this->makeMotorcycle();
        $this->makeJobOrder($motorcycle);

        $this->actingAs($this->makeUser('admin'))->delete("/motorcycles/{$motorcycle->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('motorcycles', ['id' => $motorcycle->id]);
    }

    public function test_a_motorcycle_without_job_orders_can_be_deleted_by_an_admin(): void
    {
        $motorcycle = $this->makeMotorcycle();

        $this->actingAs($this->makeUser('admin'))->delete("/motorcycles/{$motorcycle->id}")->assertRedirect('/motorcycles');

        $this->assertSame(0, Motorcycle::count());
    }

    // ------------------------------------------------------------------
    // Parts
    // ------------------------------------------------------------------

    public function test_an_admin_can_add_edit_and_delete_a_part(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $this->post('/parts', ['part_code' => 'SPK-1', 'name' => 'Spark Plug', 'unit_price' => 180, 'stock_qty' => 20, 'reorder_level' => 5])
            ->assertRedirect('/parts');
        $part = Part::firstOrFail();

        $this->put("/parts/{$part->id}", ['part_code' => 'SPK-1', 'name' => 'Spark Plug', 'unit_price' => 200, 'stock_qty' => 35, 'reorder_level' => 5])
            ->assertRedirect('/parts');
        $this->assertSame('200.00', $part->fresh()->unit_price);
        $this->assertSame(35, $part->fresh()->stock_qty);

        $this->delete("/parts/{$part->id}")->assertRedirect('/parts');
        $this->assertSame(0, Part::count());
    }

    public function test_a_part_is_validated(): void
    {
        $this->makePart('OIL-1L');
        $this->actingAs($this->makeUser('admin'));

        $valid = ['part_code' => 'NEW-1', 'name' => 'New part', 'unit_price' => 10, 'stock_qty' => 1, 'reorder_level' => 1];

        $this->post('/parts', array_merge($valid, ['part_code' => 'OIL-1L']))->assertSessionHasErrors('part_code');
        $this->post('/parts', array_merge($valid, ['name' => '']))->assertSessionHasErrors('name');
        $this->post('/parts', array_merge($valid, ['unit_price' => -1]))->assertSessionHasErrors('unit_price');
        $this->post('/parts', array_merge($valid, ['unit_price' => 'free']))->assertSessionHasErrors('unit_price');
        $this->post('/parts', array_merge($valid, ['stock_qty' => -3]))->assertSessionHasErrors('stock_qty');
        $this->post('/parts', array_merge($valid, ['reorder_level' => 'many']))->assertSessionHasErrors('reorder_level');

        $this->assertSame(1, Part::count());
    }

    public function test_a_part_used_in_a_job_order_cannot_be_deleted(): void
    {
        $part = $this->makePart();
        $this->makeJobOrder($this->makeMotorcycle())->partLines()->create(['part_id' => $part->id, 'quantity' => 1, 'unit_price' => 280]);

        $this->actingAs($this->makeUser('admin'))->delete("/parts/{$part->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('parts', ['id' => $part->id]);
    }

    public function test_the_low_stock_filter_only_shows_parts_at_or_below_their_reorder_level(): void
    {
        $this->makePart('LOW-1', 2, 100, 5);     // low
        $this->makePart('EDGE-1', 5, 100, 5);    // exactly at the level = low
        $this->makePart('FINE-1', 50, 100, 5);   // fine

        $this->assertSame(['LOW-1', 'EDGE-1'], Part::lowStock()->orderBy('id')->pluck('part_code')->all());

        $this->actingAs($this->makeUser())
            ->get('/parts?low=1')
            ->assertSee('Part LOW-1')
            ->assertSee('Part EDGE-1')
            ->assertDontSee('Part FINE-1');
    }

    public function test_the_database_refuses_negative_stock_and_prices(): void
    {
        $part = $this->makePart();

        $this->expectException(\Illuminate\Database\QueryException::class);
        $part->update(['stock_qty' => -1]);
    }
}
