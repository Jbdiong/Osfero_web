<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type'); // design | ads_management | sales
            $table->integer('year');
            $table->integer('month'); // 1-12

            // Design fields
            $table->integer('design_quantity')->nullable()->default(0);
            $table->decimal('design_rate', 10, 2)->default(140.00); // RM140 per design

            // Ads Management fields
            $table->decimal('ads_amount', 10, 2)->nullable()->default(0); // total from ads clients

            // Sales fields
            $table->decimal('sales_package_value', 10, 2)->nullable()->default(0); // value of package sold
            $table->decimal('sales_commission_rate', 5, 2)->default(10.00); // 10%

            // Computed/stored commission amount
            $table->decimal('commission_amount', 10, 2)->default(0);

            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique per user per type per month/year
            $table->unique(['tenant_id', 'user_id', 'type', 'year', 'month']);
        });

        Schema::create('commission_ads_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commission_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('client_name');
            $table->decimal('monthly_fee', 10, 2)->default(149.00); // RM149 per client per month
            $table->timestamps();

            $table->foreign('commission_id')->references('id')->on('commissions')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_ads_clients');
        Schema::dropIfExists('commissions');
    }
};
