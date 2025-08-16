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
    
    // Role Management (Central SSO only)
    Route::get('roles', [\App\Http\Controllers\Admin\RoleManagementController::class, 'index'])->name('roles.index');
    Route::get('roles/data', [\App\Http\Controllers\Admin\RoleManagementController::class, 'getRoles'])->name('roles.data');
    Route::get('permissions/data', [\App\Http\Controllers\Admin\RoleManagementController::class, 'getPermissions'])->name('permissions.data');
    Route::get('users/data', [\App\Http\Controllers\Admin\RoleManagementController::class, 'getUsers'])->name('users.data');
});

// Telescope routes (only in development)
if (app()->environment('local', 'testing')) {
    Route::get('/telescope', function () {
        return redirect('/telescope/requests');
    });
}

// API Documentation (only in development)
if (app()->environment('local', 'testing')) {
    Route::get('/docs', function () {
        return redirect('/api/documentation');
    })->name('api.docs');
    
    // Manual route for swagger docs JSON (workaround for missing l5-swagger.default.docs route)
    Route::get('/docs.json', function () {
        $path = storage_path('api-docs/api-docs.json');
        if (file_exists($path)) {
            return response()->file($path, [
                'Content-Type' => 'application/json'
            ]);
        }
        return response()->json(['error' => 'Documentation not found'], 404);
    })->name('l5-swagger.default.docs');
}
