<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Note: User creation requires tenant_id and role_id
        // This is commented out as it requires a tenant and system role to exist first
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        //     'tenant_id' => 1,
        //     'role_id' => 1,
        // ]);

        // Seed system roles, countries/states/cities, and lookups
        $this->call([
            SystemRoleSeeder::class,
            CountryStateCitySeeder::class,
            LookupSeeder::class,
            TenantSeeder::class,
            UserSeeder::class,
        ]);
    }
}
