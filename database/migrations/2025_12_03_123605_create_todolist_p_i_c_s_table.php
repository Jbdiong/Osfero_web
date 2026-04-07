<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('todolist_p_i_c_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todolist_id')->constrained('todolists')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamps();
            
            // Index for multi-tenant queries
            $table->index('tenant_id');
            // Unique constraint to prevent duplicate assignments
            $table->unique(['todolist_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todolist_p_i_c_s');
    }
};
