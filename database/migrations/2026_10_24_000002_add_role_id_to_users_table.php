<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel already creates the users table (first migration). Here we only
     * add the role foreign key to it.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // restrictOnDelete: a role cannot be deleted while users still use it.
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
