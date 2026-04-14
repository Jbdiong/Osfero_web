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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        // Backfill slug from existing tenant names
        \DB::table('tenants')->get()->each(function ($tenant) {
            \DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['slug' => \Illuminate\Support\Str::slug($tenant->name)]);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
