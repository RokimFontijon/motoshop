<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesShopData;
use Tests\TestCase;

/** Login, logout, registration and the "only logged-in people" rule. */
class AuthenticationTest extends TestCase
{
    use CreatesShopData;
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        foreach (['/dashboard', '/customers', '/motorcycles', '/job-orders', '/parts', '/reports', '/users'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_the_login_page_loads(): void
    {
        $this->get('/login')->assertOk()->assertSee('Log in');
    }

    public function test_a_user_can_log_in_with_the_right_password(): void
    {
        $user = $this->makeUser('admin', ['email' => 'boss@test.local', 'password' => 'secret-pass-1']);

        $this->post('/login', ['email' => 'boss@test.local', 'password' => 'secret-pass-1'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $this->makeUser('admin', ['email' => 'boss@test.local', 'password' => 'secret-pass-1']);

        $this->from('/login')
            ->post('/login', ['email' => 'boss@test.local', 'password' => 'not-the-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_needs_an_email_and_a_password(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_the_password_is_stored_hashed_not_as_plain_text(): void
    {
        $user = $this->makeUser('mechanic', ['password' => 'plain-text-pass']);

        $this->assertNotSame('plain-text-pass', $user->fresh()->password);
        $this->assertStringStartsWith('$2y$', $user->fresh()->password);
    }

    public function test_a_user_can_log_out(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_a_logged_in_user_does_not_see_the_login_page_again(): void
    {
        $this->actingAs($this->makeUser())->get('/login')->assertRedirect();
    }

    public function test_registration_creates_a_mechanic_and_logs_them_in(): void
    {
        Role::create(['name' => 'mechanic', 'description' => 'Mechanic']);

        $this->post('/register', [
            'name' => 'New Mechanic',
            'email' => 'new@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/dashboard');

        $user = User::where('email', 'new@test.local')->firstOrFail();
        $this->assertSame('mechanic', $user->role->name);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_cannot_be_used_to_become_an_admin(): void
    {
        $admin = Role::create(['name' => 'admin', 'description' => 'Admin']);
        Role::create(['name' => 'mechanic', 'description' => 'Mechanic']);

        // A cheater adds role_id to the form. Validation ignores it and the controller sets the role itself.
        $this->post('/register', [
            'name' => 'Cheater',
            'email' => 'cheater@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $admin->id,
        ]);

        $this->assertSame('mechanic', User::where('email', 'cheater@test.local')->firstOrFail()->role->name);
    }

    public function test_registration_is_validated(): void
    {
        Role::create(['name' => 'mechanic', 'description' => 'Mechanic']);
        $this->makeUser('mechanic', ['email' => 'taken@test.local']);

        $this->post('/register', [
            'name' => '',
            'email' => 'taken@test.local',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);
    }
}
