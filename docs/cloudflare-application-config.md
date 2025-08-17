# Application Configuration Updates for Cloudflare Tunnel

## Overview

This document outlines the specific configuration changes required in your Laravel applications when deploying with Cloudflare Tunnel. These changes ensure proper functionality with HTTPS, cross-domain sessions, and Cloudflare's proxy infrastructure.

## Central SSO Application Configuration

### Environment Variables (`.env`)

Update your Central SSO `.env` file with these Cloudflare-specific settings:

```env
# Application URLs
APP_URL=https://sso.poc.hi-dil.com
ASSET_URL=https://sso.poc.hi-dil.com

# Session Configuration
SESSION_DOMAIN=.poc.hi-dil.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://sso.poc.hi-dil.com,https://tenant-one.poc.hi-dil.com,https://tenant-two.poc.hi-dil.com

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=sso.poc.hi-dil.com,tenant-one.poc.hi-dil.com,tenant-two.poc.hi-dil.com

# Trust Cloudflare Proxies
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22
```

### Laravel Configuration Updates

#### 1. Update `config/app.php`

```php
<?php

return [
    // ... existing configuration

    'url' => env('APP_URL', 'https://sso.poc.hi-dil.com'),
    'asset_url' => env('ASSET_URL', 'https://sso.poc.hi-dil.com'),

    // Force HTTPS in production
    'force_https' => env('APP_ENV') === 'production',
];
```

#### 2. Update `config/session.php`

```php
<?php

return [
    // ... existing configuration

    'domain' => env('SESSION_DOMAIN', '.poc.hi-dil.com'),
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'same_site' => env('SESSION_SAME_SITE', 'lax'),
    
    // Use Redis for shared sessions
    'driver' => env('SESSION_DRIVER', 'redis'),
    'connection' => env('SESSION_CONNECTION', 'default'),
];
```

#### 3. Update `config/cors.php`

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'auth/*'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'https://sso.poc.hi-dil.com')),
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', 'accept,authorization,content-type,x-xsrf-token,x-csrf-token,x-requested-with')),
    
    'exposed_headers' => explode(',', env('CORS_EXPOSED_HEADERS', 'x-request-id')),
    
    'max_age' => env('CORS_MAX_AGE', 86400),
    
    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),
];
```

#### 4. Update `config/sanctum.php`

```php
<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'sso.poc.hi-dil.com,tenant-one.poc.hi-dil.com,tenant-two.poc.hi-dil.com')),
    
    'guard' => ['web'],
    
    'expiration' => null,
    
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],
];
```

#### 5. Create/Update `config/cloudflare.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudflare Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('CF_ENABLED', true),

    'trust_proxies' => [
        // Cloudflare IP ranges
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
    ],

    'headers' => [
        'cf_connecting_ip' => 'CF-Connecting-IP',
        'cf_ipcountry' => 'CF-IPCountry',
        'cf_ray' => 'CF-Ray',
        'cf_visitor' => 'CF-Visitor',
    ],

    'cache' => [
        'enabled' => env('CF_CACHE_ENABLED', true),
        'ttl' => env('CF_CACHE_TTL', 3600),
    ],
];
```

### Middleware Updates

#### 1. Update `app/Http/Middleware/TrustProxies.php`

```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     */
    protected $proxies = [
        // Cloudflare IP ranges
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
    ];

    /**
     * The headers that should be used to detect proxies.
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR |
                        Request::HEADER_X_FORWARDED_HOST |
                        Request::HEADER_X_FORWARDED_PORT |
                        Request::HEADER_X_FORWARDED_PROTO |
                        Request::HEADER_X_FORWARDED_AWS_ELB;
}
```

#### 2. Create `app/Http/Middleware/ForceHttps.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceHttps
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Force HTTPS in production
        if (app()->environment('production') && !$request->isSecure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
```

Register this middleware in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ... existing middleware
    \App\Http\Middleware\ForceHttps::class,
];
```

## Tenant Application Configuration

### Tenant 1 Application (`.env`)

```env
# Application URLs
APP_URL=https://tenant-one.poc.hi-dil.com
ASSET_URL=https://tenant-one.poc.hi-dil.com

# SSO Configuration
CENTRAL_SSO_URL=https://sso.poc.hi-dil.com
CENTRAL_SSO_API=http://central-sso:8000/api
TENANT_SLUG=tenant1

# Session Configuration
SESSION_DOMAIN=.poc.hi-dil.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://sso.poc.hi-dil.com,https://tenant-one.poc.hi-dil.com

# Trust Cloudflare Proxies
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22
```

### Tenant 2 Application (`.env`)

```env
# Application URLs
APP_URL=https://tenant-two.poc.hi-dil.com
ASSET_URL=https://tenant-two.poc.hi-dil.com

# SSO Configuration
CENTRAL_SSO_URL=https://sso.poc.hi-dil.com
CENTRAL_SSO_API=http://central-sso:8000/api
TENANT_SLUG=tenant2

# Session Configuration
SESSION_DOMAIN=.poc.hi-dil.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://sso.poc.hi-dil.com,https://tenant-two.poc.hi-dil.com

# Trust Cloudflare Proxies
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22
```

## Service Provider Updates

### 1. Update `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Force HTTPS in production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Set default string length for MySQL
        Schema::defaultStringLength(191);

        // Trust Cloudflare proxies
        $this->configureCloudflareProxies();
    }

    private function configureCloudflareProxies()
    {
        // Set trusted proxies from environment
        $trustedProxies = env('TRUSTED_PROXIES');
        if ($trustedProxies) {
            $proxies = explode(',', $trustedProxies);
            request()->setTrustedProxies($proxies, \Illuminate\Http\Request::HEADER_X_FORWARDED_ALL);
        }
    }
}
```

## Frontend Configuration Updates

### JavaScript/Vue.js Configuration

Update your frontend configuration to handle HTTPS and cross-domain requests:

#### 1. Update `resources/js/app.js`

```javascript
// Configure Axios for HTTPS and CSRF
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Configure base URL for API calls
if (process.env.NODE_ENV === 'production') {
    window.axios.defaults.baseURL = 'https://sso.poc.hi-dil.com';
}

// Handle CSRF token
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}
```

#### 2. Update Blade templates

Ensure all asset URLs use HTTPS in production:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Force HTTPS for assets in production --}}
    @if(app()->environment('production'))
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    @endif
    
    <title>{{ config('app.name', 'SSO POC') }}</title>
    
    {{-- Assets with secure URLs --}}
    <link href="{{ secure_asset('css/app.css') }}" rel="stylesheet">
    <script src="{{ secure_asset('js/app.js') }}" defer></script>
</head>
<body>
    <!-- Your content -->
</body>
</html>
```

## Database Configuration Updates

### Redis Configuration

Update Redis configuration for session sharing:

#### `config/database.php`

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        'prefix' => env('REDIS_PREFIX', 'sso_poc'),
    ],

    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
        'prefix' => env('REDIS_PREFIX', 'sso_poc') . ':cache:',
    ],

    'session' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_SESSION_DB', '2'),
        'prefix' => env('REDIS_PREFIX', 'sso_poc') . ':session:',
    ],
],
```

## Testing Configuration

### Update PHPUnit for HTTPS Testing

#### `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <!-- ... existing configuration -->
    
    <php>
        <!-- ... existing env variables -->
        <env name="APP_URL" value="https://sso.poc.hi-dil.com"/>
        <env name="SESSION_DOMAIN" value=".poc.hi-dil.com"/>
        <env name="SESSION_SECURE_COOKIE" value="true"/>
    </php>
</phpunit>
```

### Feature Test Updates

Update your feature tests to handle HTTPS:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_login_redirects_to_https()
    {
        $response = $this->post('/login', [
            'email' => 'user@tenant1.com',
            'password' => 'password'
        ]);

        $this->assertTrue($response->isRedirect());
        $this->assertStringStartsWith('https://', $response->headers->get('Location'));
    }

    public function test_cross_domain_session_sharing()
    {
        // Test that sessions work across subdomains
        $this->withServerVariables([
            'HTTP_HOST' => 'tenant-one.poc.hi-dil.com',
            'HTTPS' => 'on'
        ]);

        // Your test logic here
    }
}
```

## Deployment Checklist

### Pre-deployment Configuration Checklist

- [ ] Update all `.env` files with Cloudflare-specific settings
- [ ] Configure trusted proxies for Cloudflare IP ranges
- [ ] Set session domain to `.poc.hi-dil.com`
- [ ] Enable secure cookies and proper CORS settings
- [ ] Update asset URLs to use HTTPS
- [ ] Configure Redis for session sharing
- [ ] Test cross-domain authentication flows
- [ ] Verify SSL certificate handling
- [ ] Update frontend JavaScript for HTTPS API calls

### Post-deployment Validation

```bash
# Test HTTPS redirect
curl -I http://sso.poc.hi-dil.com
# Should return 301 redirect to HTTPS

# Test CORS headers
curl -H "Origin: https://tenant-one.poc.hi-dil.com" \
     -H "Access-Control-Request-Method: POST" \
     -H "Access-Control-Request-Headers: X-Requested-With" \
     -X OPTIONS https://sso.poc.hi-dil.com/api/auth/login

# Test session sharing
curl -c cookies.txt https://sso.poc.hi-dil.com/sanctum/csrf-cookie
curl -b cookies.txt https://tenant-one.poc.hi-dil.com/dashboard

# Verify security headers
curl -I https://sso.poc.hi-dil.com
# Should include HSTS, X-Frame-Options, etc.
```

## Troubleshooting Common Issues

### 1. CORS Errors

**Problem**: Cross-origin requests blocked
**Solution**: Verify CORS configuration in all applications

```php
// Check config/cors.php
'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS')),
```

### 2. Session Issues

**Problem**: Sessions not shared across domains
**Solution**: Verify session domain configuration

```php
// config/session.php
'domain' => env('SESSION_DOMAIN', '.poc.hi-dil.com'),
```

### 3. Mixed Content Warnings

**Problem**: HTTP resources loaded on HTTPS pages
**Solution**: Update all asset URLs to use `secure_asset()` or HTTPS

### 4. Trust Proxy Issues

**Problem**: Real client IP not detected
**Solution**: Verify Cloudflare IP ranges in TrustProxies middleware

This completes the application configuration updates required for Cloudflare Tunnel deployment. All applications will now properly handle HTTPS, cross-domain sessions, and Cloudflare's proxy infrastructure.