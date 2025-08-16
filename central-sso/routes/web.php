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
        'has_telescope_access' => $user->hasPermission('telescope.access'),
        'has_swagger_access' => $user->hasPermission('swagger.access'),
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
    Route::resource('tenants', \App\Http\Controllers\Admin\TenantController::class);
    Route::get('tenants/{tenant}/users', [\App\Http\Controllers\Admin\TenantController::class, 'users'])->name('tenants.users');
    Route::post('tenants/{tenant}/users', [\App\Http\Controllers\Admin\TenantController::class, 'assignUser'])->name('tenants.assign-user');
    Route::delete('tenants/{tenant}/users/{user}', [\App\Http\Controllers\Admin\TenantController::class, 'removeUser'])->name('tenants.remove-user');
    Route::patch('tenants/{tenant}/toggle', [\App\Http\Controllers\Admin\TenantController::class, 'toggle'])->name('tenants.toggle');
    
    // User Management
    Route::get('users', [\App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('users.index');
    Route::get('users/data', [\App\Http\Controllers\Admin\UserManagementController::class, 'getUsers'])->name('users.data');
    Route::post('users', [\App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [\App\Http\Controllers\Admin\UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{userId}/tenants', [\App\Http\Controllers\Admin\UserManagementController::class, 'assignTenant'])->name('users.assign-tenant');
    Route::delete('users/{userId}/tenants', [\App\Http\Controllers\Admin\UserManagementController::class, 'removeTenant'])->name('users.remove-tenant');
    
    // Role Management (Central SSO only)
    Route::get('roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'index'])->name('roles.index');
    Route::get('roles/data', [\App\Http\Controllers\Admin\RoleManagementController::class, 'getRoles'])->name('roles.data');
    Route::post('roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'store'])->name('roles.store');
    Route::put('roles/{role}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [\App\Http\Controllers\Admin\RoleManagementController::class, 'destroy'])->name('roles.destroy');
    Route::get('permissions/data', [\App\Http\Controllers\Admin\RoleManagementController::class, 'getPermissions'])->name('permissions.data');
    Route::get('users/data', [\App\Http\Controllers\Admin\RoleManagementController::class, 'getUsers'])->name('users.data');
    Route::post('users/{userId}/roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'assignUserRole'])->name('users.assign-role');
    Route::delete('users/{userId}/roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'removeUserRole'])->name('users.remove-role');
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
            if (!auth()->user()->hasPermission('swagger.access')) {
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
            if (!auth()->user()->hasPermission('swagger.access')) {
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
