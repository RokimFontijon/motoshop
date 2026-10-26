<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * job_orders: one row each time a motorcycle comes to the shop.
     * The odometer reading and service date saved here are the data used to
     * estimate the next service due date.
     *
     * There is NO total column. The total is calculated (labor + parts) by the
     * view v_job_order_totals, so the total can never get out of sync.
     */
    public function up(): void
    {
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();
            // Filled in right after insert, e.g. JO-00012 (nullable + unique).
            $table->string('job_number', 20)->nullable()->unique();
            $table->foreignId('motorcycle_id')->constrained('motorcycles')->restrictOnDelete();
            // If a mechanic account is deleted, the job order stays (set to NULL).
            $table->foreignId('mechanic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('service_date');
            $table->unsignedInteger('odometer_at_service');
            $table->text('complaint')->nullable();
            $table->string('status', 15)->default('pending');
            $table->decimal('labor_cost', 10, 2)->default(0);
            $table->timestamps();

            // Used by "service history of one motorcycle, newest first".
            $table->index(['motorcycle_id', 'service_date'], 'idx_job_orders_motorcycle_date');
            $table->index('status', 'idx_job_orders_status');
            $table->index('service_date', 'idx_job_orders_service_date');
            $table->index('mechanic_id', 'idx_job_orders_mechanic');
        });

        DB::statement("ALTER TABLE job_orders ADD CONSTRAINT chk_job_orders_status CHECK (status IN ('pending', 'ongoing', 'completed', 'released'))");
        DB::statement('ALTER TABLE job_orders ADD CONSTRAINT chk_job_orders_labor CHECK (labor_cost >= 0)');
        DB::statement('ALTER TABLE job_orders ADD CONSTRAINT chk_job_orders_odometer CHECK (odometer_at_service >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};
