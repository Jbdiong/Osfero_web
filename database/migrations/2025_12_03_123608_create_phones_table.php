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
        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('phone_number');
            $table->string('code')->nullable();
            $table->timestamp('code_expiring')->nullable();
            $table->foreignId('priority_id')->nullable()->constrained('lookups')->onDelete('set null');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
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
        Schema::dropIfExists('phones');
    }
};
