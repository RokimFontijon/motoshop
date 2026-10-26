<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Creates the PostgreSQL features that Laravel's schema builder cannot
     * express: trigger + function, view, partial index and row level security.
     * Then it applies the privileges for the database roles.
     *
     * The SQL lives in plain .sql files so it can also be read and run by hand
     * with psql during the COMP 018 defense.
     */
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/01_db_objects.sql')));
        DB::unprepared(file_get_contents(database_path('sql/02_privileges.sql')));
    }

    public function down(): void
    {
        // The view and the trigger go away with their tables; the functions do not.
        DB::unprepared('
            DROP VIEW IF EXISTS v_job_order_totals;
            DROP FUNCTION IF EXISTS fn_adjust_part_stock() CASCADE;
            DROP FUNCTION IF EXISTS app_is_admin() CASCADE;
            DROP FUNCTION IF EXISTS app_user_id() CASCADE;
            DROP INDEX IF EXISTS idx_parts_low_stock;
        ');
    }
};
