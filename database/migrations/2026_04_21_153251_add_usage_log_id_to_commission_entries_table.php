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
        Schema::table('commission_entries', function (Blueprint $table) {
            $table->foreignId('usage_log_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_entries', function (Blueprint $table) {
            $table->dropForeign(['usage_log_id']);
            $table->dropColumn('usage_log_id');
        });
    }
};
