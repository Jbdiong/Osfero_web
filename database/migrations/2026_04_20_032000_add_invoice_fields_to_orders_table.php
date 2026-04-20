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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('invoice_no')->nullable()->after('total_amount')->comment('Invoice number');
            $table->string('invoice_file')->nullable()->after('invoice_no')->comment('Path/URL to the PDF invoice for the customer to download');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_no', 'invoice_file']);
        });
    }
};
