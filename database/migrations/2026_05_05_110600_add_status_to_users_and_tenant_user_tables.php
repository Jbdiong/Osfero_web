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
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->after('password');
        });

        Schema::table('tenant_user', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->after('display_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('tenant_user', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
