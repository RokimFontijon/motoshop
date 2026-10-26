<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 120);
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->timestamps();

            // Used for ORDER BY full_name on the customer list.
            $table->index('full_name', 'idx_customers_full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
