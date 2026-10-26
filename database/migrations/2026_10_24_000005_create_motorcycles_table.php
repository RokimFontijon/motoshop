<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * motorcycles: one row per physical motorcycle (identified by plate number).
     * One customer can own many motorcycles (1 : many).
     */
    public function up(): void
    {
        Schema::create('motorcycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('motorcycle_model_id')->constrained('motorcycle_models')->restrictOnDelete();
            $table->string('plate_number', 15)->unique();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color', 30)->nullable();
            $table->unsignedInteger('current_odometer')->default(0);
            $table->timestamps();

            // Foreign keys are not indexed automatically in PostgreSQL.
            $table->index('customer_id', 'idx_motorcycles_customer');
            $table->index('motorcycle_model_id', 'idx_motorcycles_model');
        });

        // PostgreSQL has no UNSIGNED type, so CHECK constraints protect the data.
        DB::statement('ALTER TABLE motorcycles ADD CONSTRAINT chk_motorcycles_odometer CHECK (current_odometer >= 0)');
        DB::statement('ALTER TABLE motorcycles ADD CONSTRAINT chk_motorcycles_year CHECK (year IS NULL OR year BETWEEN 1980 AND 2100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('motorcycles');
    }
};
