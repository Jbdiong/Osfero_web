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
        // start_date is already in the create_renewals_table migration
        // This migration can be removed or left empty if start_date was added later
        // If start_date doesn't exist, uncomment below:
        // Schema::table('renewals', function (Blueprint $table) {
        //     $table->date('start_date')->nullable()->after('label');
        //     $table->index('start_date');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('renewals', function (Blueprint $table) {
        //     $table->dropIndex(['start_date']);
        //     $table->dropColumn('start_date');
        // });
    }
};
