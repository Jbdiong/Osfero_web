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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('Shop_Name');
            $table->string('Industry')->nullable();
            $table->string('Manual_Industry')->nullable();
            $table->timestamp('last_modified')->nullable();
            $table->string('Source')->nullable();
            $table->string('Manual_Source')->nullable();
            $table->string('Language')->nullable();
            $table->string('Manual_Language')->nullable();
            $table->string('City')->nullable();
            $table->string('State')->nullable();
            $table->string('Country')->nullable();
            $table->text('address_1')->nullable();
            $table->text('address_2')->nullable();
            $table->text('address_3')->nullable();
            $table->boolean('relevant')->default(true);
            $table->text('Irrelevant_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('lookups')->onDelete('set null');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('status_id');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
