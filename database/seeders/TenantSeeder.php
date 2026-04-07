<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Taiyo Creative',
                'description' => 'A marketing company',
            ],
            [
                'name' => 'DKIS',
                'description' => 'Industrial supplies company',
            ],
        ];

        foreach ($tenants as $tenantData) {
            Tenant::firstOrCreate(
                ['name' => $tenantData['name']],
                $tenantData
            );
        }

        $this->command->info('Tenants seeded successfully!');
        $this->command->info('Created: Taiyo Creative (Marketing company)');
        $this->command->info('Created: DKIS (Industrial supplies company)');
    }
}
