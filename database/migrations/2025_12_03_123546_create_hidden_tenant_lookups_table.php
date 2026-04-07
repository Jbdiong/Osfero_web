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
        Schema::create('hidden_tenant_lookups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lookup_id')->constrained('lookups')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamps();
            
            // Index for multi-tenant queries
            $table->index('tenant_id');
            // Unique constraint to prevent duplicate entries
            $table->unique(['lookup_id', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hidden_tenant_lookups');
    }
};
