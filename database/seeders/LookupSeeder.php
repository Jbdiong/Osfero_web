<?php

namespace Database\Seeders;

use App\Models\Lookup;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lookups = [];

        // ========== GROUP PARENTS (top-level) ==========

        $generalGroup = Lookup::firstOrCreate(['name' => 'General', 'parent_id' => null, 'tenant_id' => null], ['label' => 'General']);
        $leadGroup    = Lookup::firstOrCreate(['name' => 'Lead',    'parent_id' => null, 'tenant_id' => null], ['label' => 'Lead']);
        $orderGroup   = Lookup::firstOrCreate(['name' => 'Order',   'parent_id' => null, 'tenant_id' => null], ['label' => 'Order']);
        $eventGroup   = Lookup::firstOrCreate(['name' => 'Event',   'parent_id' => null, 'tenant_id' => null], ['label' => 'Event']);
        $renewalGroup = Lookup::firstOrCreate(['name' => 'Renewal', 'parent_id' => null, 'tenant_id' => null], ['label' => 'Renewal']);
        $todoGroup    = Lookup::firstOrCreate(['name' => 'Todolist','parent_id' => null, 'tenant_id' => null], ['label' => 'Todolist']);

        // ========== GENERAL (Priority + Audit Type) ==========

        $priorityParent = Lookup::firstOrCreate(
            ['name' => 'Priority', 'parent_id' => $generalGroup->id, 'tenant_id' => null],
            ['label' => 'Priority']
        );
        foreach (['Urgent', 'High', 'Normal', 'Low'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $priorityParent->id, 'tenant_id' => null];
        }

        $auditTypeParent = Lookup::firstOrCreate(
            ['name' => 'Audit Type', 'parent_id' => $generalGroup->id, 'tenant_id' => null],
            ['label' => 'Audit Type']
        );
        foreach (['Login', 'LogOut', 'Create', 'Update', 'Completed', 'Delete'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $auditTypeParent->id, 'tenant_id' => null];
        }

        // ========== LEAD ==========

        $leadPaymentParent = Lookup::firstOrCreate(
            ['name' => 'Lead Payment', 'parent_id' => $leadGroup->id, 'tenant_id' => null],
            ['label' => 'Lead Payment']
        );
        foreach (['None', 'Pending', 'Completed', 'Rejected'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $leadPaymentParent->id, 'tenant_id' => null];
        }

        $leadStatusParent = Lookup::firstOrCreate(
            ['name' => 'Lead Status', 'parent_id' => $leadGroup->id, 'tenant_id' => null],
            ['label' => 'Lead Status']
        );
        foreach (['In Progress', 'Rejected', 'Scheduled Meeting', 'Completed', 'Pending Renewal'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $leadStatusParent->id, 'tenant_id' => null];
        }

        $leadRelevantParent = Lookup::firstOrCreate(
            ['name' => 'Lead Relevant', 'parent_id' => $leadGroup->id, 'tenant_id' => null],
            ['label' => 'Lead Relevant']
        );
        foreach (['Relevant', 'Irrelevant'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $leadRelevantParent->id, 'tenant_id' => null];
        }

        $leadSourceParent = Lookup::firstOrCreate(
            ['name' => 'Lead Source', 'parent_id' => $leadGroup->id, 'tenant_id' => null],
            ['label' => 'Lead Source']
        );
        foreach (['xhs', 'Facebook', 'Instagram', 'Others'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $leadSourceParent->id, 'tenant_id' => null];
        }

        $leadLanguageParent = Lookup::firstOrCreate(
            ['name' => 'Lead Language', 'parent_id' => $leadGroup->id, 'tenant_id' => null],
            ['label' => 'Lead Language']
        );
        foreach (['English', 'Chinese', 'Malay', 'Others'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $leadLanguageParent->id, 'tenant_id' => null];
        }

        $leadIrrelevantReasonParent = Lookup::firstOrCreate(
            ['name' => 'Lead Irrelevant Reason', 'parent_id' => $leadGroup->id, 'tenant_id' => null],
            ['label' => 'Lead Irrelevant Reason']
        );
        foreach (['Price', 'Missing in Action'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $leadIrrelevantReasonParent->id, 'tenant_id' => null];
        }

        $leadIndustryParent = Lookup::firstOrCreate(
            ['name' => 'Lead Industry', 'parent_id' => $leadGroup->id, 'tenant_id' => null],
            ['label' => 'Lead Industry']
        );
        foreach (['Beauty', 'Car', 'Wellness', 'Others'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $leadIndustryParent->id, 'tenant_id' => null];
        }

        // ========== ORDER ==========

        $orderProgressParent = Lookup::firstOrCreate(
            ['name' => 'Order Progress', 'parent_id' => $orderGroup->id, 'tenant_id' => null],
            ['label' => 'Order Progress']
        );

        // ========== TODOLIST ==========

        $todolistStatusParent = Lookup::firstOrCreate(
            ['name' => 'Todolist Status', 'parent_id' => $todoGroup->id, 'tenant_id' => null],
            ['label' => 'Todolist Status']
        );
        foreach (['Waiting List', 'To do', 'In Progress', 'Pending', 'Completed'] as $v) {
            Lookup::firstOrCreate(
                ['name' => $v, 'parent_id' => $todolistStatusParent->id, 'tenant_id' => null],
                ['label' => $v]
            );
        }

        // ========== EVENT ==========

        $eventStatusParent = Lookup::firstOrCreate(
            ['name' => 'Event Status', 'parent_id' => $eventGroup->id, 'tenant_id' => null],
            ['label' => 'Event Status']
        );
        foreach (['Completed', 'Not Yet'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $eventStatusParent->id, 'tenant_id' => null];
        }

        // ========== RENEWAL ==========

        $renewalStatusParent = Lookup::firstOrCreate(
            ['name' => 'Renewal Status', 'parent_id' => $renewalGroup->id, 'tenant_id' => null],
            ['label' => 'Renewal Status']
        );
        foreach (['Pending Renewal', 'Followed Up', 'On Going', 'Ended'] as $v) {
            $lookups[] = ['name' => $v, 'label' => $v, 'parent_id' => $renewalStatusParent->id, 'tenant_id' => null];
        }

        // Insert all child lookups
        Lookup::insert($lookups);

        $this->command->info('Global lookups seeded successfully with group hierarchy!');
    }
}
