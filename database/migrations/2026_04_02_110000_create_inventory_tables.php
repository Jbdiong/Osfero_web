<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. BRANDS (Reference table for your 200+ brands)
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // 2. UOM CODES (Units of Measure)
        Schema::create('uom_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(0); // 0 for global defaults
            $table->string('code', 10); // e.g., PCS, KG, M
            $table->string('name', 50);
            $table->integer('precision')->default(0);
            $table->timestamps();
        });

        // 3. WAREHOUSES (The physical buildings)
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // 4. CATEGORIES & TEMPLATES (Folder + Blueprints)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 100);
            $table->string('slug', 100)->nullable();
            $table->json('template_schema')->nullable(); // The "Template" lives here for easy access
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // 5. ITEMS (The "Master" definition - The Product Identity)
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('brand_id')->nullable(); // Link to Brands
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('specs')->nullable(); // Global technical specs (Material, etc)
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('restrict');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });

        // 6. ITEM VARIANTS (The actual SKUs - "Size/Variation")
        Schema::create('item_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->unsignedBigInteger('uom_id')->nullable(); // Link to UOM table
            $table->decimal('min_stock_level', 10, 2)->default(0);
            $table->json('variant_specs')->nullable(); // Variant specs (Size, Color)
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('uom_codes')->onDelete('set null');
        });

        // 7. LOCATIONS (The specific Bins/Shelves inside a Warehouse)
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('name'); // e.g., "Aisle 4, Bin B"
            $table->string('barcode')->nullable()->unique();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });

        // 8. STOCKS (Current Snapshot: Maps a Variant to a Location)
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variant_id'); 
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('variant_id')->references('id')->on('item_variants')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            
            // Ensures a variant only has one row per location
            $table->unique(['variant_id', 'location_id']); 
        });

        // 9. STOCK MOVEMENTS (The Audit Log / Ledger)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('variant_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable(); // For Invoice ID or PO ID
            $table->string('reference_type')->nullable(); // e.g., 'Invoice', 'Adjustment'
            $table->enum('type', ['in', 'out', 'transfer_in', 'transfer_out', 'adjustment']);
            $table->decimal('quantity', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('variant_id')->references('id')->on('item_variants')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('item_variants');
        Schema::dropIfExists('items');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('uom_codes');
        Schema::dropIfExists('brands');
    }
};