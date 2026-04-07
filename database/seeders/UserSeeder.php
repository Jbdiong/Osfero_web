<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SystemRole;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get system roles
        $superadminRole = SystemRole::where('role', 'Superadmin')->first();
        $tenantAdminRole = SystemRole::where('role', 'Tenant admin')->first();
        $managerRole = SystemRole::where('role', 'Manager')->first();
        $staffRole = SystemRole::where('role', 'Staff')->first();

        if (!$superadminRole || !$tenantAdminRole || !$managerRole || !$staffRole) {
            $this->command->error('Please run SystemRoleSeeder first to create system roles.');
            return;
        }

        // Get or create first tenant for tenant-based users
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->warn('No tenant found. Creating a default tenant for tenant-based users...');
            $tenant = Tenant::create([
                'name' => 'Default Tenant',
                'description' => 'Default tenant for system users',
            ]);
        }

        $users = [
            [
                'name' => 'Superadmin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role_id' => $superadminRole->id,
                'tenant_id' => null, // Superadmin is system-wide
            ],
            [
                'name' => 'Tenantadmin',
                'email' => 'tenantadmin@example.com',
                'password' => Hash::make('password'),
                'role_id' => $tenantAdminRole->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@example.com',
                'password' => Hash::make('password'),
                'role_id' => $managerRole->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Staff',
                'email' => 'staff@example.com',
                'password' => Hash::make('password'),
                'role_id' => $staffRole->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Staff2',
                'email' => 'staff2@example.com',
                'password' => Hash::make('password'),
                'role_id' => $staffRole->id,
                'tenant_id' => 2,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Users seeded successfully!');
        $this->command->info('Superadmin: admin@example.com / password (system-wide, no tenant)');
        $this->command->info('Tenantadmin: tenantadmin@example.com / password');
        $this->command->info('Manager: manager@example.com / password');
        $this->command->info('Staff: staff@example.com / password');
    }
}
