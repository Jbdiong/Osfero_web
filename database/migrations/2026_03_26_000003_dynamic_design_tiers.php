<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve existing bonus values before dropping columns
        $existing = DB::table('commission_settings')->get();

        Schema::table('commission_settings', function (Blueprint $table) {
            $table->dropColumn(['design_bonus_30', 'design_bonus_40', 'design_bonus_50', 'design_bonus_70']);
        });

        Schema::table('commission_settings', function (Blueprint $table) {
            $table->json('design_tiers')->nullable()->after('design_rate');
        });

        // Migrate old fixed tiers to JSON using the saved values (or sensible defaults)
        foreach ($existing as $row) {
            $tiers = [
                ['min_qty' => 30, 'max_qty' => 39, 'bonus_percent' => (float)($row->design_bonus_30 ?? 5)],
                ['min_qty' => 40, 'max_qty' => 49, 'bonus_percent' => (float)($row->design_bonus_40 ?? 10)],
                ['min_qty' => 50, 'max_qty' => 69, 'bonus_percent' => (float)($row->design_bonus_50 ?? 15)],
                ['min_qty' => 70, 'max_qty' => null, 'bonus_percent' => (float)($row->design_bonus_70 ?? 20)],
            ];

            DB::table('commission_settings')
                ->where('id', $row->id)
                ->update(['design_tiers' => json_encode($tiers)]);
        }
    }

    public function down(): void
    {
        Schema::table('commission_settings', function (Blueprint $table) {
            $table->dropColumn('design_tiers');
        });

        Schema::table('commission_settings', function (Blueprint $table) {
            $table->decimal('design_bonus_30', 5, 2)->default(5.00);
            $table->decimal('design_bonus_40', 5, 2)->default(10.00);
            $table->decimal('design_bonus_50', 5, 2)->default(15.00);
            $table->decimal('design_bonus_70', 5, 2)->default(20.00);
        });
    }
};
