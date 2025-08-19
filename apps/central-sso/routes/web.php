<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\MainAuthController;

Route::get('/', function () {
    return view('landing');
});

Route::get('/debug-session', function () {
    $user = auth()->user();
    return response()->json([
        'session_config' => [
            'driver' => config('session.driver'),
            'domain' => config('session.domain'),
            'cookie' => config('session.cookie'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
        ],
        'session_id' => session()->getId(),
        'auth_check' => auth()->check(),
        'user_id' => auth()->id(),
        'user_email' => $user ? $user->email : null,
        'session_data' => session()->all(),
        'cookies_received' => request()->cookies->all(),
    ]);
});

Route::get('/clear-all-cookies', function () {
    $response = response('All session cookies cleared. <a href="/login">Login again</a>');
    
    // Clear all possible session cookies that might be conflicting
    $cookiesToClear = [
        'laravel_session',
        'laravel-session', 
        'central_sso_session',
        'tenant1_session',
        'tenant2_session',
        'XSRF-TOKEN'
    ];
    
    foreach ($cookiesToClear as $cookieName) {
        $response->withCookie(cookie()->forget($cookieName));
        $response->withCookie(cookie()->make($cookieName, '', -1, '/', 'localhost'));
    }
    
    return $response;
});

// Main Central SSO Login Routes
Route::get('/login', [MainAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [MainAuthController::class, 'login'])->name('main.login.submit');
Route::get('/dashboard', [MainAuthController::class, 'showDashboard'])->name('dashboard')->middleware('auth');
Route::get('/tenant-select', [MainAuthController::class, 'showTenantSelection'])->name('tenant.select');
Route::post('/tenant-select', [MainAuthController::class, 'selectTenant'])->name('tenant.select.submit');
Route::post('/tenant-access', [MainAuthController::class, 'accessTenant'])->name('tenant.access');
Route::get('/logout', [MainAuthController::class, 'logout'])->name('main.logout');

// User Profile Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [App\Http\Controllers\UserProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [App\Http\Controllers\UserProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\UserProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [App\Http\Controllers\UserProfileController::class, 'updatePassword'])->name('profile.password');
    
    // Family Members
    Route::get('/profile/family', [App\Http\Controllers\UserProfileController::class, 'family'])->name('profile.family');
    Route::post('/profile/family', [App\Http\Controllers\UserProfileController::class, 'storeFamilyMember'])->name('profile.family.store');
    Route::put('/profile/family/{familyMember}', [App\Http\Controllers\UserProfileController::class, 'updateFamilyMember'])->name('profile.family.update');
    Route::delete('/profile/family/{familyMember}', [App\Http\Controllers\UserProfileController::class, 'destroyFamilyMember'])->name('profile.family.destroy');
    
    // Contacts
    Route::get('/profile/contacts', [App\Http\Controllers\UserProfileController::class, 'contacts'])->name('profile.contacts');
    Route::post('/profile/contacts', [App\Http\Controllers\UserProfileController::class, 'storeContact'])->name('profile.contacts.store');
    Route::put('/profile/contacts/{contact}', [App\Http\Controllers\UserProfileController::class, 'updateContact'])->name('profile.contacts.update');
    Route::delete('/profile/contacts/{contact}', [App\Http\Controllers\UserProfileController::class, 'destroyContact'])->name('profile.contacts.destroy');
    
    // Addresses
    Route::get('/profile/addresses', [App\Http\Controllers\UserProfileController::class, 'addresses'])->name('profile.addresses');
    Route::post('/profile/addresses', [App\Http\Controllers\UserProfileController::class, 'storeAddress'])->name('profile.addresses.store');
    Route::put('/profile/addresses/{address}', [App\Http\Controllers\UserProfileController::class, 'updateAddress'])->name('profile.addresses.update');
    Route::delete('/profile/addresses/{address}', [App\Http\Controllers\UserProfileController::class, 'destroyAddress'])->name('profile.addresses.destroy');
    
    // Social Media
    Route::get('/profile/social-media', [App\Http\Controllers\UserProfileController::class, 'socialMedia'])->name('profile.social-media');
    Route::post('/profile/social-media', [App\Http\Controllers\UserProfileController::class, 'storeSocialMedia'])->name('profile.social-media.store');
    Route::put('/profile/social-media/{socialMedia}', [App\Http\Controllers\UserProfileController::class, 'updateSocialMedia'])->name('profile.social-media.update');
    Route::delete('/profile/social-media/{socialMedia}', [App\Http\Controllers\UserProfileController::class, 'destroySocialMedia'])->name('profile.social-media.destroy');
});

// SSO Authentication Routes (for tenant-specific login)
Route::middleware(['web'])->group(function () {
    Route::get('/auth/{tenant_slug}', [SSOController::class, 'showLoginForm'])->name('sso.form');
    Route::get('/auth/{tenant_slug}/check', [SSOController::class, 'checkAuth'])->name('sso.check');
    Route::post('/auth/login', [SSOController::class, 'handleLogin'])->name('sso.login');
    Route::get('/auth/logout', [SSOController::class, 'logout'])->name('sso.logout');
});

// Debug route to check authentication (temporary)
Route::get('/debug/auth', function () {
    if (!auth()->check()) {
        return response()->json(['error' => 'Not authenticated', 'session_id' => session()->getId()]);
    }
    
    $user = auth()->user();
    
    // Debug the hasPermission method step by step
    $rolesQuery = $user->roles();
    $rolesWithSwagger = $rolesQuery->whereHas('permissions', function ($q) {
        $q->where('slug', 'swagger.access');
    })->get();
    
    return response()->json([
        'authenticated' => true,
        'session_id' => session()->getId(),
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
        ],
        'roles' => $user->roles->map(function($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions->pluck('slug')
            ];
        }),
        'all_permissions' => $user->getAllPermissions()->pluck('slug'),
        'roles_with_swagger' => $rolesWithSwagger->map(function($role) {
            return [
                'name' => $role->name,
                'slug' => $role->slug
            ];
        }),
        'has_telescope_access' => $user->hasPermissionTo('telescope.access'),
        'has_swagger_access' => $user->hasPermissionTo('swagger.access'),
        'raw_roles_count' => $user->roles()->count(),
        'model_type_check' => get_class($user)
    ]);
});

// Admin Routes (Protected by authentication and permissions)
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // Admin Dashboard
    Route::get('/', function () {
        return redirect()->route('admin.tenants.index');
    })->name('dashboard');
    
    // Tenant Management
    Route::resource('tenants', \App\Http\Controllers\Admin\TenantManagementController::class);
    Route::post('tenants/bulk-create', [\App\Http\Controllers\Admin\TenantManagementController::class, 'bulkCreate'])->name('tenants.bulk-create');
    Route::get('tenants/{tenant}/users', [\App\Http\Controllers\Admin\TenantController::class, 'users'])->name('tenants.users');
    Route::post('tenants/{tenant}/users', [\App\Http\Controllers\Admin\TenantController::class, 'assignUser'])->name('tenants.assign-user');
    Route::delete('tenants/{tenant}/users/{user}', [\App\Http\Controllers\Admin\TenantController::class, 'removeUser'])->name('tenants.remove-user');
    Route::patch('tenants/{tenant}/toggle', [\App\Http\Controllers\Admin\TenantController::class, 'toggle'])->name('tenants.toggle');
    
    // User Management
    Route::get('users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
    Route::get('users/create', [\App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('users.create');
    Route::post('users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [\App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::get('users/data', [\App\Http\Controllers\Admin\UserManagementController::class, 'getUsers'])->name('users.data');
    Route::post('users/{userId}/tenants', [\App\Http\Controllers\Admin\UserManagementController::class, 'assignTenant'])->name('users.assign-tenant');
    Route::delete('users/{userId}/tenants', [\App\Http\Controllers\Admin\UserManagementController::class, 'removeTenant'])->name('users.remove-tenant');
    
    // User Contact Management
    Route::get('users/{user}/contacts', [\App\Http\Controllers\Admin\UserManagementController::class, 'contacts'])->name('users.contacts')->middleware('can:View User Contacts');
    Route::post('users/{user}/contacts', [\App\Http\Controllers\Admin\UserManagementController::class, 'storeContact'])->name('users.contacts.store')->middleware('can:Manage User Contacts');
    Route::put('users/{user}/contacts/{contact}', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateContact'])->name('users.contacts.update')->middleware('can:Manage User Contacts');
    Route::delete('users/{user}/contacts/{contact}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroyContact'])->name('users.contacts.destroy')->middleware('can:Manage User Contacts');
    
    // User Address Management
    Route::get('users/{user}/addresses', [\App\Http\Controllers\Admin\UserManagementController::class, 'addresses'])->name('users.addresses')->middleware('can:View User Addresses');
    Route::post('users/{user}/addresses', [\App\Http\Controllers\Admin\UserManagementController::class, 'storeAddress'])->name('users.addresses.store')->middleware('can:Manage User Addresses');
    Route::put('users/{user}/addresses/{address}', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateAddress'])->name('users.addresses.update')->middleware('can:Manage User Addresses');
    Route::delete('users/{user}/addresses/{address}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroyAddress'])->name('users.addresses.destroy')->middleware('can:Manage User Addresses');
    
    // User Family Management
    Route::get('users/{user}/family', [\App\Http\Controllers\Admin\UserManagementController::class, 'family'])->name('users.family')->middleware('can:View User Family Members');
    Route::post('users/{user}/family', [\App\Http\Controllers\Admin\UserManagementController::class, 'storeFamilyMember'])->name('users.family.store')->middleware('can:Manage User Family Members');
    Route::put('users/{user}/family/{familyMember}', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateFamilyMember'])->name('users.family.update')->middleware('can:Manage User Family Members');
    Route::delete('users/{user}/family/{familyMember}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroyFamilyMember'])->name('users.family.destroy')->middleware('can:Manage User Family Members');
    
    // User Social Media Management
    Route::get('users/{user}/social-media', [\App\Http\Controllers\Admin\UserManagementController::class, 'socialMedia'])->name('users.social-media')->middleware('can:View User Social Media');
    Route::post('users/{user}/social-media', [\App\Http\Controllers\Admin\UserManagementController::class, 'storeSocialMedia'])->name('users.social-media.store')->middleware('can:Manage User Social Media');
    Route::put('users/{user}/social-media/{socialMedia}', [\App\Http\Controllers\Admin\UserManagementController::class, 'updateSocialMedia'])->name('users.social-media.update')->middleware('can:Manage User Social Media');
    Route::delete('users/{user}/social-media/{socialMedia}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroySocialMedia'])->name('users.social-media.destroy')->middleware('can:Manage User Social Media');
    
    // Login Analytics
    Route::get('analytics', [\App\Http\Controllers\Admin\LoginAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/statistics', [\App\Http\Controllers\Admin\LoginAnalyticsController::class, 'getStatistics'])->name('analytics.statistics');
    Route::get('analytics/recent-activity', [\App\Http\Controllers\Admin\LoginAnalyticsController::class, 'getRecentActivity'])->name('analytics.recent-activity');
    Route::get('analytics/trends', [\App\Http\Controllers\Admin\LoginAnalyticsController::class, 'getLoginTrends'])->name('analytics.trends');
    Route::get('analytics/user/{userId}', [\App\Http\Controllers\Admin\LoginAnalyticsController::class, 'getUserActivity'])->name('analytics.user');
    Route::get('analytics/tenant/{tenantId}', [\App\Http\Controllers\Admin\LoginAnalyticsController::class, 'getTenantAnalytics'])->name('analytics.tenant');
    Route::get('analytics/failed-attempts', [\App\Http\Controllers\Admin\LoginAnalyticsController::class, 'getFailedAttempts'])->name('analytics.failed-attempts');
    Route::get('analytics/export', [\App\Http\Controllers\Admin\LoginAnalyticsController::class, 'export'])->name('analytics.export');
    Route::post('analytics/cleanup', [\App\Http\Controllers\Admin\LoginAnalyticsController::class, 'cleanup'])->name('analytics.cleanup');
    
    // Role Management (Central SSO only)
    Route::get('roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'index'])->name('roles.index');
    Route::get('roles/create', [\App\Http\Controllers\Admin\RoleManagementController::class, 'create'])->name('roles.create');
    Route::get('roles/{role}/edit', [\App\Http\Controllers\Admin\RoleManagementController::class, 'edit'])->name('roles.edit');
    Route::get('roles/data', [\App\Http\Controllers\Admin\RoleManagementController::class, 'getRoles'])->name('roles.data');
    Route::post('roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'destroy'])->name('roles.destroy');
    Route::get('permissions/data', [\App\Http\Controllers\Admin\RoleManagementController::class, 'getPermissions'])->name('permissions.data');
    Route::post('users/{userId}/roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'assignUserRole'])->name('users.assign-role');
    Route::delete('users/{userId}/roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'removeUserRole'])->name('users.remove-role');
    
    // Settings Management
    Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/reset/{key}', [\App\Http\Controllers\Admin\SettingsController::class, 'reset'])->name('settings.reset');
    Route::post('settings/clear-cache', [\App\Http\Controllers\Admin\SettingsController::class, 'clearCache'])->name('settings.clear-cache');
    
    // Audit Logs Management
    Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{activity}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('audit-logs.show');
    Route::get('audit-logs/api/activities', [\App\Http\Controllers\Admin\AuditLogController::class, 'activities'])->name('audit-logs.activities');
    Route::get('audit-logs/api/statistics', [\App\Http\Controllers\Admin\AuditLogController::class, 'statistics'])->name('audit-logs.statistics');
    Route::post('audit-logs/export', [\App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::post('audit-logs/cleanup', [\App\Http\Controllers\Admin\AuditLogController::class, 'cleanup'])->name('audit-logs.cleanup');
    Route::get('audit-logs/users/{userId}', [\App\Http\Controllers\Admin\AuditLogController::class, 'userActivities'])->name('audit-logs.user-activities');
});

// API-style routes for admin role management (web authenticated)
Route::prefix('api')->middleware(['auth', 'web'])->group(function () {
    // Role management API routes for the admin interface
    Route::get('roles', [\App\Http\Controllers\Api\RoleController::class, 'index']);
    Route::post('roles', [\App\Http\Controllers\Api\RoleController::class, 'store']);
    Route::put('roles/{role}', [\App\Http\Controllers\Api\RoleController::class, 'update']);
    Route::delete('roles/{role}', [\App\Http\Controllers\Api\RoleController::class, 'destroy']);
    
    // User role assignment routes
    Route::post('users/{userId}/roles', [\App\Http\Controllers\Api\UserRoleController::class, 'assignRole']);
    Route::delete('users/{userId}/roles', [\App\Http\Controllers\Api\UserRoleController::class, 'removeRole']);
    
    // Admin user management API routes
    Route::prefix('admin')->group(function () {
        Route::get('users/{user}', function (\App\Models\User $user) {
            $user->load(['tenants', 'roles']);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'tenants' => $user->tenants->map(function ($tenant) {
                        return [
                            'id' => $tenant->id,
                            'name' => $tenant->name,
                            'slug' => $tenant->slug
                        ];
                    }),
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'role' => [
                                'id' => $role->id,
                                'name' => $role->name,
                                'slug' => $role->slug,
                                'is_system' => $role->is_system
                            ],
                            'tenant_id' => $role->pivot->tenant_id ?? null
                        ];
                    })
                ]
            ]);
        });
    });
});

// Telescope routes (only in development)
if (app()->environment('local', 'testing')) {
    Route::get('/telescope', function () {
        return redirect('/telescope/requests');
    });
}

// API Documentation (only in development)
if (app()->environment('local', 'testing')) {
    Route::middleware(['auth'])->group(function () {
        Route::get('/docs', function () {
            // Check permission manually with better error handling
            if (!auth()->user()->hasPermissionTo('swagger.access')) {
                return response()->json([
                    'error' => 'Access denied', 
                    'message' => 'You do not have permission to access API documentation',
                    'user_id' => auth()->id(),
                    'user_permissions' => auth()->user()->getAllPermissions()->pluck('slug')
                ], 403);
            }
            return redirect('/api/documentation');
        })->name('api.docs');
        
        // Manual route for swagger docs JSON (workaround for missing l5-swagger.default.docs route)
        Route::get('/docs.json', function () {
            // Check permission manually with better error handling
            if (!auth()->user()->hasPermissionTo('swagger.access')) {
                return response()->json([
                    'error' => 'Access denied', 
                    'message' => 'You do not have permission to access API documentation',
                    'user_id' => auth()->id(),
                    'user_permissions' => auth()->user()->getAllPermissions()->pluck('slug')
                ], 403);
            }
            
            $path = storage_path('api-docs/api-docs.json');
            if (file_exists($path)) {
                return response()->file($path, [
                    'Content-Type' => 'application/json'
                ]);
            }
            return response()->json(['error' => 'Documentation not found'], 404);
        })->name('l5-swagger.default.docs');
    });
}
