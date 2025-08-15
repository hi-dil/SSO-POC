<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// SSO Authentication Routes
Route::get('/auth/sso', [AuthController::class, 'ssoRedirect'])->name('sso.redirect');
Route::get('/auth/callback', [AuthController::class, 'ssoCallback'])->name('sso.callback');

// Protected Routes
Route::middleware(['App\Http\Middleware\SSOAuthenticate'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
});

// Telescope routes (only in development)
if (app()->environment('local', 'testing')) {
    Route::get('/telescope', function () {
        return redirect('/telescope/requests');
    });
}
