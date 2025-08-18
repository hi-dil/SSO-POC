# 📖 Tenant 1 Mobile API Implementation Guide

## Overview

This guide walks you through implementing a secure mobile API for Tenant 1 that enables direct mobile app connectivity without certificate pinning complexity. The implementation uses OAuth 2.0 with PKCE, Laravel Sanctum for token management, and multi-layer security.

## Architecture Flow

Mobile apps connect directly to Tenant 1 API, which validates credentials with Central SSO behind the scenes:

```
Mobile App → Tenant 1 API → Central SSO (via SecureSSOService)
```

This approach provides:
- **Tenant Isolation**: Each tenant manages its own mobile API
- **Scalability**: Central SSO isn't overwhelmed by mobile requests
- **Security**: Credentials always validated against central authority
- **Flexibility**: Each tenant can customize mobile experience

---

## 📦 Installation & Dependencies

### 1. Install Required Packages

In your `tenant1-app` directory:

```bash
# Core mobile API dependencies
composer require laravel/sanctum
composer require firebase/php-jwt
composer require spatie/laravel-rate-limited-job-middleware

# For enhanced security (optional)
composer require pragmarx/google2fa-laravel
```

### 2. Environment Configuration

Add to your `.env` file:

```env
# Mobile API Configuration
MOBILE_API_ENABLED=true
MOBILE_API_VERSION=v1

# OAuth 2.0 Settings  
OAUTH_ACCESS_TOKEN_TTL=30          # Access token lifetime (minutes)
OAUTH_REFRESH_TOKEN_TTL=43200      # Refresh token lifetime (minutes = 30 days)
OAUTH_AUTH_CODE_TTL=10             # Authorization code lifetime (minutes)

# Mobile Security (generate with: openssl rand -hex 32)
MOBILE_HMAC_SECRET=your_64_character_hmac_secret_here
MOBILE_REQUEST_TIMEOUT=300         # Request timeout (seconds)
MOBILE_BLOCK_COMPROMISED_DEVICES=false

# Rate Limiting for Mobile
MOBILE_RATE_LIMIT_PER_MINUTE=60
MOBILE_RATE_LIMIT_PER_DEVICE=100

# Push Notifications (optional)
FCM_SERVER_KEY=your_firebase_server_key
APNS_CERTIFICATE_PATH=/path/to/apns.pem
APNS_PRODUCTION=false
```

### 3. Publish Sanctum Configuration

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

---

## 🗄️ Database Setup

### 1. Create Mobile-Specific Migrations

```bash
# Create migrations for mobile functionality
php artisan make:migration create_mobile_devices_table
php artisan make:migration create_oauth_auth_codes_table
php artisan make:migration create_oauth_refresh_tokens_table
php artisan make:migration create_mobile_api_logs_table
```

### 2. Migration Files

**Mobile Devices Migration:**
```php
<?php
// database/migrations/xxxx_create_mobile_devices_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->unique();
            $table->enum('device_type', ['ios', 'android', 'other'])->default('other');
            $table->string('device_name')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();
            $table->text('push_token')->nullable();
            $table->text('fingerprint')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'device_id']);
            $table->index('last_seen_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_devices');
    }
};
```

**OAuth Authorization Codes Migration:**
```php
<?php
// database/migrations/xxxx_create_oauth_auth_codes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('oauth_auth_codes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('client_id');
            $table->text('scopes')->nullable();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at');
            $table->string('code_challenge', 128)->nullable();
            $table->string('code_challenge_method', 10)->default('S256');
            $table->timestamps();
            
            $table->index('expires_at');
            $table->index(['client_id', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('oauth_auth_codes');
    }
};
```

**OAuth Refresh Tokens Migration:**
```php
<?php
// database/migrations/xxxx_create_oauth_refresh_tokens_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('oauth_refresh_tokens', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('access_token_id')->nullable();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at');
            $table->string('device_id')->nullable();
            $table->text('device_fingerprint')->nullable();
            $table->timestamps();
            
            $table->index('expires_at');
            $table->index(['device_id', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('oauth_refresh_tokens');
    }
};
```

**Mobile API Logs Migration:**
```php
<?php
// database/migrations/xxxx_create_mobile_api_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mobile_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('device_id')->nullable();
            $table->string('endpoint');
            $table->string('method', 10);
            $table->integer('status_code');
            $table->integer('response_time_ms')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['device_id', 'created_at']);
            $table->index(['endpoint', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_api_logs');
    }
};
```

### 3. Run Migrations

```bash
php artisan migrate
```

---

## 📱 Model Setup

### 1. MobileDevice Model

```php
<?php
// app/Models/MobileDevice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_type',
        'device_name', 
        'device_model',
        'os_version',
        'app_version',
        'push_token',
        'fingerprint',
        'last_seen_at'
    ];

    protected $casts = [
        'last_seen_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function isActive(): bool
    {
        return $this->last_seen_at && 
               $this->last_seen_at->gt(now()->subDays(30));
    }
}
```

### 2. Update User Model

Add to your existing User model:

```php
<?php
// app/Models/User.php

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // ... existing code ...

    /**
     * Get the mobile devices for the user
     */
    public function mobileDevices(): HasMany
    {
        return $this->hasMany(MobileDevice::class);
    }

    /**
     * Get active mobile devices
     */
    public function activeMobileDevices(): HasMany
    {
        return $this->mobileDevices()
            ->where('last_seen_at', '>', now()->subDays(30));
    }

    /**
     * Check if device is registered to this user
     */
    public function hasDevice(string $deviceId): bool
    {
        return $this->mobileDevices()
            ->where('device_id', $deviceId)
            ->exists();
    }
}
```

---

## 🔐 Mobile Authentication Service

### 1. Create MobileAuthService

```php
<?php
// app/Services/MobileAuthService.php

namespace App\Services;

use App\Models\User;
use App\Models\MobileDevice;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class MobileAuthService
{
    private $ssoService;
    private $codeValidityMinutes = 10;
    private $accessTokenMinutes = 30;
    private $refreshTokenDays = 30;
    
    public function __construct(SecureSSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }
    
    /**
     * Generate authorization code with PKCE support
     */
    public function generateAuthorizationCode(
        string $clientId,
        string $codeChallenge,
        string $codeChallengeMethod = 'S256',
        array $scopes = []
    ): array {
        // Validate code challenge method
        if (!in_array($codeChallengeMethod, ['plain', 'S256'])) {
            throw new \InvalidArgumentException('Invalid code challenge method');
        }
        
        // Generate secure authorization code
        $code = Str::random(64);
        $codeData = [
            'client_id' => $clientId,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod,
            'scopes' => $scopes,
            'expires_at' => now()->addMinutes($this->codeValidityMinutes)
        ];
        
        // Store in cache with expiration
        Cache::put(
            "auth_code:{$code}", 
            $codeData, 
            $this->codeValidityMinutes * 60
        );
        
        return [
            'authorization_code' => $code,
            'expires_in' => $this->codeValidityMinutes * 60
        ];
    }
    
    /**
     * Exchange authorization code for tokens using direct login
     */
    public function exchangeCodeForTokens(
        string $code,
        string $codeVerifier,
        string $deviceId,
        array $deviceInfo = [],
        string $email = null,
        string $password = null
    ): array {
        // Retrieve and validate authorization code
        $codeData = Cache::get("auth_code:{$code}");
        
        if (!$codeData) {
            throw new \Exception('Invalid or expired authorization code');
        }
        
        // Verify PKCE
        if (!$this->verifyCodeChallenge(
            $codeVerifier,
            $codeData['code_challenge'],
            $codeData['code_challenge_method']
        )) {
            throw new \Exception('Invalid code verifier');
        }
        
        // Delete code after use (one-time use)
        Cache::forget("auth_code:{$code}");
        
        // Authenticate with Central SSO
        if (!$email || !$password) {
            throw new \Exception('Email and password required for authentication');
        }
        
        $ssoResult = $this->ssoService->login($email, $password);
        
        if (!$ssoResult['success']) {
            // Record failed login audit
            $this->ssoService->recordLoginAudit(
                null,
                $email,
                'mobile_oauth',
                false,
                $ssoResult['message'] ?? 'Authentication failed'
            );
            
            throw new \Exception($ssoResult['message'] ?? 'Authentication failed');
        }
        
        // Create or update local user
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $ssoResult['user']['name'],
                'sso_user_id' => $ssoResult['user']['id']
            ]
        );
        
        // Register or update device
        $device = $this->registerDevice($user, $deviceId, $deviceInfo);
        
        // Create Sanctum tokens
        $accessToken = $user->createToken(
            'mobile-access-token',
            $codeData['scopes'] ?? ['*'],
            now()->addMinutes($this->accessTokenMinutes)
        );
        
        $refreshToken = $this->generateRefreshToken($user, $device);
        
        // Record successful login audit
        $this->ssoService->recordLoginAudit(
            $user->sso_user_id,
            $email,
            'mobile_oauth',
            true
        );
        
        return [
            'token_type' => 'Bearer',
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->accessTokenMinutes * 60,
            'scope' => implode(' ', $codeData['scopes'] ?? ['*']),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ];
    }
    
    /**
     * Direct login bypass (alternative to OAuth flow)
     */
    public function directLogin(
        string $email,
        string $password,
        string $deviceId,
        array $deviceInfo = []
    ): array {
        // Authenticate with Central SSO
        $ssoResult = $this->ssoService->login($email, $password);
        
        if (!$ssoResult['success']) {
            // Record failed login audit
            $this->ssoService->recordLoginAudit(
                null,
                $email,
                'mobile_direct',
                false,
                $ssoResult['message'] ?? 'Authentication failed'
            );
            
            throw new \Exception($ssoResult['message'] ?? 'Authentication failed');
        }
        
        // Create or update local user
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $ssoResult['user']['name'],
                'sso_user_id' => $ssoResult['user']['id']
            ]
        );
        
        // Register or update device
        $device = $this->registerDevice($user, $deviceId, $deviceInfo);
        
        // Create Sanctum tokens
        $accessToken = $user->createToken(
            'mobile-access-token',
            ['*'],
            now()->addMinutes($this->accessTokenMinutes)
        );
        
        $refreshToken = $this->generateRefreshToken($user, $device);
        
        // Record successful login audit
        $this->ssoService->recordLoginAudit(
            $user->sso_user_id,
            $email,
            'mobile_direct',
            true
        );
        
        return [
            'token_type' => 'Bearer',
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->accessTokenMinutes * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ];
    }
    
    /**
     * Verify PKCE code challenge
     */
    private function verifyCodeChallenge(
        string $verifier,
        string $challenge,
        string $method
    ): bool {
        if ($method === 'plain') {
            return $verifier === $challenge;
        }
        
        if ($method === 'S256') {
            $calculatedChallenge = base64_encode(
                hash('sha256', $verifier, true)
            );
            // URL-safe base64 encoding
            $calculatedChallenge = strtr($calculatedChallenge, '+/', '-_');
            $calculatedChallenge = rtrim($calculatedChallenge, '=');
            
            return $calculatedChallenge === $challenge;
        }
        
        return false;
    }
    
    /**
     * Generate refresh token with device binding
     */
    private function generateRefreshToken(User $user, MobileDevice $device): string
    {
        $payload = [
            'user_id' => $user->id,
            'device_id' => $device->device_id,
            'device_fingerprint' => $device->fingerprint,
            'iat' => time(),
            'exp' => time() + ($this->refreshTokenDays * 24 * 60 * 60),
            'jti' => Str::uuid()->toString()
        ];
        
        return JWT::encode($payload, env('JWT_SECRET'), 'HS256');
    }
    
    /**
     * Refresh access token
     */
    public function refreshAccessToken(
        string $refreshToken,
        string $deviceId,
        string $deviceFingerprint
    ): array {
        try {
            // Decode and validate refresh token
            $payload = JWT::decode(
                $refreshToken,
                new Key(env('JWT_SECRET'), 'HS256')
            );
            
            // Verify device binding
            if ($payload->device_id !== $deviceId) {
                throw new \Exception('Device mismatch');
            }
            
            // Get user and device
            $user = User::findOrFail($payload->user_id);
            $device = MobileDevice::where('device_id', $deviceId)->firstOrFail();
            
            // Update device last seen
            $device->updateLastSeen();
            
            // Issue new access token
            $accessToken = $user->createToken(
                'mobile-access-token',
                ['*'],
                now()->addMinutes($this->accessTokenMinutes)
            );
            
            // Optionally rotate refresh token
            $newRefreshToken = $this->generateRefreshToken($user, $device);
            
            return [
                'token_type' => 'Bearer',
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $newRefreshToken,
                'expires_in' => $this->accessTokenMinutes * 60
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('Invalid refresh token: ' . $e->getMessage());
        }
    }
    
    /**
     * Register or update mobile device
     */
    private function registerDevice(
        User $user,
        string $deviceId,
        array $deviceInfo
    ): MobileDevice {
        return MobileDevice::updateOrCreate(
            ['device_id' => $deviceId],
            [
                'user_id' => $user->id,
                'device_type' => $deviceInfo['device_type'] ?? 'unknown',
                'device_name' => $deviceInfo['device_name'] ?? null,
                'device_model' => $deviceInfo['device_model'] ?? null,
                'os_version' => $deviceInfo['os_version'] ?? null,
                'app_version' => $deviceInfo['app_version'] ?? null,
                'push_token' => $deviceInfo['push_token'] ?? null,
                'fingerprint' => $this->generateDeviceFingerprint($deviceInfo),
                'last_seen_at' => now()
            ]
        );
    }
    
    /**
     * Generate device fingerprint for additional security
     */
    private function generateDeviceFingerprint(array $deviceInfo): string
    {
        $fingerprintData = [
            $deviceInfo['device_model'] ?? '',
            $deviceInfo['os_version'] ?? '',
            $deviceInfo['screen_resolution'] ?? '',
            $deviceInfo['timezone'] ?? '',
            $deviceInfo['language'] ?? ''
        ];
        
        return hash('sha256', implode('|', $fingerprintData));
    }
}
```

---

## 🛡️ Security Middleware

### 1. Mobile Security Middleware

```php
<?php
// app/Http/Middleware/MobileSecurityMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\LogMobileApiRequest;

class MobileSecurityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Verify request signature
        if (!$this->verifyRequestSignature($request)) {
            return response()->json([
                'error' => 'Invalid request signature'
            ], 401);
        }
        
        // 2. Validate timestamp (prevent replay attacks)
        if (!$this->validateTimestamp($request)) {
            return response()->json([
                'error' => 'Request expired or invalid timestamp'
            ], 401);
        }
        
        // 3. Check device binding for authenticated requests
        if ($request->user() && !$this->verifyDeviceBinding($request)) {
            return response()->json([
                'error' => 'Device verification failed'
            ], 401);
        }
        
        // 4. Check for compromised devices
        if ($this->isDeviceCompromised($request)) {
            Log::warning('Compromised device detected', [
                'user_id' => $request->user()->id ?? null,
                'device_id' => $request->header('X-Device-Id'),
                'ip' => $request->ip()
            ]);
            
            // Just log for now, don't block
            if (config('mobile.block_compromised_devices', false)) {
                return response()->json([
                    'error' => 'Device security check failed'
                ], 403);
            }
        }
        
        // 5. Log API request for analytics
        $this->logApiRequest($request);
        
        $response = $next($request);
        
        // 6. Add security headers to response
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        
        return $response;
    }
    
    private function verifyRequestSignature(Request $request): bool
    {
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $deviceId = $request->header('X-Device-Id');
        
        if (!$signature || !$timestamp || !$deviceId) {
            return false;
        }
        
        // Create canonical request string
        $method = $request->method();
        $path = $request->path();
        $body = $request->getContent();
        $canonicalRequest = "{$method}|{$path}|{$timestamp}|{$deviceId}|{$body}";
        
        // Verify HMAC signature
        $expectedSignature = hash_hmac(
            'sha256',
            $canonicalRequest,
            config('mobile.security.hmac_secret')
        );
        
        return hash_equals($expectedSignature, $signature);
    }
    
    private function validateTimestamp(Request $request): bool
    {
        $timestamp = $request->header('X-Timestamp');
        
        if (!$timestamp || !is_numeric($timestamp)) {
            return false;
        }
        
        $requestTime = (int) $timestamp;
        $currentTime = time();
        $tolerance = config('mobile.security.request_timeout', 300);
        
        return abs($currentTime - $requestTime) <= $tolerance;
    }
    
    private function verifyDeviceBinding(Request $request): bool
    {
        $deviceId = $request->header('X-Device-Id');
        $user = $request->user();
        
        if (!$deviceId || !$user) {
            return true; // Skip for non-authenticated requests
        }
        
        // Check if device is registered to this user
        return $user->hasDevice($deviceId);
    }
    
    private function isDeviceCompromised(Request $request): bool
    {
        $deviceInfo = json_decode($request->header('X-Device-Info'), true);
        
        if (!$deviceInfo) {
            return false;
        }
        
        // Check for jailbreak/root indicators
        $compromisedIndicators = [
            'jailbroken' => $deviceInfo['jailbroken'] ?? false,
            'rooted' => $deviceInfo['rooted'] ?? false,
            'debugger_attached' => $deviceInfo['debugger'] ?? false,
            'emulator' => $deviceInfo['emulator'] ?? false
        ];
        
        return array_filter($compromisedIndicators) !== [];
    }
    
    private function logApiRequest(Request $request): void
    {
        // Async job to log API request
        dispatch(new LogMobileApiRequest([
            'user_id' => $request->user()->id ?? null,
            'device_id' => $request->header('X-Device-Id'),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'response_time' => 0 // Will be updated in job
        ]));
    }
}
```

### 2. Create API Logging Job

```php
<?php
// app/Jobs/LogMobileApiRequest.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class LogMobileApiRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $requestData;

    public function __construct(array $requestData)
    {
        $this->requestData = $requestData;
    }

    public function handle()
    {
        DB::table('mobile_api_logs')->insert([
            'user_id' => $this->requestData['user_id'],
            'device_id' => $this->requestData['device_id'],
            'endpoint' => $this->requestData['endpoint'],
            'method' => $this->requestData['method'],
            'status_code' => 200, // Default, can be updated
            'response_time_ms' => null,
            'ip_address' => $this->requestData['ip_address'],
            'user_agent' => $this->requestData['user_agent'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
```

---

## 🎯 Controllers

### 1. Mobile Auth Controller

```php
<?php
// app/Http/Controllers/Api/Mobile/AuthController.php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MobileAuthService;
use App\Services\SecureSSOService;
use App\Http\Requests\Mobile\AuthorizeRequest;
use App\Http\Requests\Mobile\TokenRequest;
use App\Http\Requests\Mobile\RefreshRequest;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    private $authService;
    private $ssoService;
    
    public function __construct(
        MobileAuthService $authService,
        SecureSSOService $ssoService
    ) {
        $this->authService = $authService;
        $this->ssoService = $ssoService;
    }
    
    /**
     * OAuth 2.0 Authorization Endpoint
     */
    public function authorize(AuthorizeRequest $request)
    {
        try {
            $result = $this->authService->generateAuthorizationCode(
                $request->client_id,
                $request->code_challenge,
                $request->code_challenge_method ?? 'S256',
                explode(' ', $request->scope ?? '*')
            );
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mobile auth authorize error', [
                'error' => $e->getMessage(),
                'client_id' => $request->client_id
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * OAuth 2.0 Token Endpoint
     */
    public function token(TokenRequest $request)
    {
        try {
            $deviceInfo = [
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'device_model' => $request->device_model,
                'os_version' => $request->os_version,
                'app_version' => $request->app_version,
                'push_token' => $request->push_token,
                'screen_resolution' => $request->screen_resolution,
                'timezone' => $request->timezone,
                'language' => $request->language
            ];
            
            $result = $this->authService->exchangeCodeForTokens(
                $request->code,
                $request->code_verifier,
                $request->device_id,
                $deviceInfo,
                $request->email,
                $request->password
            );
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mobile auth token error', [
                'error' => $e->getMessage(),
                'device_id' => $request->device_id
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Direct Login (Alternative to OAuth flow)
     */
    public function directLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'required|string',
            'device_info' => 'required|array'
        ]);
        
        try {
            $result = $this->authService->directLogin(
                $request->email,
                $request->password,
                $request->device_id,
                $request->device_info
            );
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mobile direct login error', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }
    
    /**
     * Refresh Token Endpoint
     */
    public function refresh(RefreshRequest $request)
    {
        try {
            $result = $this->authService->refreshAccessToken(
                $request->refresh_token,
                $request->device_id,
                $request->device_fingerprint
            );
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mobile token refresh error', [
                'error' => $e->getMessage(),
                'device_id' => $request->device_id
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 401);
        }
    }
    
    /**
     * Logout
     */
    public function logout(Request $request)
    {
        try {
            // Revoke current access token
            $request->user()->currentAccessToken()->delete();
            
            // Optionally revoke all tokens for this device
            if ($request->revoke_all_device_tokens) {
                $request->user()->tokens()
                    ->where('name', 'mobile-access-token')
                    ->delete();
            }
            
            // Log logout event
            $this->ssoService->recordLoginAudit(
                $request->user()->sso_user_id,
                $request->user()->email,
                'mobile_logout',
                true
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Mobile logout error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id ?? null
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * List user's registered devices
     */
    public function listDevices(Request $request)
    {
        $devices = $request->user()->mobileDevices()
            ->orderBy('last_seen_at', 'desc')
            ->get()
            ->map(function ($device) {
                return [
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'device_type' => $device->device_type,
                    'device_model' => $device->device_model,
                    'os_version' => $device->os_version,
                    'app_version' => $device->app_version,
                    'last_seen_at' => $device->last_seen_at,
                    'is_active' => $device->isActive()
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => $devices
        ]);
    }
    
    /**
     * Revoke access for a specific device
     */
    public function revokeDevice(Request $request, string $deviceId)
    {
        try {
            // Find the device
            $device = $request->user()->mobileDevices()
                ->where('device_id', $deviceId)
                ->firstOrFail();
            
            // Revoke all tokens for this device
            $request->user()->tokens()
                ->where('name', 'mobile-access-token')
                ->delete(); // In a real implementation, you'd filter by device
            
            // Delete device record
            $device->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Device access revoked successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Update push notification token
     */
    public function updatePushToken(Request $request)
    {
        $request->validate([
            'push_token' => 'required|string'
        ]);
        
        $deviceId = $request->header('X-Device-Id');
        
        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'error' => 'Device ID required'
            ], 400);
        }
        
        $device = $request->user()->mobileDevices()
            ->where('device_id', $deviceId)
            ->first();
            
        if ($device) {
            $device->update([
                'push_token' => $request->push_token
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Push token updated successfully'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'error' => 'Device not found'
        ], 404);
    }
}
```

---

## 🛣️ Routes Configuration

### 1. Create Mobile API Routes

```php
<?php
// routes/api_mobile.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\ProfileController;

Route::prefix('v1/mobile')->group(function () {
    
    // Public authentication endpoints
    Route::prefix('auth')->group(function () {
        // OAuth 2.0 PKCE flow
        Route::post('authorize', [AuthController::class, 'authorize']);
        Route::post('token', [AuthController::class, 'token']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        
        // Direct login (alternative)
        Route::post('login', [AuthController::class, 'directLogin']);
        
        // Password reset (optional)
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });
    
    // Protected endpoints
    Route::middleware([
        'auth:sanctum',
        'mobile.security',
        'throttle:mobile-api'
    ])->group(function () {
        
        // User profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('/avatar', [ProfileController::class, 'uploadAvatar']);
            Route::delete('/avatar', [ProfileController::class, 'deleteAvatar']);
        });
        
        // Device management
        Route::prefix('devices')->group(function () {
            Route::get('/', [AuthController::class, 'listDevices']);
            Route::delete('/{deviceId}', [AuthController::class, 'revokeDevice']);
            Route::post('/current/push-token', [AuthController::class, 'updatePushToken']);
        });
        
        // Logout
        Route::post('auth/logout', [AuthController::class, 'logout']);
    });
});
```

### 2. Include in RouteServiceProvider

Add to `app/Providers/RouteServiceProvider.php`:

```php
public function boot()
{
    $this->configureRateLimiting();

    $this->routes(function () {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        Route::middleware('web')
            ->group(base_path('routes/web.php'));
            
        // Add mobile API routes
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api_mobile.php'));
    });
}

protected function configureRateLimiting()
{
    RateLimiter::for('mobile-api', function (Request $request) {
        return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
    });
}
```

---

## ⚙️ Configuration

### 1. Create Mobile Config File

```php
<?php
// config/mobile.php

return [
    'enabled' => env('MOBILE_API_ENABLED', true),
    
    'version' => env('MOBILE_API_VERSION', 'v1'),
    
    'oauth' => [
        'access_token_ttl' => env('OAUTH_ACCESS_TOKEN_TTL', 30),
        'refresh_token_ttl' => env('OAUTH_REFRESH_TOKEN_TTL', 43200),
        'auth_code_ttl' => env('OAUTH_AUTH_CODE_TTL', 10),
    ],
    
    'security' => [
        'hmac_secret' => env('MOBILE_HMAC_SECRET'),
        'request_timeout' => env('MOBILE_REQUEST_TIMEOUT', 300),
        'block_compromised_devices' => env('MOBILE_BLOCK_COMPROMISED_DEVICES', false),
        'require_device_binding' => true,
        'require_signature' => true,
    ],
    
    'rate_limits' => [
        'per_minute' => env('MOBILE_RATE_LIMIT_PER_MINUTE', 60),
        'per_device' => env('MOBILE_RATE_LIMIT_PER_DEVICE', 100),
    ],
    
    'push_notifications' => [
        'fcm' => [
            'server_key' => env('FCM_SERVER_KEY'),
        ],
        'apns' => [
            'certificate_path' => env('APNS_CERTIFICATE_PATH'),
            'production' => env('APNS_PRODUCTION', false),
        ],
    ],
    
    'allowed_app_versions' => [
        'ios' => ['1.0.0', '1.1.0', '1.2.0'],
        'android' => ['1.0.0', '1.1.0', '1.2.0'],
    ],
];
```

### 2. Register Middleware

Add to `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ... existing middleware ...
    'mobile.security' => \App\Http\Middleware\MobileSecurityMiddleware::class,
];
```

---

## 🧪 Testing

### 1. Test the Implementation

```bash
# Test authorization endpoint
curl -X POST http://localhost:8001/api/v1/mobile/auth/authorize \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: $(date +%s)" \
  -H "X-Device-Id: test-device-001" \
  -H "X-Signature: [generated_signature]" \
  -d '{
    "client_id": "mobile_app",
    "code_challenge": "E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM",
    "code_challenge_method": "S256"
  }'

# Test token exchange
curl -X POST http://localhost:8001/api/v1/mobile/auth/token \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: $(date +%s)" \
  -H "X-Device-Id: test-device-001" \
  -H "X-Signature: [generated_signature]" \
  -d '{
    "code": "[authorization_code]",
    "code_verifier": "dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk",
    "device_id": "test-device-001",
    "email": "user@tenant1.com",
    "password": "password",
    "device_type": "ios",
    "device_name": "iPhone 15 Pro",
    "device_model": "iPhone16,1",
    "os_version": "17.0",
    "app_version": "1.0.0"
  }'
```

### 2. Generate HMAC Signature (for testing)

```bash
# Generate HMAC signature for testing
method="POST"
path="/api/v1/mobile/auth/authorize"
timestamp=$(date +%s)
device_id="test-device-001"
body='{"client_id":"mobile_app","code_challenge":"E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM","code_challenge_method":"S256"}'
canonical_request="$method|$path|$timestamp|$device_id|$body"

# Generate signature (replace YOUR_HMAC_SECRET)
signature=$(echo -n "$canonical_request" | openssl dgst -sha256 -hmac "YOUR_HMAC_SECRET" | cut -d' ' -f2)
echo "X-Signature: $signature"
```

---

## 🚀 Next Steps

1. **Test the Implementation**: Use the provided curl commands to test your endpoints
2. **Implement Client SDKs**: Follow the [iOS](client-sdks/ios-implementation.md) and [Android](client-sdks/android-implementation.md) guides
3. **Configure Security**: Review the [Security Configuration](security-configuration.md) guide
4. **Set up Monitoring**: Configure analytics using the [Monitoring Guide](monitoring-and-analytics.md)
5. **Deploy to Production**: Follow the [Deployment Checklist](deployment-checklist.md)

The implementation provides a solid foundation for mobile API security without the operational complexity of certificate pinning, while maintaining enterprise-grade protection through multiple security layers.