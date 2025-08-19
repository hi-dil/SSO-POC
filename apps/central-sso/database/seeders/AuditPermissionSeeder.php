<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class AuditPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create audit-related permissions
        $auditPermissions = [
            [
                'name' => 'audit.view',
                'display_name' => 'View Audit Logs',
                'category' => 'audit',
                'description' => 'View audit logs and activity history',
            ],
            [
                'name' => 'audit.export',
                'display_name' => 'Export Audit Logs',
                'category' => 'audit',
                'description' => 'Export audit logs to CSV or JSON',
            ],
            [
                'name' => 'audit.manage',
                'display_name' => 'Manage Audit Logs',
                'category' => 'audit',
                'description' => 'Manage audit log retention and cleanup',
            ],
        ];

        foreach ($auditPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'name' => $permissionData['name'],
                    'slug' => $permissionData['name'], // Use name as slug for Spatie Permission
                    'guard_name' => 'web',
                    'category' => $permissionData['category'],
                    'description' => $permissionData['description'],
                ]
            );
        }

        // Assign audit permissions to Super Admin role
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $auditPermissionNames = collect($auditPermissions)->pluck('name')->toArray();
            $permissions = Permission::whereIn('name', $auditPermissionNames)->get();
            
            foreach ($permissions as $permission) {
                if (!$superAdminRole->hasPermissionTo($permission)) {
                    $superAdminRole->givePermissionTo($permission);
                    $this->command->info("Assigned '{$permission->name}' permission to Super Admin role");
                }
            }
        }

        // Optionally assign view permission to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $viewPermission = Permission::where('name', 'audit.view')->first();
            if ($viewPermission && !$adminRole->hasPermissionTo($viewPermission)) {
                $adminRole->givePermissionTo($viewPermission);
                $this->command->info("Assigned 'audit.view' permission to Admin role");
            }
        }

        $this->command->info('Audit permissions seeded successfully!');
    }
}
