<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Secure Tenant Web Routes
|--------------------------------------------------------------------------
|
| This file contains the web routes for the secure tenant application.
| All routes include proper security middleware and audit logging.
|
*/

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Health check for monitoring (no authentication required)
Route::get('/health', [AuthController::class, 'health'])->name('health');

// Authentication Routes (for guests only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// SSO Routes (secure processing)
Route::prefix('sso')->name('sso.')->group(function () {
    Route::get('/process', [AuthController::class, 'ssoProcess'])->name('process');
    Route::post('/callback', [AuthController::class, 'ssoCallback'])->name('callback');
});

// Protected Routes (authentication required)
Route::middleware('auth')->group(function () {
    // Authentication management
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Main application routes
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    
    // User profile routes
    Route::get('/profile', function () {
        return view('profile', ['user' => auth()->user()]);
    })->name('profile');
    
    // Settings route
    Route::get('/settings', function () {
        return view('settings', ['user' => auth()->user()]);
    })->name('settings');
    
    // Example protected API routes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/user', function () {
            return response()->json([
                'user' => auth()->user(),
                'tenant' => config('app.tenant_slug'),
                'permissions' => [], // Add your permission logic here
            ]);
        })->name('user');
        
        Route::get('/status', function () {
            return response()->json([
                'status' => 'authenticated',
                'tenant' => config('app.tenant_slug'),
                'user_id' => auth()->id(),
                'timestamp' => now()->toISOString(),
            ]);
        })->name('status');
    });
});

// Admin routes (for admin users only)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard', [
            'user' => auth()->user(),
            'stats' => [
                'total_users' => \App\Models\User::count(),
                'active_sessions' => 0, // Implement session counting logic
                'recent_logins' => 0, // Implement recent login counting
            ]
        ]);
    })->name('dashboard');
    
    Route::get('/users', function () {
        return view('admin.users', [
            'users' => \App\Models\User::paginate(20)
        ]);
    })->name('users');
    
    Route::get('/audit', function () {
        // Implement audit log viewing
        return view('admin.audit', [
            'logs' => [] // Add audit log retrieval logic
        ]);
    })->name('audit');
});

// Development routes (only in local environment)
if (app()->environment('local')) {
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::get('/test-sso', function () {
            return view('dev.test-sso');
        })->name('test-sso');
        
        Route::get('/security-check', function () {
            $ssoService = app(\App\Services\SecureSSOService::class);
            
            return response()->json([
                'sso_health' => $ssoService->healthCheck(),
                'config' => [
                    'tenant_slug' => config('app.tenant_slug'),
                    'central_sso_url' => config('app.central_sso_url'),
                    'api_key_configured' => !empty(config('security.api_key')),
                    'hmac_secret_configured' => !empty(config('security.hmac_secret')),
                    'ssl_verify' => config('security.ssl_verify'),
                ],
                'features' => config('security.features'),
            ]);
        })->name('security-check');
    });
}