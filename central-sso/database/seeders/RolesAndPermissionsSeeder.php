<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage-tenants',
            'manage-users', 
            'manage-tenant-users',
            'view-analytics',
            'manage-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        $tenantAdminRole = Role::create(['name' => 'tenant-admin']);
        $tenantAdminRole->givePermissionTo([
            'manage-tenant-users',
            'view-analytics',
        ]);

        $userRole = Role::create(['name' => 'user']);
        // Users don't get any special permissions by default

        // Assign super-admin role to existing super admin users
        $superAdmins = User::where('email', 'superadmin@sso.com')->get();
        foreach ($superAdmins as $admin) {
            $admin->assignRole('super-admin');
        }

        // Assign tenant-admin role to existing admin users
        $tenantAdmins = User::where('is_admin', true)
            ->where('email', '!=', 'superadmin@sso.com')
            ->get();
        
        foreach ($tenantAdmins as $admin) {
            $admin->assignRole('tenant-admin');
        }

        // Assign user role to regular users
        $regularUsers = User::where('is_admin', false)->get();
        foreach ($regularUsers as $user) {
            $user->assignRole('user');
        }

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Roles created:');
        $this->command->info('- super-admin: Full system access');
        $this->command->info('- tenant-admin: Manage specific tenant users');
        $this->command->info('- user: Regular user access');
        $this->command->info('');
        $this->command->info('Permissions created:');
        foreach ($permissions as $permission) {
            $this->command->info("- $permission");
        }
    }
}