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
        // Remove from items
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['supplier_price', 'sales_price']);
        });

        // Add to item_variants
        Schema::table('item_variants', function (Blueprint $table) {
            $table->decimal('supplier_price', 15, 2)->nullable()->after('barcode');
            $table->decimal('sales_price', 15, 2)->nullable()->after('supplier_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_variants', function (Blueprint $table) {
            $table->dropColumn(['supplier_price', 'sales_price']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->decimal('supplier_price', 15, 2)->nullable()->after('description');
            $table->decimal('sales_price', 15, 2)->nullable()->after('supplier_price');
        });
    }
};
