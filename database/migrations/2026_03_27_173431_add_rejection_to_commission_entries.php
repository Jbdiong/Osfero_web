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
            $table->boolean('is_rejected')->default(false)->after('is_approved');
            $table->string('rejection_reason')->nullable()->after('is_rejected');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_entries', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['is_rejected', 'rejection_reason', 'rejected_at', 'rejected_by']);
        });
    }
};
