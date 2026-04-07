<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This adds "Speed Boosts" to your most common search queries.
     */
    public function up(): void
    {
        Schema::table('item_variants', function (Blueprint $table) {
            // 1. Fast lookup for all variants belonging to an item
            // 2. Fast lookup when searching for a specific SKU
            $table->index(['item_id', 'sku']); 
            
            // 3. Fast lookup for scanning barcodes on mobile
            $table->index('barcode'); 
        });

        Schema::table('stocks', function (Blueprint $table) {
            // 4. Fast lookup for "What is the stock in this specific bin?"
            $table->index(['location_id', 'variant_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            // 5. Fast lookup for the audit trail of a specific item
            $table->index(['variant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_variants', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'sku']);
            $table->dropIndex(['barcode']);
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropIndex(['location_id', 'variant_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['variant_id', 'created_at']);
        });
    }
};