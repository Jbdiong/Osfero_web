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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('record_id');
            $table->string('column_name')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('audit_type')->nullable();
            $table->string('performed_by')->nullable();
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
        Schema::dropIfExists('audits');
    }
};
