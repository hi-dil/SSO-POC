<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        // Create tenants
        $tenant1 = Tenant::create([
            'id' => 'tenant1',
            'data' => [
                'name' => 'Tenant 1',
                'slug' => 'tenant1',
                'domain' => 'tenant1.local'
            ],
        ]);

        $tenant2 = Tenant::create([
            'id' => 'tenant2',
            'data' => [
                'name' => 'Tenant 2',
                'slug' => 'tenant2',
                'domain' => 'tenant2.local'
            ],
        ]);

        // Create users for tenant1
        $user1 = User::create([
            'name' => 'Tenant 1 User',
            'email' => 'user@tenant1.com',
            'password' => Hash::make('tenant123'),
            'is_admin' => false,
        ]);
        $user1->tenants()->attach($tenant1);

        $admin1 = User::create([
            'name' => 'Tenant 1 Admin',
            'email' => 'admin@tenant1.com',
            'password' => Hash::make('admin123'),
            'is_admin' => true,
        ]);
        $admin1->tenants()->attach($tenant1);

        // Create users for tenant2
        $user2 = User::create([
            'name' => 'Tenant 2 User',
            'email' => 'user@tenant2.com',
            'password' => Hash::make('tenant456'),
            'is_admin' => false,
        ]);
        $user2->tenants()->attach($tenant2);

        $admin2 = User::create([
            'name' => 'Tenant 2 Admin',
            'email' => 'admin@tenant2.com',
            'password' => Hash::make('admin456'),
            'is_admin' => true,
        ]);
        $admin2->tenants()->attach($tenant2);

        // Create a super admin with access to both tenants
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@sso.com',
            'password' => Hash::make('super123'),
            'is_admin' => true,
        ]);
        $superAdmin->tenants()->attach([$tenant1->id, $tenant2->id]);

        $this->command->info('Test data seeded successfully!');
        $this->command->info('Users created:');
        $this->command->info('- user@tenant1.com (password: tenant123) - Tenant 1 User');
        $this->command->info('- admin@tenant1.com (password: admin123) - Tenant 1 Admin');
        $this->command->info('- user@tenant2.com (password: tenant456) - Tenant 2 User');
        $this->command->info('- admin@tenant2.com (password: admin456) - Tenant 2 Admin');
        $this->command->info('- superadmin@sso.com (password: super123) - Super Admin (both tenants)');
    }
}