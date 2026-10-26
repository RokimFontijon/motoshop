<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

/** Two roles, different permissions (route protection with the "role:admin" middleware). */
class RoleAccessTest extends TestCase
{
    use CreatesShopData;
    use RefreshDatabase;

    public function test_a_mechanic_cannot_open_admin_pages(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $part = $this->makePart();

        $this->actingAs($mechanic);

        $this->get('/users')->assertForbidden();
        $this->get('/users/create')->assertForbidden();
        $this->get('/parts/create')->assertForbidden();
        $this->get("/parts/{$part->id}/edit")->assertForbidden();
    }

    public function test_a_mechanic_cannot_change_the_inventory_or_the_staff(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $part = $this->makePart();

        $this->actingAs($mechanic);

        $this->post('/parts', ['part_code' => 'X', 'name' => 'X', 'unit_price' => 1, 'stock_qty' => 1, 'reorder_level' => 1])->assertForbidden();
        $this->put("/parts/{$part->id}", ['part_code' => 'X', 'name' => 'X', 'unit_price' => 1, 'stock_qty' => 99, 'reorder_level' => 1])->assertForbidden();
        $this->delete("/parts/{$part->id}")->assertForbidden();
        $this->post('/users', ['name' => 'A', 'email' => 'a@a.com', 'role_id' => 1, 'password' => 'password123'])->assertForbidden();

        $this->assertSame(10, $part->fresh()->stock_qty);
    }

    public function test_a_mechanic_cannot_delete_records(): void
    {
        $mechanic = $this->makeUser('mechanic');
        $motorcycle = $this->makeMotorcycle();
        $jobOrder = $this->makeJobOrder($motorcycle);

        $this->actingAs($mechanic);

        $this->delete("/customers/{$motorcycle->customer_id}")->assertForbidden();
        $this->delete("/motorcycles/{$motorcycle->id}")->assertForbidden();
        $this->delete("/job-orders/{$jobOrder->id}")->assertForbidden();

        $this->assertDatabaseHas('job_orders', ['id' => $jobOrder->id]);
    }

    public function test_a_mechanic_can_see_the_inventory_but_not_the_admin_buttons(): void
    {
        $this->makePart('OIL-1L');

        $this->actingAs($this->makeUser('mechanic'))
            ->get('/parts')
            ->assertOk()
            ->assertSee('Part OIL-1L')
            ->assertDontSee('+ New part');
    }

    public function test_the_staff_menu_is_only_shown_to_admins(): void
    {
        $this->actingAs($this->makeUser('mechanic'))->get('/dashboard')->assertDontSee('>Staff<', false);
        $this->actingAs($this->makeUser('admin'))->get('/dashboard')->assertSee('>Staff<', false);
    }

    public function test_an_admin_can_open_the_admin_pages(): void
    {
        $admin = $this->makeUser('admin');
        $part = $this->makePart();

        $this->actingAs($admin);

        $this->get('/users')->assertOk();
        $this->get('/users/create')->assertOk();
        $this->get('/parts/create')->assertOk();
        $this->get("/parts/{$part->id}/edit")->assertOk();
    }

    public function test_an_admin_can_create_another_admin_account(): void
    {
        $admin = $this->makeUser('admin');
        $adminRole = $admin->role;

        $this->actingAs($admin)->post('/users', [
            'name' => 'Second Admin',
            'email' => 'second@test.local',
            'role_id' => $adminRole->id,
            'password' => 'password123',
        ])->assertRedirect('/users');

        $this->assertDatabaseHas('users', ['email' => 'second@test.local', 'role_id' => $adminRole->id]);
    }

    public function test_an_admin_cannot_delete_or_demote_their_own_account(): void
    {
        $admin = $this->makeUser('admin');
        $mechanicRole = $this->makeUser('mechanic')->role;

        $this->actingAs($admin)->delete("/users/{$admin->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->put("/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role_id' => $mechanicRole->id,
        ]);
        $this->assertSame('admin', $admin->fresh()->role->name);
    }

    public function test_deleting_a_mechanic_keeps_their_job_orders(): void
    {
        $admin = $this->makeUser('admin');
        $mechanic = $this->makeUser('mechanic');
        $jobOrder = $this->makeJobOrder($this->makeMotorcycle(), ['mechanic_id' => $mechanic->id]);

        $this->actingAs($admin)->delete("/users/{$mechanic->id}")->assertRedirect('/users');

        $this->assertDatabaseMissing('users', ['id' => $mechanic->id]);
        $this->assertNull($jobOrder->fresh()->mechanic_id);
    }
}
