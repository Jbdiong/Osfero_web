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
            $table->foreignId('todolist_id')->nullable()->after('order_item_id')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Remove the old link
            if (Schema::hasColumn('usage_logs', 'commission_entry_id')) {
                $table->dropForeign(['commission_entry_id']);
                $table->dropColumn('commission_entry_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_logs', function (Blueprint $table) {
            $table->dropForeign(['todolist_id']);
            $table->dropColumn('todolist_id');
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
            
            $table->foreignId('commission_entry_id')->nullable()->constrained('commission_entries')->nullOnDelete();
        });
    }
};
