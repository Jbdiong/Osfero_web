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
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->foreignId('commission_entry_id')->nullable()->after('notes')->constrained('commission_entries')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropForeign(['commission_entry_id']);
            $table->dropColumn('commission_entry_id');
        });
    }
};
