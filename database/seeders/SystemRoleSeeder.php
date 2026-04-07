<?php

namespace Database\Seeders;

use App\Models\SystemRole;
use Illuminate\Database\Seeder;

class SystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'role' => 'Superadmin',
                'description' => 'System super administrator with full access to all tenants and system settings. Reserved for developers.',
            ],
            [
                'role' => 'Tenant admin',
                'description' => 'Administrator for a specific tenant with full access to manage their organization.',
            ],
            [
                'role' => 'Manager',
                'description' => 'Manager role with access to manage leads, events, and todolists within their tenant.',
            ],
            [
                'role' => 'Staff',
                'description' => 'Staff member with basic access to view and update leads, events, and todolists.',
            ],
        ];

        foreach ($roles as $role) {
            SystemRole::firstOrCreate(
                ['role' => $role['role']],
                ['description' => $role['description']]
            );
        }

        $this->command->info('System roles seeded successfully!');
    }
}
