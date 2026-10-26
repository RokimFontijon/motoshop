<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Run with:  php artisan migrate:fresh --seed
 *
 * Demo logins (password for all: "password"):
 *     admin@motorshop.test      (admin)
 *     mechanic1@motorshop.test  (mechanic)
 *     mechanic2@motorshop.test  (mechanic)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Row level security is active on job_orders. Nobody is logged in while
        // seeding, so tell PostgreSQL that this session acts as an admin.
        if (DB::getDriverName() === 'pgsql') {
            DB::select("select set_config('app.role', 'admin', false)");
        }

        $this->call([
            CatalogSeeder::class,   // roles, models, service types, intervals, parts
            DemoDataSeeder::class,  // users, customers, motorcycles, job orders
        ]);
    }
}
