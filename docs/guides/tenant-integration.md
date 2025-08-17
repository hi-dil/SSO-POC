# Tenant Integration Guide

This guide provides step-by-step instructions for integrating a new Laravel application with the Central SSO system.

## Overview

The integration process involves setting up your Laravel application to communicate with the Central SSO server for authentication while maintaining local user management and application-specific features.

## Prerequisites

- Laravel 11 application
- Docker and Docker Compose
- Access to the Central SSO server
- MariaDB database for the tenant application

## Step-by-Step Integration

### 1. Install Required Dependencies

```bash
composer require tymon/jwt-auth
composer require laravel/telescope
composer require guzzlehttp/guzzle
```

### 2. Environment Configuration

Add these variables to your `.env` file:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=your_tenant_db
DB_USERNAME=sso_user
DB_PASSWORD=sso_password

# Central SSO Configuration
CENTRAL_SSO_URL=http://central-sso:8000
CENTRAL_SSO_DOMAIN=localhost:8000
TENANT_SLUG=your-tenant-slug

# JWT Configuration
JWT_SECRET=your_jwt_secret_here
JWT_TTL=60

# App Configuration
APP_URL=http://localhost:8003  # Use next available port
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:8003,localhost:8000
```

### 3. Database Migration

Create a users table with SSO integration:

```php
<?php
// database/migrations/2024_01_01_000000_create_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->string('sso_user_id')->nullable(); // SSO user mapping
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
```

### 4. User Model Configuration

Update your User model:

```php
<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'sso_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
```

### 5. SSO Service Implementation

Create a service to handle SSO authentication:

```php
<?php
// app/Services/SSOService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SSOService
{
    private string $centralSSOUrl;
    private string $tenantSlug;

    public function __construct()
    {
        $this->centralSSOUrl = config('app.central_sso_url');
        $this->tenantSlug = config('app.tenant_slug');
    }

    public function verifyToken(string $token): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get($this->centralSSOUrl . '/api/auth/me');

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::error('SSO token verification failed: ' . $e->getMessage());
        }

        return null;
    }

    public function createOrUpdateUser(array $userData): User
    {
        $user = User::updateOrCreate(
            ['sso_user_id' => $userData['id']],
            [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'is_admin' => $userData['is_admin'] ?? false,
                'password' => Hash::make('sso_user_' . $userData['id']),
            ]
        );

        return $user;
    }

    public function authenticateUser(User $user): void
    {
        Auth::login($user, true);
        session()->regenerate();
    }
}
```

### 6. SSO Controller

Create a controller to handle SSO authentication flow:

```php
<?php
// app/Http/Controllers/Auth/SSOController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SSOService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SSOController extends Controller
{
    private SSOService $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function process(Request $request)
    {
        return view('auth.sso-process', [
            'centralSSOUrl' => config('app.central_sso_url'),
            'tenantSlug' => config('app.tenant_slug'),
        ]);
    }

    public function callback(Request $request)
    {
        $token = $request->input('token');
        
        if (!$token) {
            return response()->json(['error' => 'No token provided'], 400);
        }

        $userData = $this->ssoService->verifyToken($token);
        
        if (!$userData) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Check tenant access
        $tenantSlug = config('app.tenant_slug');
        if (!in_array($tenantSlug, $userData['tenants'] ?? [])) {
            return response()->json(['error' => 'Access denied to this tenant'], 403);
        }

        // Create/update local user
        $user = $this->ssoService->createOrUpdateUser($userData);
        
        // Authenticate locally
        $this->ssoService->authenticateUser($user);

        return response()->json(['success' => true, 'redirect' => route('dashboard')]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
```

### 7. SSO Processing View

Create the SSO processing page with JavaScript authentication:

```blade
{{-- resources/views/auth/sso-process.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing SSO Login...</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full space-y-8 p-6">
        <div class="text-center">
            <div id="loading" class="space-y-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <h2 class="text-xl font-semibold text-gray-900">Processing SSO Login...</h2>
                <p class="text-gray-600">Please wait while we authenticate you.</p>
            </div>
            
            <div id="error" class="hidden space-y-4">
                <div class="rounded-full h-12 w-12 bg-red-100 flex items-center justify-center mx-auto">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900">Authentication Failed</h2>
                <p id="error-message" class="text-gray-600"></p>
                <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check SSO authentication status
            fetch('{{ $centralSSOUrl }}/api/auth/check', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.authenticated) {
                    // Process authentication
                    fetch('{{ route('sso.callback') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ token: data.token })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            window.location.href = result.redirect;
                        } else {
                            showError(result.error || 'Authentication failed');
                        }
                    })
                    .catch(() => showError('Network error occurred'));
                } else {
                    // Redirect to central SSO
                    window.location.href = '{{ $centralSSOUrl }}/login?redirect=' + 
                        encodeURIComponent(window.location.origin + '/sso/process');
                }
            })
            .catch(() => showError('Unable to connect to authentication server'));
        });

        function showError(message) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error').classList.remove('hidden');
            document.getElementById('error-message').textContent = message;
        }
    </script>
</body>
</html>
```

### 8. Login View with SSO Integration

Create a dual-authentication login page:

```blade
{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Sign in to {{ config('app.name') }}
            </h2>
        </div>

        <!-- SSO Login Button -->
        <div class="mb-6">
            <a href="{{ route('sso.process') }}" 
               class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Login with SSO
            </a>
        </div>

        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300" />
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-gray-50 text-gray-500">Or sign in directly</span>
            </div>
        </div>

        <!-- Local Login Form -->
        <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
            @csrf
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Email address" value="{{ old('email') }}">
                </div>
                <div>
                    <input id="password" name="password" type="password" autocomplete="current-password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
            </div>

            @if ($errors->any())
                <div class="text-red-600 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Sign in locally
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
```

### 9. Routes Configuration

Add SSO routes to your application:

```php
<?php
// routes/web.php
use App\Http\Controllers\Auth\SSOController;
use Illuminate\Support\Facades\Route;

// SSO Routes
Route::prefix('sso')->group(function () {
    Route::get('/process', [SSOController::class, 'process'])->name('sso.process');
    Route::post('/callback', [SSOController::class, 'callback'])->name('sso.callback');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    
    Route::post('/login', [LoginController::class, 'authenticate']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SSOController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
```

### 10. Docker Configuration

Add your tenant application to the main `docker-compose.yml`:

```yaml
# Add this service to the existing docker-compose.yml
your-tenant-app:
  build:
    context: ./your-tenant-app
    dockerfile: Dockerfile
  container_name: your-tenant-app
  ports:
    - "8003:8000"  # Use next available port
  volumes:
    - ./your-tenant-app:/var/www/html
  networks:
    - sso-network
  depends_on:
    - mariadb
  environment:
    - DB_HOST=mariadb
    - DB_DATABASE=your_tenant_db
    - CENTRAL_SSO_URL=http://central-sso:8000
```

### 11. Database Registration

Register your tenant in the Central SSO system:

```sql
-- Connect to central SSO database
docker exec -it mariadb mysql -u sso_user -psso_password sso_main

-- Insert new tenant
INSERT INTO tenants (id, slug, name, domain, description, is_active, created_at, updated_at) 
VALUES ('your-tenant-slug', 'your-tenant-slug', 'Your Tenant Name', 'localhost:8003', 'Description of your tenant application', 1, NOW(), NOW());

-- Grant access to test users (optional)
INSERT INTO tenant_users (user_id, tenant_id, created_at, updated_at)
SELECT id, 'your-tenant-slug', NOW(), NOW() 
FROM users 
WHERE email IN ('superadmin@sso.com', 'admin@tenant1.com');
```

## Testing Your Integration

### 1. Start Services
```bash
docker compose up -d
```

### 2. Run Migrations
```bash
docker exec your-tenant-app php artisan migrate
```

### 3. Test SSO Flow
1. Visit `http://localhost:8003/login`
2. Click "Login with SSO"
3. Should redirect to central SSO and back automatically

### 4. Test Local Authentication
1. Create a local user in your tenant database
2. Use the direct login form
3. Verify local authentication works independently

## Advanced Features

### Custom User Synchronization

Handle additional user data from SSO:

```php
// In SSOService::createOrUpdateUser()
$user = User::updateOrCreate(
    ['sso_user_id' => $userData['id']],
    [
        'name' => $userData['name'],
        'email' => $userData['email'],
        'is_admin' => $userData['is_admin'] ?? false,
        'department' => $userData['department'] ?? null,
        'job_title' => $userData['job_title'] ?? null,
        'avatar_url' => $userData['avatar_url'] ?? null,
        'password' => Hash::make('sso_user_' . $userData['id']),
    ]
);
```

### Custom Middleware

Create middleware for automatic SSO token validation:

```php
<?php
// app/Http/Middleware/VerifySSOToken.php
namespace App\Http\Middleware;

use App\Services\SSOService;
use Closure;
use Illuminate\Http\Request;

class VerifySSOToken
{
    private SSOService $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if ($token) {
            $userData = $this->ssoService->verifyToken($token);
            if ($userData) {
                $user = $this->ssoService->createOrUpdateUser($userData);
                auth()->login($user);
            }
        }

        return $next($request);
    }
}
```

### Local Role System

Implement tenant-specific roles:

```php
// Create roles migration for tenant app
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->timestamps();
});

Schema::create('user_roles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('role_id')->constrained()->onDelete('cascade');
    $table->timestamps();
});
```

## Troubleshooting

### Common Issues

#### 1. Authentication Failures
- **Check**: SSO server accessibility
- **Verify**: Environment variables are correct
- **Debug**: Network connectivity between containers

#### 2. Token Validation Errors
- **Check**: JWT secret configuration
- **Verify**: Token expiration settings
- **Debug**: API response from central SSO

#### 3. User Synchronization Issues
- **Check**: Database migrations are run
- **Verify**: User model fillable attributes
- **Debug**: SSO user data structure

#### 4. Access Denied Errors
- **Check**: Tenant registration in central SSO
- **Verify**: User has access to the tenant
- **Debug**: JWT token claims

### Debug Commands

```bash
# Check container logs
docker compose logs your-tenant-app

# Test SSO connectivity
docker exec your-tenant-app curl -I http://central-sso:8000

# Check database connection
docker exec your-tenant-app php artisan tinker
>>> DB::connection()->getPdo()

# Test user creation
>>> User::create(['name' => 'Test', 'email' => 'test@example.com', 'password' => bcrypt('password')])
```

## Best Practices

### Security
1. **Validate Tokens**: Always verify JWT tokens from central SSO
2. **Secure Communications**: Use HTTPS in production
3. **Input Validation**: Validate all user data from SSO
4. **Session Management**: Properly handle session lifecycle

### Performance
1. **Cache Tokens**: Cache valid tokens to reduce SSO calls
2. **Async Operations**: Use queues for user synchronization
3. **Database Indexing**: Index SSO user ID fields
4. **Connection Pooling**: Use connection pooling for database

### Maintenance
1. **Monitor Integration**: Log SSO authentication events
2. **Update Dependencies**: Keep JWT and HTTP libraries updated
3. **Test Regularly**: Verify SSO integration after updates
4. **Document Changes**: Keep integration documentation current

This integration guide provides a complete foundation for connecting any Laravel application to the Central SSO system while maintaining flexibility for custom requirements.