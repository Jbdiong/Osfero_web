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
            $table->string('po_no')->nullable()->after('invoice_file');
            $table->string('po_file')->nullable()->after('po_no');
            $table->string('quotation_no')->nullable()->after('po_file');
            $table->string('quotation_file')->nullable()->after('quotation_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['po_no', 'po_file', 'quotation_no', 'quotation_file']);
        });
    }
};
