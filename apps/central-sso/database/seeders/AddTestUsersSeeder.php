<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

class AddTestUsersSeeder extends Seeder
{
    public function run()
    {
        // Get existing tenants by ID
        $tenant1 = Tenant::find('tenant1');
        $tenant2 = Tenant::find('tenant2');

        if (!$tenant1 || !$tenant2) {
            $this->command->error('Tenants not found. Please seed tenants first.');
            return;
        }

        // Clear existing test users if they exist
        $testEmails = [
            'user@tenant1.com',
            'admin@tenant1.com', 
            'user@tenant2.com',
            'admin@tenant2.com',
            'superadmin@sso.com'
        ];

        User::whereIn('email', $testEmails)->delete();

        // Create users for tenant1
        $user1 = User::create([
            'name' => 'Tenant 1 User',
            'email' => 'user@tenant1.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);
        $user1->tenants()->attach($tenant1);

        $admin1 = User::create([
            'name' => 'Tenant 1 Admin',
            'email' => 'admin@tenant1.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);
        $admin1->tenants()->attach($tenant1);

        // Create users for tenant2
        $user2 = User::create([
            'name' => 'Tenant 2 User',
            'email' => 'user@tenant2.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);
        $user2->tenants()->attach($tenant2);

        $admin2 = User::create([
            'name' => 'Tenant 2 Admin',
            'email' => 'admin@tenant2.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);
        $admin2->tenants()->attach($tenant2);

        // Create a super admin with access to both tenants
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@sso.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);
        $superAdmin->tenants()->attach([$tenant1->id, $tenant2->id]);
        
        // Assign super-admin role to the super admin user
        $superAdminRole = \App\Models\Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdmin->assignRole($superAdminRole);
        }

        $this->command->info('Test users added successfully!');
        $this->command->info('Users created:');
        $this->command->info('- user@tenant1.com (password: password) - Tenant 1 User');
        $this->command->info('- admin@tenant1.com (password: password) - Tenant 1 Admin');
        $this->command->info('- user@tenant2.com (password: password) - Tenant 2 User');
        $this->command->info('- admin@tenant2.com (password: password) - Tenant 2 Admin');
        $this->command->info('- superadmin@sso.com (password: password) - Super Admin (both tenants)');
    }
}