<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create group-level parent records (grandparents)
        $groups = [
            ['name' => 'Lead',     'label' => 'Lead',     'child_ids' => [3, 4, 5, 6, 7, 8, 9]],
            ['name' => 'Order',    'label' => 'Order',    'child_ids' => [60]],
            ['name' => 'Event',    'label' => 'Event',    'child_ids' => [11]],
            ['name' => 'Renewal',  'label' => 'Renewal',  'child_ids' => [12]],
            ['name' => 'Todolist', 'label' => 'Todolist', 'child_ids' => [10]],
            ['name' => 'General',  'label' => 'General',  'child_ids' => [1, 2]], // Priority + Audit Type
        ];

        foreach ($groups as $group) {
            $groupId = DB::table('lookups')->insertGetId([
                'name'       => $group['name'],
                'label'      => $group['label'],
                'parent_id'  => null,
                'tenant_id'  => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('lookups')
                ->whereIn('id', $group['child_ids'])
                ->update(['parent_id' => $groupId]);
        }
    }

    public function down(): void
    {
        // Remove group parent_id from original parent lookups (IDs 1-12, 60)
        DB::table('lookups')
            ->whereIn('id', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 60])
            ->update(['parent_id' => null]);

        // Delete the group records by name
        DB::table('lookups')
            ->whereIn('name', ['Lead', 'Order', 'Event', 'Renewal', 'Todolist', 'General'])
            ->whereNull('tenant_id')
            ->whereNull('parent_id')
            ->delete();
    }
};
