<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class DefaultRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create default permissions
        $permissions = [
            // User Management
            ['name' => 'View Users', 'slug' => 'users.view', 'category' => 'users', 'description' => 'Can view user list and details', 'guard_name' => 'web'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'category' => 'users', 'description' => 'Can create new users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'category' => 'users', 'description' => 'Can edit user details'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'category' => 'users', 'description' => 'Can delete users'],
            
            // Role Management
            ['name' => 'View Roles', 'slug' => 'roles.view', 'category' => 'roles', 'description' => 'Can view roles and permissions'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'category' => 'roles', 'description' => 'Can create new roles'],
            ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'category' => 'roles', 'description' => 'Can edit roles and permissions'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'category' => 'roles', 'description' => 'Can delete custom roles'],
            ['name' => 'Assign Roles', 'slug' => 'roles.assign', 'category' => 'roles', 'description' => 'Can assign roles to users'],
            
            // Tenant Management
            ['name' => 'View Tenants', 'slug' => 'tenants.view', 'category' => 'tenants', 'description' => 'Can view tenant list and details'],
            ['name' => 'Create Tenants', 'slug' => 'tenants.create', 'category' => 'tenants', 'description' => 'Can create new tenants'],
            ['name' => 'Edit Tenants', 'slug' => 'tenants.edit', 'category' => 'tenants', 'description' => 'Can edit tenant details'],
            ['name' => 'Delete Tenants', 'slug' => 'tenants.delete', 'category' => 'tenants', 'description' => 'Can delete tenants'],
            
            // System Administration
            ['name' => 'System Settings', 'slug' => 'system.settings', 'category' => 'system', 'description' => 'Can manage system settings', 'is_system' => true],
            ['name' => 'View Logs', 'slug' => 'system.logs', 'category' => 'system', 'description' => 'Can view system logs'],
            ['name' => 'Manage API', 'slug' => 'api.manage', 'category' => 'api', 'description' => 'Can manage API settings and tokens'],
        ];

        foreach ($permissions as $permissionData) {
            // Add default guard_name if not set
            if (!isset($permissionData['guard_name'])) {
                $permissionData['guard_name'] = 'web';
            }
            
            Permission::firstOrCreate(
                ['slug' => $permissionData['slug']],
                $permissionData
            );
        }

        // Create default roles
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full system access with all permissions',
                'is_system' => true,
                'permissions' => Permission::all()->pluck('slug')->toArray()
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin', 
                'description' => 'Administrator with most permissions',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit', 'users.delete',
                    'roles.view', 'roles.assign',
                    'tenants.view', 'tenants.edit',
                    'system.logs'
                ]
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Can manage users and view reports',
                'permissions' => [
                    'users.view', 'users.create', 'users.edit',
                    'roles.view', 'roles.assign',
                    'tenants.view'
                ]
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Basic user with limited access',
                'permissions' => [
                    'users.view'
                ]
            ],
            [
                'name' => 'Viewer',
                'slug' => 'viewer',
                'description' => 'Read-only access to most resources',
                'permissions' => [
                    'users.view',
                    'roles.view',
                    'tenants.view'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);
            
            // Add default guard_name if not set
            if (!isset($roleData['guard_name'])) {
                $roleData['guard_name'] = 'web';
            }
            
            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );

            // Assign permissions to role
            $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }

        $this->command->info('Default roles and permissions created successfully!');
    }
}
