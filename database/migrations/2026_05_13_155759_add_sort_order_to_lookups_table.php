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
            $table->unsignedInteger('sort_order')->default(0)->after('color');
        });

        // Seed correct sort order for Todolist Status
        $parent = DB::table('lookups')->where('name', 'Todolist Status')->first();
        if ($parent) {
            $order = [
                'Waiting List' => 1,
                'To do'        => 2,
                'In Progress'  => 3,
                'Pending'      => 4,
                'Completed'    => 5,
            ];
            foreach ($order as $name => $position) {
                DB::table('lookups')
                    ->where('parent_id', $parent->id)
                    ->where('name', $name)
                    ->whereNull('tenant_id')
                    ->update(['sort_order' => $position]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('lookups', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
