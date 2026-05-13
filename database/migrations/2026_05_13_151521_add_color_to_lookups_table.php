<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lookups', function (Blueprint $table) {
            $table->string('color', 50)->nullable()->after('tenant_id');
        });

        // Seed default colors for the 5 global Todolist Status entries
        $defaults = [
            'Waiting List' => '#a855f7', // purple
            'To do'        => '#3b82f6', // blue
            'In Progress'  => '#f97316', // orange
            'Pending'      => '#ec4899', // pink
            'Completed'    => '#22c55e', // green
        ];

        $parent = DB::table('lookups')->where('name', 'Todolist Status')->first();
        if ($parent) {
            foreach ($defaults as $name => $color) {
                DB::table('lookups')
                    ->where('parent_id', $parent->id)
                    ->where('name', $name)
                    ->whereNull('tenant_id')
                    ->update(['color' => $color]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('lookups', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
