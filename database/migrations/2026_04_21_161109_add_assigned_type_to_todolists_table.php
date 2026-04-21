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
        Schema::table('todolists', function (Blueprint $table) {
            $table->string('assigned_type')->nullable()->after('quantity'); // design, video, ads_management
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('todolists', function (Blueprint $table) {
            $table->dropColumn('assigned_type');
        });
    }
};
