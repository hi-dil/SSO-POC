<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new permissions for user profile management
        $permissions = [
            // User Contacts Management
            [
                'name' => 'users.contacts.view',
                'slug' => 'users.contacts.view',
                'category' => 'users',
                'description' => 'View user contact information',
                'is_system' => true
            ],
            [
                'name' => 'users.contacts.manage',
                'slug' => 'users.contacts.manage',
                'category' => 'users',
                'description' => 'Add, edit, and delete user contact information',
                'is_system' => true
            ],
            
            // User Addresses Management
            [
                'name' => 'users.addresses.view',
                'slug' => 'users.addresses.view',
                'category' => 'users',
                'description' => 'View user address information',
                'is_system' => true
            ],
            [
                'name' => 'users.addresses.manage',
                'slug' => 'users.addresses.manage',
                'category' => 'users',
                'description' => 'Add, edit, and delete user address information',
                'is_system' => true
            ],
            
            // User Family Management
            [
                'name' => 'users.family.view',
                'slug' => 'users.family.view',
                'category' => 'users',
                'description' => 'View user family member information',
                'is_system' => true
            ],
            [
                'name' => 'users.family.manage',
                'slug' => 'users.family.manage',
                'category' => 'users',
                'description' => 'Add, edit, and delete user family member information',
                'is_system' => true
            ],
            
            // User Social Media Management
            [
                'name' => 'users.social.view',
                'slug' => 'users.social.view',
                'category' => 'users',
                'description' => 'View user social media profiles',
                'is_system' => true
            ],
            [
                'name' => 'users.social.manage',
                'slug' => 'users.social.manage',
                'category' => 'users',
                'description' => 'Add, edit, and delete user social media profiles',
                'is_system' => true
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create($permissionData);
        }

        // Assign new permissions to Super Admin role
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $newPermissions = Permission::whereIn('slug', [
                'users.contacts.view',
                'users.contacts.manage',
                'users.addresses.view',
                'users.addresses.manage',
                'users.family.view',
                'users.family.manage',
                'users.social.view',
                'users.social.manage',
            ])->get();
            
            $superAdminRole->givePermissionTo($newPermissions);
        }

        // Assign view permissions to Admin role
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $viewPermissions = Permission::whereIn('slug', [
                'users.contacts.view',
                'users.addresses.view',
                'users.family.view',
                'users.social.view',
            ])->get();
            
            $adminRole->givePermissionTo($viewPermissions);
        }

        // Assign some permissions to Manager role
        $managerRole = Role::where('slug', 'manager')->first();
        if ($managerRole) {
            $managerPermissions = Permission::whereIn('slug', [
                'users.contacts.view',
                'users.addresses.view',
                'users.family.view',
            ])->get();
            
            $managerRole->givePermissionTo($managerPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionSlugs = [
            'users.contacts.view',
            'users.contacts.manage',
            'users.addresses.view',
            'users.addresses.manage',
            'users.family.view',
            'users.family.manage',
            'users.social.view',
            'users.social.manage',
        ];

        Permission::whereIn('slug', $permissionSlugs)->delete();
    }
};
