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
        Schema::table('phones', function (Blueprint $table) {
            $table->dropColumn(['code', 'code_expiring']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phones', function (Blueprint $table) {
            $table->string('code')->nullable()->after('phone_number');
            $table->timestamp('code_expiring')->nullable()->after('code');
        });
    }
};
