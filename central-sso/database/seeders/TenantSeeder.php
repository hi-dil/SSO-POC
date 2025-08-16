<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default tenants with specific IDs
        $tenant1 = Tenant::create([
            'id' => 'tenant1',
            'name' => 'Tenant One',
            'slug' => 'tenant1',
            'domain' => 'tenant1.localhost:8001',
            'is_active' => true,
            'data' => [
                'plan' => 'basic',
                'features' => [
                    'analytics' => true,
                ]
            ]
        ]);

        $tenant2 = Tenant::create([
            'id' => 'tenant2',
            'name' => 'Tenant Two', 
            'slug' => 'tenant2',
            'domain' => 'tenant2.localhost:8002',
            'is_active' => true,
            'data' => [
                'plan' => 'premium',
                'features' => [
                    'analytics' => true,
                    'api' => true,
                ]
            ]
        ]);

        // Create admin user
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@sso.localhost',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        // Create test user
        $testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // Attach users to tenants
        $admin->tenants()->attach([$tenant1->id, $tenant2->id]);
        $testUser->tenants()->attach([$tenant1->id]);

        $this->command->info('Tenants and test users created successfully!');
        $this->command->info('Admin: admin@sso.localhost / password');
        $this->command->info('Test User: test@example.com / password');
    }
}
