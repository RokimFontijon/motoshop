<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * parts: spare parts inventory.
     * stock_qty is changed automatically by a database trigger whenever a part
     * is added to or removed from a job order (see database/sql/01_db_objects.sql).
     */
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_code', 30)->unique();
            $table->string('name', 120);
            $table->decimal('unit_price', 10, 2);
            $table->integer('stock_qty')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->timestamps();

            $table->index('name', 'idx_parts_name');
        });

        DB::statement('ALTER TABLE parts ADD CONSTRAINT chk_parts_price CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE parts ADD CONSTRAINT chk_parts_stock CHECK (stock_qty >= 0)');
        DB::statement('ALTER TABLE parts ADD CONSTRAINT chk_parts_reorder CHECK (reorder_level >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
