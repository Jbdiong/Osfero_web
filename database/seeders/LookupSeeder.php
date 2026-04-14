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

        // ========== GLOBAL LOOKUPS (tenant_id = null for system-wide sharing) ==========
        
        // Priority
        $priorityParent = Lookup::create([
            'name' => 'Priority',
            'label' => 'Priority',
            'parent_id' => null,
            'tenant_id' => null, // Global - shared across all tenants
        ]);

        $priorities = ['Urgent', 'High', 'Normal', 'Low'];
        foreach ($priorities as $priority) {
            $lookups[] = [
                'name' => $priority,
                'label' => $priority,
                'parent_id' => $priorityParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // Audit Type
        $auditTypeParent = Lookup::create([
            'name' => 'Audit Type',
            'label' => 'Audit Type',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $auditTypes = ['Login', 'LogOut', 'Create', 'Update', 'Completed', 'Delete'];
        foreach ($auditTypes as $auditType) {
            $lookups[] = [
                'name' => $auditType,
                'label' => $auditType,
                'parent_id' => $auditTypeParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // ========== LEAD LOOKUPS ==========
        
        // Lead Payment
        $leadPaymentParent = Lookup::create([
            'name' => 'Lead Payment',
            'label' => 'Lead Payment',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $leadPayments = ['None', 'Pending', 'Completed', 'Rejected'];
        foreach ($leadPayments as $payment) {
            $lookups[] = [
                'name' => $payment,
                'label' => $payment,
                'parent_id' => $leadPaymentParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // Lead Status
        $leadStatusParent = Lookup::create([
            'name' => 'Lead Status',
            'label' => 'Lead Status',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $leadStatuses = ['In Progress', 'Rejected', 'Scheduled Meeting', 'Completed', 'Pending Renewal'];
        foreach ($leadStatuses as $status) {
            $lookups[] = [
                'name' => $status,
                'label' => $status,
                'parent_id' => $leadStatusParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // Lead Relevant
        $leadRelevantParent = Lookup::create([
            'name' => 'Lead Relevant',
            'label' => 'Lead Relevant',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $leadRelevants = ['Relevant', 'Irrelevant'];
        foreach ($leadRelevants as $relevant) {
            $lookups[] = [
                'name' => $relevant,
                'label' => $relevant,
                'parent_id' => $leadRelevantParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // Lead Source
        $leadSourceParent = Lookup::create([
            'name' => 'Lead Source',
            'label' => 'Lead Source',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $leadSources = ['xhs', 'Facebook', 'Instagram', 'Others'];
        foreach ($leadSources as $source) {
            $lookups[] = [
                'name' => $source,
                'label' => $source,
                'parent_id' => $leadSourceParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // Lead Language
        $leadLanguageParent = Lookup::create([
            'name' => 'Lead Language',
            'label' => 'Lead Language',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $leadLanguages = ['English', 'Chinese', 'Malay', 'Others'];
        foreach ($leadLanguages as $language) {
            $lookups[] = [
                'name' => $language,
                'label' => $language,
                'parent_id' => $leadLanguageParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // Lead Irrelevant Reason
        $leadIrrelevantReasonParent = Lookup::create([
            'name' => 'Lead Irrelevant Reason',
            'label' => 'Lead Irrelevant Reason',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $leadIrrelevantReasons = ['Price', 'Missing in Action'];
        foreach ($leadIrrelevantReasons as $reason) {
            $lookups[] = [
                'name' => $reason,
                'label' => $reason,
                'parent_id' => $leadIrrelevantReasonParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // Lead Industry
        $leadIndustryParent = Lookup::create([
            'name' => 'Lead Industry',
            'label' => 'Lead Industry',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $leadIndustries = ['Beauty', 'Car', 'Wellness', 'Others'];
        foreach ($leadIndustries as $industry) {
            $lookups[] = [
                'name' => $industry,
                'label' => $industry,
                'parent_id' => $leadIndustryParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // ========== TODOLIST LOOKUPS ==========
        
        // Todolist Status
        $todolistStatusParent = Lookup::create([
            'name' => 'Todolist Status',
            'label' => 'Todolist Status',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $todolistStatuses = ['To do', 'In Progress', 'Pending', 'Completed'];
        foreach ($todolistStatuses as $status) {
            $lookups[] = [
                'name' => $status,
                'label' => $status,
                'parent_id' => $todolistStatusParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // ========== EVENT LOOKUPS ==========
        
        // Event Status
        $eventStatusParent = Lookup::create([
            'name' => 'Event Status',
            'label' => 'Event Status',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $eventStatuses = ['Completed', 'Not Yet'];
        foreach ($eventStatuses as $status) {
            $lookups[] = [
                'name' => $status,
                'label' => $status,
                'parent_id' => $eventStatusParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // ========== RENEWAL LOOKUPS ==========
        
        // Renewal Status
        $renewalStatusParent = Lookup::create([
            'name' => 'Renewal Status',
            'label' => 'Renewal Status',
            'parent_id' => null,
            'tenant_id' => null, // Global
        ]);

        $renewalStatuses = ['Pending Renewal', 'Followed Up', 'On Going'];
        foreach ($renewalStatuses as $status) {
            $lookups[] = [
                'name' => $status,
                'label' => $status,
                'parent_id' => $renewalStatusParent->id,
                'tenant_id' => null, // Global
            ];
        }

        // Insert all child lookups
        Lookup::insert($lookups);

        $this->command->info('Global lookups seeded successfully! (tenant_id = null for system-wide sharing)');
    }
}
