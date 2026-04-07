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
        Schema::create('lookups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label')->nullable();
            $table->unsignedBigInteger('lookup_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->timestamps();
            
            // Index for multi-tenant queries
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lookups');
    }
};
