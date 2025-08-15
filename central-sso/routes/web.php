<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\MainAuthController;

Route::get('/', function () {
    return view('welcome');
});

// Main Central SSO Login Routes
Route::get('/login', [MainAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [MainAuthController::class, 'login'])->name('main.login.submit');
Route::get('/tenant-select', [MainAuthController::class, 'showTenantSelection'])->name('tenant.select');
Route::post('/tenant-select', [MainAuthController::class, 'selectTenant'])->name('tenant.select.submit');
Route::get('/logout', [MainAuthController::class, 'logout'])->name('main.logout');

// SSO Authentication Routes (for tenant-specific login)
Route::get('/auth/{tenant_slug}', [SSOController::class, 'showLoginForm'])->name('sso.form');
Route::post('/auth/login', [SSOController::class, 'handleLogin'])->name('sso.login');

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
});

// Telescope routes (only in development)
if (app()->environment('local', 'testing')) {
    Route::get('/telescope', function () {
        return redirect('/telescope/requests');
    });
}
