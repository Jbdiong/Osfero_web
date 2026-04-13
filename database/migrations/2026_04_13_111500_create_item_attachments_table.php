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
        Schema::create('item_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained('item_variants')->cascadeOnDelete();
            $table->string('path'); // The path to the file in storage
            $table->string('file_name')->nullable(); // Original filename
            $table->string('mime_type')->nullable(); // Image type (e.g., image/jpeg)
            $table->unsignedInteger('sort_order')->default(0); // For ordering in a gallery
            $table->boolean('is_main')->default(false); // To mark the primary thumbnail
            $table->timestamps();

            // Indexes for performance
            $table->index('tenant_id');
            $table->index(['item_id', 'item_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_attachments');
    }
};
