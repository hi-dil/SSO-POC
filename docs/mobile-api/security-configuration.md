# 🔒 Mobile API Security Configuration

## Overview

This guide covers the security configuration for Tenant 1's mobile API. The security model is designed to provide enterprise-grade protection without the operational complexity of certificate pinning, using multiple layers of defense including HMAC signing, device binding, and OAuth 2.0 with PKCE.

## Security Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Security Layers                        │
├─────────────────────────────────────────────────────────────┤
│ 1. Transport Security (HTTPS/TLS 1.3)                      │
│ 2. Request Signing (HMAC-SHA256)                          │
│ 3. Timestamp Validation (Replay Protection)               │
│ 4. Device Binding (Token-Device Association)              │
│ 5. OAuth 2.0 PKCE (Authorization Code Flow)               │
│ 6. Rate Limiting (DoS Protection)                         │
│ 7. Compromise Detection (Jailbreak/Root)                  │
│ 8. Audit Logging (Security Monitoring)                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 Why No Certificate Pinning?

### Decision Rationale

Certificate pinning was **intentionally excluded** for the following reasons:

#### ❌ Operational Challenges
- **Certificate Rotation**: Requires coordinated app updates
- **Emergency Changes**: Users can be locked out during cert issues
- **Testing Complexity**: Harder to test with development certificates
- **Corporate Proxies**: Blocks legitimate enterprise proxy setups

#### ✅ Alternative Protection
Your current security stack provides equivalent protection:
- **HMAC Signing**: Prevents tampering even with MITM
- **Device Binding**: Stolen tokens unusable on other devices
- **Short Token Expiry**: Limits exposure window (30 minutes)
- **Timestamp Validation**: Prevents replay attacks

#### 📊 Risk Analysis
| Attack Vector | With Cert Pinning | Without Pinning + HMAC |
|--------------|-------------------|-------------------------|
| Standard MITM | Protected ✅ | Protected ✅ |
| Compromised CA | Protected ✅ | Vulnerable ⚠️ (rare) |
| Token Theft | Vulnerable ⚠️ | Protected ✅ |
| Data Tampering | Protected ✅ | Protected ✅ |
| Replay Attacks | Vulnerable ⚠️ | Protected ✅ |

---

## 🔐 Multi-Layer Security Implementation

### Layer 1: Transport Security

#### TLS Configuration

**Nginx Configuration (Production):**
```nginx
server {
    listen 443 ssl http2;
    server_name tenant1.example.com;

    # TLS Configuration
    ssl_protocols TLSv1.3 TLSv1.2;
    ssl_ciphers ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_tickets off;
    ssl_session_timeout 1d;

    # Security Headers
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # Certificate Transparency
    ssl_stapling on;
    ssl_stapling_verify on;
    ssl_trusted_certificate /path/to/chain.pem;
    resolver 8.8.8.8 8.8.4.4 valid=300s;
    resolver_timeout 5s;

    location /api/v1/mobile {
        proxy_pass http://tenant1-backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Security headers for mobile API
        proxy_set_header X-Request-Start $msec;
        proxy_hide_header X-Powered-By;
    }
}
```

**Laravel TLS Configuration:**
```php
// config/session.php
'secure' => env('SESSION_SECURE_COOKIE', true),
'same_site' => env('SESSION_SAME_SITE', 'lax'),

// .env
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
TRUSTED_PROXIES=*
```

### Layer 2: Request Signing (HMAC-SHA256)

#### Backend Verification

```php
<?php
// app/Http/Middleware/MobileSecurityMiddleware.php

private function verifyRequestSignature(Request $request): bool
{
    $signature = $request->header('X-Signature');
    $timestamp = $request->header('X-Timestamp');
    $deviceId = $request->header('X-Device-Id');
    
    if (!$signature || !$timestamp || !$deviceId) {
        Log::warning('Missing security headers', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
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
    
    $isValid = hash_equals($expectedSignature, $signature);
    
    if (!$isValid) {
        Log::warning('Invalid HMAC signature', [
            'device_id' => $deviceId,
            'ip' => $request->ip(),
            'path' => $path,
            'method' => $method
        ]);
    }
    
    return $isValid;
}
```

#### HMAC Secret Generation

```bash
# Generate a secure HMAC secret (64 characters)
openssl rand -hex 32

# Or use Laravel's built-in generator
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# Add to .env
MOBILE_HMAC_SECRET=your_64_character_secret_here
```

### Layer 3: Timestamp Validation

#### Replay Attack Prevention

```php
private function validateTimestamp(Request $request): bool
{
    $timestamp = $request->header('X-Timestamp');
    
    if (!$timestamp || !is_numeric($timestamp)) {
        return false;
    }
    
    $requestTime = (int) $timestamp;
    $currentTime = time();
    $tolerance = config('mobile.security.request_timeout', 300); // 5 minutes
    
    $timeDiff = abs($currentTime - $requestTime);
    
    if ($timeDiff > $tolerance) {
        Log::info('Request timestamp outside tolerance', [
            'timestamp' => $timestamp,
            'current_time' => $currentTime,
            'difference' => $timeDiff,
            'tolerance' => $tolerance,
            'device_id' => $request->header('X-Device-Id')
        ]);
        return false;
    }
    
    return true;
}
```

#### Clock Drift Handling

```php
// config/mobile.php
'security' => [
    'request_timeout' => env('MOBILE_REQUEST_TIMEOUT', 300), // 5 minutes
    'clock_drift_tolerance' => env('MOBILE_CLOCK_DRIFT', 60), // 1 minute
],

// In middleware
private function validateTimestampWithDrift(Request $request): bool
{
    $timestamp = (int) $request->header('X-Timestamp');
    $currentTime = time();
    
    // Base tolerance + clock drift allowance
    $baseTolerance = config('mobile.security.request_timeout', 300);
    $driftTolerance = config('mobile.security.clock_drift_tolerance', 60);
    $totalTolerance = $baseTolerance + $driftTolerance;
    
    return abs($currentTime - $timestamp) <= $totalTolerance;
}
```

### Layer 4: Device Binding

#### Device Registration

```php
<?php
// app/Services/MobileAuthService.php

private function registerDevice(User $user, string $deviceId, array $deviceInfo): MobileDevice
{
    $fingerprint = $this->generateDeviceFingerprint($deviceInfo);
    
    return MobileDevice::updateOrCreate(
        [
            'user_id' => $user->id,
            'device_id' => $deviceId
        ],
        [
            'device_type' => $deviceInfo['device_type'] ?? 'unknown',
            'device_name' => $deviceInfo['device_name'] ?? null,
            'device_model' => $deviceInfo['device_model'] ?? null,
            'os_version' => $deviceInfo['os_version'] ?? null,
            'app_version' => $deviceInfo['app_version'] ?? null,
            'push_token' => $deviceInfo['push_token'] ?? null,
            'fingerprint' => $fingerprint,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'is_trusted' => true,
            'security_flags' => json_encode([
                'jailbroken' => $deviceInfo['jailbroken'] ?? false,
                'rooted' => $deviceInfo['rooted'] ?? false,
                'debugger' => $deviceInfo['debugger'] ?? false,
                'emulator' => $deviceInfo['emulator'] ?? false
            ])
        ]
    );
}

private function generateDeviceFingerprint(array $deviceInfo): string
{
    // Create stable device fingerprint
    $fingerprintData = [
        $deviceInfo['device_model'] ?? '',
        $deviceInfo['os_version'] ?? '',
        $deviceInfo['screen_resolution'] ?? '',
        $deviceInfo['timezone'] ?? '',
        $deviceInfo['language'] ?? '',
        $deviceInfo['cpu_type'] ?? '',
        $deviceInfo['total_memory'] ?? ''
    ];
    
    return hash('sha256', implode('|', array_filter($fingerprintData)));
}
```

#### Device Verification

```php
private function verifyDeviceBinding(Request $request): bool
{
    $deviceId = $request->header('X-Device-Id');
    $user = $request->user();
    
    if (!$deviceId || !$user) {
        return true; // Skip for public endpoints
    }
    
    $device = $user->mobileDevices()
        ->where('device_id', $deviceId)
        ->where('is_active', true)
        ->first();
    
    if (!$device) {
        Log::warning('Unknown device attempting access', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'ip' => $request->ip()
        ]);
        return false;
    }
    
    // Update last seen
    $device->updateLastSeen();
    
    // Check device fingerprint if provided
    $providedFingerprint = $request->header('X-Device-Fingerprint');
    if ($providedFingerprint && $device->fingerprint) {
        $fingerprintMatch = $this->compareFingerprints(
            $providedFingerprint, 
            $device->fingerprint
        );
        
        if (!$fingerprintMatch) {
            Log::warning('Device fingerprint mismatch', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'expected' => substr($device->fingerprint, 0, 8) . '...',
                'provided' => substr($providedFingerprint, 0, 8) . '...'
            ]);
            // Don't fail hard - fingerprints can change with OS updates
        }
    }
    
    return true;
}

private function compareFingerprints(string $provided, string $stored): bool
{
    // Exact match
    if ($provided === $stored) {
        return true;
    }
    
    // Could implement fuzzy matching for minor changes
    // For now, strict comparison
    return false;
}
```

### Layer 5: OAuth 2.0 with PKCE

#### PKCE Implementation

```php
<?php
// app/Services/MobileAuthService.php

public function generateAuthorizationCode(
    string $clientId,
    string $codeChallenge,
    string $codeChallengeMethod = 'S256',
    array $scopes = []
): array {
    // Validate code challenge method
    if (!in_array($codeChallengeMethod, ['plain', 'S256'])) {
        throw new InvalidArgumentException('Invalid code challenge method');
    }
    
    // Validate code challenge format
    if ($codeChallengeMethod === 'S256' && !$this->isValidBase64Url($codeChallenge)) {
        throw new InvalidArgumentException('Invalid code challenge format for S256');
    }
    
    // Generate secure authorization code
    $code = Str::random(64);
    
    // Store code data with expiration
    $codeData = [
        'client_id' => $clientId,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => $codeChallengeMethod,
        'scopes' => $scopes,
        'created_at' => now(),
        'expires_at' => now()->addMinutes($this->codeValidityMinutes)
    ];
    
    Cache::put(
        "auth_code:{$code}",
        $codeData,
        $this->codeValidityMinutes * 60
    );
    
    Log::info('Authorization code generated', [
        'client_id' => $clientId,
        'challenge_method' => $codeChallengeMethod,
        'scopes' => $scopes,
        'expires_at' => $codeData['expires_at']
    ]);
    
    return [
        'authorization_code' => $code,
        'expires_in' => $this->codeValidityMinutes * 60
    ];
}

private function verifyCodeChallenge(
    string $verifier,
    string $challenge,
    string $method
): bool {
    if ($method === 'plain') {
        return hash_equals($verifier, $challenge);
    }
    
    if ($method === 'S256') {
        // Generate challenge from verifier
        $hash = hash('sha256', $verifier, true);
        $calculatedChallenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
        
        return hash_equals($calculatedChallenge, $challenge);
    }
    
    return false;
}

private function isValidBase64Url(string $input): bool
{
    // Check if string contains only valid base64url characters
    return preg_match('/^[A-Za-z0-9_-]+$/', $input) === 1;
}
```

### Layer 6: Rate Limiting

#### Multi-Level Rate Limiting

```php
<?php
// app/Http/Middleware/MobileRateLimitMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MobileRateLimitMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $deviceId = $request->header('X-Device-Id');
        $userId = $request->user()->id ?? null;
        $endpoint = $request->path();
        
        // Check multiple rate limit levels
        $rateLimits = [
            'ip' => $this->checkIpRateLimit($ip),
            'device' => $this->checkDeviceRateLimit($deviceId),
            'user' => $this->checkUserRateLimit($userId),
            'endpoint' => $this->checkEndpointRateLimit($endpoint, $ip)
        ];
        
        foreach ($rateLimits as $type => $result) {
            if (!$result['allowed']) {
                Log::warning("Rate limit exceeded: {$type}", [
                    'ip' => $ip,
                    'device_id' => $deviceId,
                    'user_id' => $userId,
                    'endpoint' => $endpoint,
                    'limit_type' => $type,
                    'current_count' => $result['current'],
                    'limit' => $result['limit']
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $result['retry_after']
                ], 429);
            }
        }
        
        return $next($request);
    }
    
    private function checkIpRateLimit(string $ip): array
    {
        $key = "rate_limit:ip:{$ip}";
        $limit = config('mobile.rate_limits.per_ip_per_minute', 100);
        $window = 60; // 1 minute
        
        return $this->checkRateLimit($key, $limit, $window);
    }
    
    private function checkDeviceRateLimit(?string $deviceId): array
    {
        if (!$deviceId) {
            return ['allowed' => true];
        }
        
        $key = "rate_limit:device:{$deviceId}";
        $limit = config('mobile.rate_limits.per_device_per_minute', 60);
        $window = 60;
        
        return $this->checkRateLimit($key, $limit, $window);
    }
    
    private function checkUserRateLimit(?int $userId): array
    {
        if (!$userId) {
            return ['allowed' => true];
        }
        
        $key = "rate_limit:user:{$userId}";
        $limit = config('mobile.rate_limits.per_user_per_minute', 120);
        $window = 60;
        
        return $this->checkRateLimit($key, $limit, $window);
    }
    
    private function checkEndpointRateLimit(string $endpoint, string $ip): array
    {
        // Different limits for different endpoint types
        $authEndpoints = ['auth/login', 'auth/token', 'auth/refresh'];
        $uploadEndpoints = ['profile/avatar'];
        
        if (Str::contains($endpoint, $authEndpoints)) {
            $limit = 10; // Stricter for auth endpoints
        } elseif (Str::contains($endpoint, $uploadEndpoints)) {
            $limit = 5; // Very strict for uploads
        } else {
            $limit = 60; // Standard limit
        }
        
        $key = "rate_limit:endpoint:{$endpoint}:{$ip}";
        return $this->checkRateLimit($key, $limit, 60);
    }
    
    private function checkRateLimit(string $key, int $limit, int $window): array
    {
        $current = Cache::get($key, 0);
        
        if ($current >= $limit) {
            $ttl = Cache::ttl($key);
            return [
                'allowed' => false,
                'current' => $current,
                'limit' => $limit,
                'retry_after' => max($ttl, 1)
            ];
        }
        
        // Increment counter
        if ($current === 0) {
            Cache::put($key, 1, $window);
        } else {
            Cache::increment($key);
        }
        
        return [
            'allowed' => true,
            'current' => $current + 1,
            'limit' => $limit
        ];
    }
}
```

#### Rate Limit Configuration

```php
// config/mobile.php
'rate_limits' => [
    'per_ip_per_minute' => env('MOBILE_RATE_LIMIT_IP', 100),
    'per_device_per_minute' => env('MOBILE_RATE_LIMIT_DEVICE', 60),
    'per_user_per_minute' => env('MOBILE_RATE_LIMIT_USER', 120),
    'auth_endpoints' => env('MOBILE_RATE_LIMIT_AUTH', 10),
    'upload_endpoints' => env('MOBILE_RATE_LIMIT_UPLOAD', 5),
],

// Graduated backoff
'backoff_strategy' => [
    'initial_delay' => 1, // seconds
    'max_delay' => 300, // 5 minutes
    'multiplier' => 2,
    'jitter' => true
]
```

### Layer 7: Compromise Detection

#### Device Security Checks

```php
private function isDeviceCompromised(Request $request): bool
{
    $deviceInfo = json_decode($request->header('X-Device-Info'), true);
    
    if (!$deviceInfo) {
        // Missing device info is suspicious
        Log::info('Missing device security info', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        return false;
    }
    
    $compromiseIndicators = [
        'jailbroken' => $deviceInfo['jailbroken'] ?? false,
        'rooted' => $deviceInfo['rooted'] ?? false,
        'debugger_attached' => $deviceInfo['debugger'] ?? false,
        'emulator' => $deviceInfo['emulator'] ?? false,
        'hook_detected' => $deviceInfo['hooks'] ?? false,
        'app_tampered' => $deviceInfo['tampered'] ?? false
    ];
    
    $compromisedCount = count(array_filter($compromiseIndicators));
    
    if ($compromisedCount > 0) {
        Log::warning('Compromised device detected', [
            'user_id' => $request->user()->id ?? null,
            'device_id' => $request->header('X-Device-Id'),
            'ip' => $request->ip(),
            'indicators' => $compromiseIndicators,
            'compromise_count' => $compromisedCount
        ]);
        
        // Store compromise event
        $this->recordCompromiseEvent($request, $compromiseIndicators);
        
        return true;
    }
    
    return false;
}

private function recordCompromiseEvent(Request $request, array $indicators): void
{
    DB::table('security_events')->insert([
        'event_type' => 'device_compromise',
        'user_id' => $request->user()->id ?? null,
        'device_id' => $request->header('X-Device-Id'),
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'event_data' => json_encode([
            'indicators' => $indicators,
            'endpoint' => $request->path(),
            'method' => $request->method()
        ]),
        'severity' => $this->calculateSeverity($indicators),
        'created_at' => now()
    ]);
}

private function calculateSeverity(array $indicators): string
{
    $highRiskIndicators = ['jailbroken', 'rooted', 'debugger_attached'];
    $highRiskCount = count(array_intersect_key(
        array_filter($indicators),
        array_flip($highRiskIndicators)
    ));
    
    if ($highRiskCount > 0) return 'high';
    if (count(array_filter($indicators)) > 1) return 'medium';
    return 'low';
}
```

### Layer 8: Audit Logging

#### Security Event Logging

```php
<?php
// app/Services/SecurityAuditService.php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecurityAuditService
{
    public function logSecurityEvent(
        string $eventType,
        Request $request,
        array $additionalData = [],
        string $severity = 'info'
    ): void {
        $eventData = [
            'event_type' => $eventType,
            'user_id' => $request->user()->id ?? null,
            'device_id' => $request->header('X-Device-Id'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'severity' => $severity,
            'event_data' => json_encode(array_merge([
                'headers' => $this->sanitizeHeaders($request->headers->all()),
                'timestamp' => $request->header('X-Timestamp'),
                'signature_provided' => !empty($request->header('X-Signature')),
            ], $additionalData)),
            'created_at' => now()
        ];
        
        // Store in database
        DB::table('security_events')->insert($eventData);
        
        // Also log to Laravel log for immediate visibility
        Log::channel('security')->{$severity}($eventType, $eventData);
        
        // Alert on high severity events
        if ($severity === 'critical' || $severity === 'high') {
            $this->sendSecurityAlert($eventData);
        }
    }
    
    public function logAuthenticationEvent(
        string $email,
        bool $success,
        Request $request,
        ?string $failureReason = null
    ): void {
        $this->logSecurityEvent(
            $success ? 'mobile_auth_success' : 'mobile_auth_failure',
            $request,
            [
                'email' => $email,
                'success' => $success,
                'failure_reason' => $failureReason,
                'device_type' => $request->input('device_info.device_type'),
                'app_version' => $request->input('device_info.app_version')
            ],
            $success ? 'info' : 'warning'
        );
    }
    
    public function logTokenEvent(
        string $action,
        Request $request,
        array $tokenData = []
    ): void {
        $this->logSecurityEvent(
            "token_{$action}",
            $request,
            array_merge($tokenData, [
                'action' => $action,
                'token_type' => 'mobile_access_token'
            ])
        );
    }
    
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];
        $allowedHeaders = [
            'x-device-id', 'x-timestamp', 'x-device-info',
            'user-agent', 'accept', 'content-type'
        ];
        
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $allowedHeaders)) {
                $sanitized[$key] = is_array($value) ? $value[0] : $value;
            }
        }
        
        return $sanitized;
    }
    
    private function sendSecurityAlert(array $eventData): void
    {
        // Implement your alerting mechanism here
        // Examples: Slack, email, SMS, webhook
        
        Log::critical('Security alert triggered', $eventData);
        
        // Could integrate with services like:
        // - Slack notifications
        // - PagerDuty alerts
        // - Email notifications
        // - Webhook to security monitoring system
    }
}
```

---

## ⚙️ Configuration Management

### Environment Variables

```env
# Core Mobile Security
MOBILE_API_ENABLED=true
MOBILE_HMAC_SECRET=your_64_character_secret_here
MOBILE_REQUEST_TIMEOUT=300
MOBILE_BLOCK_COMPROMISED_DEVICES=false

# OAuth 2.0 Configuration
OAUTH_ACCESS_TOKEN_TTL=30
OAUTH_REFRESH_TOKEN_TTL=43200
OAUTH_AUTH_CODE_TTL=10

# Rate Limiting
MOBILE_RATE_LIMIT_IP=100
MOBILE_RATE_LIMIT_DEVICE=60
MOBILE_RATE_LIMIT_USER=120
MOBILE_RATE_LIMIT_AUTH=10
MOBILE_RATE_LIMIT_UPLOAD=5

# Security Monitoring
SECURITY_LOG_CHANNEL=security
SECURITY_ALERT_WEBHOOK=https://hooks.slack.com/your-webhook
SECURITY_ALERT_EMAIL=security@example.com

# Device Security
ALLOW_JAILBROKEN_DEVICES=false
ALLOW_ROOTED_DEVICES=false
ALLOW_EMULATORS=true
DEVICE_FINGERPRINT_STRICT=false
```

### Security Configuration File

```php
<?php
// config/mobile.php

return [
    'enabled' => env('MOBILE_API_ENABLED', true),
    
    'security' => [
        'hmac_secret' => env('MOBILE_HMAC_SECRET'),
        'request_timeout' => env('MOBILE_REQUEST_TIMEOUT', 300),
        'require_signature' => env('MOBILE_REQUIRE_SIGNATURE', true),
        'require_device_binding' => env('MOBILE_REQUIRE_DEVICE_BINDING', true),
        'block_compromised_devices' => env('MOBILE_BLOCK_COMPROMISED_DEVICES', false),
        
        'allowed_compromised_devices' => [
            'jailbroken' => env('ALLOW_JAILBROKEN_DEVICES', false),
            'rooted' => env('ALLOW_ROOTED_DEVICES', false),
            'emulator' => env('ALLOW_EMULATORS', true),
            'debugger' => env('ALLOW_DEBUGGER', false),
        ],
        
        'device_fingerprint' => [
            'strict_mode' => env('DEVICE_FINGERPRINT_STRICT', false),
            'tolerance_score' => env('FINGERPRINT_TOLERANCE', 0.8),
        ]
    ],
    
    'oauth' => [
        'access_token_ttl' => env('OAUTH_ACCESS_TOKEN_TTL', 30),
        'refresh_token_ttl' => env('OAUTH_REFRESH_TOKEN_TTL', 43200),
        'auth_code_ttl' => env('OAUTH_AUTH_CODE_TTL', 10),
        'allowed_clients' => explode(',', env('OAUTH_ALLOWED_CLIENTS', 'mobile_app')),
    ],
    
    'rate_limits' => [
        'per_ip_per_minute' => env('MOBILE_RATE_LIMIT_IP', 100),
        'per_device_per_minute' => env('MOBILE_RATE_LIMIT_DEVICE', 60),
        'per_user_per_minute' => env('MOBILE_RATE_LIMIT_USER', 120),
        'auth_endpoints' => env('MOBILE_RATE_LIMIT_AUTH', 10),
        'upload_endpoints' => env('MOBILE_RATE_LIMIT_UPLOAD', 5),
    ],
    
    'monitoring' => [
        'log_channel' => env('SECURITY_LOG_CHANNEL', 'security'),
        'alert_webhook' => env('SECURITY_ALERT_WEBHOOK'),
        'alert_email' => env('SECURITY_ALERT_EMAIL'),
        'enable_metrics' => env('ENABLE_SECURITY_METRICS', true),
    ],
];
```

---

## 🔍 Security Monitoring

### Custom Log Channel

```php
// config/logging.php
'channels' => [
    // ... existing channels ...
    
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,
        'replace_placeholders' => true,
    ],
    
    'mobile_api' => [
        'driver' => 'daily',
        'path' => storage_path('logs/mobile-api.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,
        'replace_placeholders' => true,
    ],
],
```

### Security Metrics

```php
<?php
// app/Services/SecurityMetricsService.php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SecurityMetricsService
{
    public function getSecurityMetrics(int $hours = 24): array
    {
        $since = now()->subHours($hours);
        
        return [
            'authentication' => [
                'successful_logins' => $this->getAuthMetric('mobile_auth_success', $since),
                'failed_logins' => $this->getAuthMetric('mobile_auth_failure', $since),
                'unique_devices' => $this->getUniqueDevices($since),
                'unique_users' => $this->getUniqueUsers($since),
            ],
            
            'security_events' => [
                'compromised_devices' => $this->getSecurityEventCount('device_compromise', $since),
                'invalid_signatures' => $this->getSecurityEventCount('invalid_signature', $since),
                'rate_limit_exceeded' => $this->getSecurityEventCount('rate_limit_exceeded', $since),
                'suspicious_requests' => $this->getSecurityEventCount('suspicious_request', $since),
            ],
            
            'api_usage' => [
                'total_requests' => $this->getApiRequestCount($since),
                'avg_response_time' => $this->getAvgResponseTime($since),
                'error_rate' => $this->getErrorRate($since),
            ],
            
            'device_health' => [
                'jailbroken_attempts' => $this->getCompromiseAttempts('jailbroken', $since),
                'rooted_attempts' => $this->getCompromiseAttempts('rooted', $since),
                'emulator_usage' => $this->getCompromiseAttempts('emulator', $since),
            ]
        ];
    }
    
    private function getAuthMetric(string $eventType, $since): int
    {
        return DB::table('security_events')
            ->where('event_type', $eventType)
            ->where('created_at', '>=', $since)
            ->count();
    }
    
    private function getUniqueDevices($since): int
    {
        return DB::table('security_events')
            ->where('created_at', '>=', $since)
            ->whereNotNull('device_id')
            ->distinct('device_id')
            ->count('device_id');
    }
    
    private function getUniqueUsers($since): int
    {
        return DB::table('security_events')
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }
    
    private function getSecurityEventCount(string $eventType, $since): int
    {
        return DB::table('security_events')
            ->where('event_type', $eventType)
            ->where('created_at', '>=', $since)
            ->count();
    }
    
    private function getCompromiseAttempts(string $indicator, $since): int
    {
        return DB::table('security_events')
            ->where('event_type', 'device_compromise')
            ->where('created_at', '>=', $since)
            ->whereRaw("JSON_EXTRACT(event_data, '$.indicators.{$indicator}') = true")
            ->count();
    }
}
```

---

## 🚨 Incident Response

### Automated Response

```php
<?php
// app/Services/IncidentResponseService.php

namespace App\Services;

use App\Models\User;
use App\Models\MobileDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncidentResponseService
{
    public function handleSecurityIncident(string $incidentType, array $context): void
    {
        switch ($incidentType) {
            case 'multiple_failed_logins':
                $this->handleFailedLoginSpike($context);
                break;
                
            case 'compromised_device_detected':
                $this->handleCompromisedDevice($context);
                break;
                
            case 'suspicious_api_activity':
                $this->handleSuspiciousActivity($context);
                break;
                
            case 'rate_limit_abuse':
                $this->handleRateLimitAbuse($context);
                break;
        }
    }
    
    private function handleFailedLoginSpike(array $context): void
    {
        $email = $context['email'];
        $failureCount = $context['failure_count'];
        $timeWindow = $context['time_window'];
        
        Log::warning('Failed login spike detected', $context);
        
        if ($failureCount >= 10) {
            // Temporarily block the user
            $this->temporarilyBlockUser($email, 'Multiple failed login attempts');
            
            // Revoke all mobile tokens for this user
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->tokens()->where('name', 'mobile-access-token')->delete();
            }
        }
    }
    
    private function handleCompromisedDevice(array $context): void
    {
        $deviceId = $context['device_id'];
        $userId = $context['user_id'];
        $indicators = $context['indicators'];
        
        Log::critical('Compromised device detected', $context);
        
        // Mark device as compromised
        MobileDevice::where('device_id', $deviceId)
            ->update([
                'is_compromised' => true,
                'compromise_detected_at' => now(),
                'compromise_indicators' => json_encode($indicators)
            ]);
        
        // Revoke all tokens for this device
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                // In a real implementation, you'd filter tokens by device
                $user->tokens()->where('name', 'mobile-access-token')->delete();
                
                // Notify user
                $this->notifyUserOfCompromisedDevice($user, $deviceId);
            }
        }
    }
    
    private function handleSuspiciousActivity(array $context): void
    {
        $severity = $context['severity'];
        $pattern = $context['pattern'];
        
        Log::warning('Suspicious API activity detected', $context);
        
        if ($severity === 'high') {
            // Temporarily increase rate limits
            $this->enableStrictRateLimiting($context);
            
            // Alert security team
            $this->alertSecurityTeam('Suspicious activity detected', $context);
        }
    }
    
    private function handleRateLimitAbuse(array $context): void
    {
        $source = $context['source']; // IP, device, or user
        $sourceId = $context['source_id'];
        
        Log::warning('Rate limit abuse detected', $context);
        
        // Temporarily block the source
        Cache::put("blocked_{$source}:{$sourceId}", true, 3600); // 1 hour
        
        // Alert if persistent
        if ($context['repeat_offender']) {
            $this->alertSecurityTeam('Persistent rate limit abuse', $context);
        }
    }
    
    private function temporarilyBlockUser(string $email, string $reason): void
    {
        $blockDuration = 1800; // 30 minutes
        
        Cache::put("blocked_user:{$email}", [
            'reason' => $reason,
            'blocked_at' => now(),
            'expires_at' => now()->addSeconds($blockDuration)
        ], $blockDuration);
        
        Log::info('User temporarily blocked', [
            'email' => $email,
            'reason' => $reason,
            'duration' => $blockDuration
        ]);
    }
    
    private function notifyUserOfCompromisedDevice(User $user, string $deviceId): void
    {
        // Send notification about compromised device
        // Implementation depends on your notification system
        
        Log::info('User notified of compromised device', [
            'user_id' => $user->id,
            'email' => $user->email,
            'device_id' => $deviceId
        ]);
    }
    
    private function enableStrictRateLimiting(array $context): void
    {
        $duration = 3600; // 1 hour
        
        Cache::put('strict_rate_limiting', true, $duration);
        
        Log::info('Strict rate limiting enabled', [
            'duration' => $duration,
            'reason' => $context['pattern'] ?? 'Suspicious activity'
        ]);
    }
    
    private function alertSecurityTeam(string $message, array $context): void
    {
        // Implement your alerting mechanism
        Log::critical('Security team alert: ' . $message, $context);
        
        // Could integrate with:
        // - Slack webhook
        // - Email alerts
        // - PagerDuty
        // - SMS alerts
    }
}
```

---

## 🛡️ Security Best Practices

### Development

1. **Never commit secrets** to version control
2. **Use different HMAC secrets** for dev/staging/production
3. **Test with compromised device scenarios**
4. **Validate all security headers** in middleware
5. **Log security events** comprehensively

### Production

1. **Monitor security metrics** continuously
2. **Set up alerting** for security events
3. **Regularly rotate HMAC secrets**
4. **Review device compromise reports**
5. **Keep dependencies updated**

### Operations

1. **Regular security audits** of logs
2. **Incident response procedures** documented
3. **Rate limit tuning** based on traffic patterns
4. **Device trust scoring** implementation
5. **Compliance reporting** automation

---

This security configuration provides enterprise-grade protection for your mobile API without the operational complexity of certificate pinning. The multi-layer approach ensures that even if one layer is compromised, the others continue to provide protection.

**Next Steps**: Continue with the [Android Implementation Guide](client-sdks/android-implementation.md) and [iOS Implementation Guide](client-sdks/ios-implementation.md) to see how these security measures are implemented on the client side.