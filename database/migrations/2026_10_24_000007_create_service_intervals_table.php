<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * service_intervals: many-to-many between motorcycle_models and service_types,
     * with extra columns (how many km / months between services).
     * Example: Honda Click 125 + Change Oil = every 3,000 km or 3 months.
     * Each model + service type pair appears only once (unique constraint).
     */
    public function up(): void
    {
        Schema::create('service_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('motorcycle_model_id')->constrained('motorcycle_models')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types')->cascadeOnDelete();
            $table->unsignedInteger('interval_km');
            $table->unsignedSmallInteger('interval_months');
            $table->timestamps();

            $table->unique(['motorcycle_model_id', 'service_type_id'], 'uq_service_intervals_model_type');
            $table->index('service_type_id', 'idx_service_intervals_type');
        });

        DB::statement('ALTER TABLE service_intervals ADD CONSTRAINT chk_service_intervals_positive CHECK (interval_km > 0 AND interval_months > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('service_intervals');
    }
};
