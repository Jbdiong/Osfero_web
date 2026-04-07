<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old tables
        Schema::dropIfExists('commission_ads_clients');
        Schema::dropIfExists('commissions');

        // Commission settings: one row per tenant (rates managed by tenant admin)
        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->decimal('design_rate', 10, 2)->default(140.00);   // RM per design
            $table->decimal('design_bonus_30', 5, 2)->default(5.00);  // % if qty >= 30
            $table->decimal('design_bonus_40', 5, 2)->default(10.00);
            $table->decimal('design_bonus_50', 5, 2)->default(15.00);
            $table->decimal('design_bonus_70', 5, 2)->default(20.00);
            $table->decimal('sales_rate', 5, 2)->default(10.00);      // % of package value
            $table->decimal('ads_fee', 10, 2)->default(149.00);       // RM per client per month
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Individual commission entries logged by staff (accumulate per month)
        Schema::create('commission_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type');                              // design | ads_management | sales
            $table->integer('year');
            $table->integer('month');                            // 1-12
            $table->string('name');                              // project / client name
            $table->integer('quantity')->nullable()->default(0); // design only
            $table->decimal('package_value', 10, 2)->nullable(); // sales only
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_entries');
        Schema::dropIfExists('commission_settings');
    }
};
