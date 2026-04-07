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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->foreignId('status_id')->nullable()->constrained('lookups')->onDelete('set null');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('start_time');
            $table->index('end_time');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
