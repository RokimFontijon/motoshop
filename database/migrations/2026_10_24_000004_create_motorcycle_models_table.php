<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * motorcycle_models: the catalog of models (Honda Click 125, Yamaha Mio...).
     * Brand and model are stored once here, not repeated on every motorcycle.
     */
    public function up(): void
    {
        Schema::create('motorcycle_models', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 50);
            $table->string('name', 80);
            $table->unsignedSmallInteger('engine_cc')->nullable();
            $table->timestamps();

            // The same brand + model name cannot be entered twice.
            $table->unique(['brand', 'name'], 'uq_motorcycle_models_brand_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motorcycle_models');
    }
};
