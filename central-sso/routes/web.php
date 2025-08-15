<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SSOController;

Route::get('/', function () {
    return view('welcome');
});

// SSO Authentication Routes
Route::get('/auth/{tenant_slug}', [SSOController::class, 'showLoginForm'])->name('sso.form');
Route::post('/auth/login', [SSOController::class, 'handleLogin'])->name('sso.login');

// Telescope routes (only in development)
if (app()->environment('local', 'testing')) {
    Route::get('/telescope', function () {
        return redirect('/telescope/requests');
    });
}
