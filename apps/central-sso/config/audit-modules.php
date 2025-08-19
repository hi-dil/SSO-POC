<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Module Definitions
    |--------------------------------------------------------------------------
    |
    | This configuration defines the module and submodule structure for
    | organizing audit logs. Each module represents a functional area
    | of the application with specific submodules for different actions.
    |
    */

    'modules' => [
        'authentication' => [
            'name' => 'Authentication',
            'description' => 'User authentication and session management',
            'submodules' => [
                'login' => 'User login events',
                'logout' => 'User logout events',
                'password_reset' => 'Password reset requests and completions',
                'password_change' => 'Password change events',
                'failed_login' => 'Failed login attempts',
                'token_refresh' => 'JWT token refresh events',
            ]
        ],

        'user_management' => [
            'name' => 'User Management',
            'description' => 'User account and profile management',
            'submodules' => [
                'user_created' => 'New user account creation',
                'user_updated' => 'User account updates',
                'user_deleted' => 'User account deletion',
                'profile_updated' => 'User profile information changes',
                'contact_updated' => 'User contact information changes',
                'address_updated' => 'User address information changes',
                'family_updated' => 'User family member information changes',
                'social_media_updated' => 'User social media profile changes',
                'tenant_assigned' => 'User tenant access assignments',
                'tenant_removed' => 'User tenant access removal',
            ]
        ],

        'tenant_management' => [
            'name' => 'Tenant Management',
            'description' => 'Multi-tenant organization management',
            'submodules' => [
                'tenant_created' => 'New tenant organization creation',
                'tenant_updated' => 'Tenant information updates',
                'tenant_deleted' => 'Tenant organization deletion',
                'tenant_activated' => 'Tenant activation events',
                'tenant_deactivated' => 'Tenant deactivation events',
                'user_assigned' => 'User assignment to tenant',
                'user_removed' => 'User removal from tenant',
                'settings_updated' => 'Tenant-specific settings changes',
            ]
        ],

        'settings' => [
            'name' => 'System Settings',
            'description' => 'System configuration and settings management',
            'submodules' => [
                'jwt_settings_updated' => 'JWT token configuration changes',
                'session_settings_updated' => 'Session management settings changes',
                'security_settings_updated' => 'Security parameter changes',
                'system_settings_updated' => 'General system configuration changes',
                'cache_cleared' => 'Settings cache clearing events',
                'settings_reset' => 'Settings reset to default values',
                'bulk_settings_updated' => 'Multiple settings updated simultaneously',
            ]
        ],

        'roles_permissions' => [
            'name' => 'Roles & Permissions',
            'description' => 'Role-based access control management',
            'submodules' => [
                'role_created' => 'New role creation',
                'role_updated' => 'Role information updates',
                'role_deleted' => 'Role deletion',
                'permission_created' => 'New permission creation',
                'permission_updated' => 'Permission updates',
                'permission_deleted' => 'Permission deletion',
                'role_assigned' => 'Role assignment to users',
                'role_removed' => 'Role removal from users',
                'permission_assigned' => 'Permission assignment to roles',
                'permission_removed' => 'Permission removal from roles',
            ]
        ],

        'security' => [
            'name' => 'Security Events',
            'description' => 'Security monitoring and threat detection',
            'submodules' => [
                'failed_login' => 'Failed authentication attempts',
                'account_locked' => 'Account lockout events',
                'account_unlocked' => 'Account unlock events',
                'suspicious_activity' => 'Suspicious behavior detection',
                'multiple_failed_attempts' => 'Multiple failed login attempts',
                'unusual_access_pattern' => 'Unusual access pattern detection',
                'ip_blocked' => 'IP address blocking events',
                'rate_limit_exceeded' => 'Rate limiting violations',
                'security_scan_detected' => 'Security scan attempt detection',
            ]
        ],

        'system' => [
            'name' => 'System Administration',
            'description' => 'System-level administrative activities',
            'submodules' => [
                'backup_created' => 'System backup creation',
                'backup_restored' => 'System backup restoration',
                'maintenance_mode_enabled' => 'Maintenance mode activation',
                'maintenance_mode_disabled' => 'Maintenance mode deactivation',
                'database_migration' => 'Database migration execution',
                'cache_cleared' => 'System cache clearing',
                'queue_processed' => 'Background queue processing',
                'log_archived' => 'Log file archival',
                'system_health_check' => 'System health monitoring',
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log Names
    |--------------------------------------------------------------------------
    |
    | Map module names to Spatie Activity Log names for organization.
    | This helps filter and categorize activities in the admin interface.
    |
    */

    'log_names' => [
        'authentication' => 'auth',
        'user_management' => 'users',
        'tenant_management' => 'tenants',
        'settings' => 'settings',
        'roles_permissions' => 'roles',
        'security' => 'security',
        'system' => 'system',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Icons
    |--------------------------------------------------------------------------
    |
    | SVG icons for each module to display in the admin interface.
    |
    */

    'icons' => [
        'authentication' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'user_management' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>',
        'tenant_management' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        'settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'roles_permissions' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
        'security' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
        'system' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>',
    ],
];