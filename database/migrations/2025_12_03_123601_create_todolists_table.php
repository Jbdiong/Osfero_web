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
        Schema::create('todolists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->onDelete('cascade');
            $table->unsignedBigInteger('payment_id')->nullable(); // Will add foreign key in later migration
            $table->string('Title');
            $table->text('Description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('priority_id')->nullable()->constrained('lookups')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('todolists')->onDelete('cascade');
            $table->foreignId('status_id')->nullable()->constrained('lookups')->onDelete('set null');
            $table->unsignedInteger('position')->default(0)->comment('Position for drag and drop ordering within status');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('start_date');
            $table->index('end_date');
            $table->index('tenant_id');
            $table->index(['status_id', 'position']); // Composite index for efficient ordering by status and position
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('todolists');
    }
};
