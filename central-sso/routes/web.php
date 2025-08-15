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

// Telescope routes (only in development)
if (app()->environment('local', 'testing')) {
    Route::get('/telescope', function () {
        return redirect('/telescope/requests');
    });
}
