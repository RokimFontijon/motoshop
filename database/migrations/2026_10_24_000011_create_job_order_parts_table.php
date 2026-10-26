<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * job_order_parts: the parts used in a job order (one row per part line).
     *
     * unit_price is copied from parts.unit_price when the line is added. This is
     * a deliberate, small denormalization: if the shop changes a part's price
     * next month, old job orders must keep the price the customer really paid.
     */
    public function up(): void
    {
        Schema::create('job_order_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained('job_orders')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            $table->index('job_order_id', 'idx_jop_job_order');
            $table->index('part_id', 'idx_jop_part');
        });

        DB::statement('ALTER TABLE job_order_parts ADD CONSTRAINT chk_jop_quantity CHECK (quantity > 0)');
        DB::statement('ALTER TABLE job_order_parts ADD CONSTRAINT chk_jop_price CHECK (unit_price >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_parts');
    }
};
