<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * job_order_services: pivot table (many-to-many).
     * One job order can include many services, and one service type appears in
     * many job orders. The pair is the primary key, so no duplicates.
     */
    public function up(): void
    {
        Schema::create('job_order_services', function (Blueprint $table) {
            $table->foreignId('job_order_id')->constrained('job_orders')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types')->restrictOnDelete();

            $table->primary(['job_order_id', 'service_type_id']);
            $table->index('service_type_id', 'idx_jos_service_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_services');
    }
};
