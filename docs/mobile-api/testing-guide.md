# 🧪 Mobile API Testing Guide

## Overview

This guide provides comprehensive testing strategies for the Tenant 1 Mobile API system, covering backend Laravel testing, mobile client testing, security testing, and integration testing. The testing approach ensures reliability, security, and performance across the entire mobile authentication ecosystem.

## Testing Philosophy

### Multi-Layer Testing Strategy

```
┌─────────────────────────────────────────────────────────────┐
│                     Testing Pyramid                        │
├─────────────────────────────────────────────────────────────┤
│ End-to-End Tests     │ Cross-platform mobile app tests     │
│ Integration Tests    │ API + Mobile client integration     │
│ Component Tests      │ Service layer + middleware tests    │
│ Unit Tests          │ Individual function/method tests     │
└─────────────────────────────────────────────────────────────┘
```

### Testing Principles

1. **Security First**: Every security feature must have comprehensive tests
2. **Mobile-Centric**: Focus on mobile-specific scenarios (offline, poor network, etc.)
3. **Real-World Scenarios**: Test actual user workflows and edge cases
4. **Performance Focused**: Validate API response times and mobile app performance
5. **Cross-Platform**: Ensure consistency between iOS and Android implementations

---

## 🔧 Backend Testing (Laravel)

### 1. Unit Tests

#### Authentication Service Tests

```php
<?php
// tests/Unit/MobileAuthServiceTest.php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\MobileAuthService;
use App\Services\SecureSSOService;
use App\Models\User;
use App\Models\MobileDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

class MobileAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $authService;
    protected $mockSSOService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSSOService = Mockery::mock(SecureSSOService::class);
        $this->authService = new MobileAuthService($this->mockSSOService);
    }

    public function test_generate_authorization_code_with_valid_pkce()
    {
        $result = $this->authService->generateAuthorizationCode(
            'mobile_app',
            'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'S256',
            ['read', 'write']
        );

        $this->assertArrayHasKey('authorization_code', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertEquals(600, $result['expires_in']); // 10 minutes

        // Verify code is stored in cache
        $code = $result['authorization_code'];
        $this->assertTrue(Cache::has("auth_code:{$code}"));
    }

    public function test_generate_authorization_code_with_invalid_challenge_method()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid code challenge method');

        $this->authService->generateAuthorizationCode(
            'mobile_app',
            'challenge',
            'INVALID_METHOD'
        );
    }

    public function test_exchange_code_for_tokens_success()
    {
        // Arrange
        $user = User::factory()->create();
        $authCode = 'test_auth_code';
        $codeVerifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
        $codeChallenge = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';
        
        // Store auth code in cache
        Cache::put("auth_code:{$authCode}", [
            'client_id' => 'mobile_app',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'scopes' => ['read', 'write'],
            'expires_at' => now()->addMinutes(10)
        ], 600);

        // Mock SSO service response
        $this->mockSSOService
            ->shouldReceive('login')
            ->once()
            ->with('test@example.com', 'password')
            ->andReturn([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'token' => 'jwt_token_here'
            ]);

        $this->mockSSOService
            ->shouldReceive('recordLoginAudit')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->authService->exchangeCodeForTokens(
            $authCode,
            $codeVerifier,
            'device_123',
            [
                'device_type' => 'ios',
                'device_name' => 'iPhone Test',
                'device_model' => 'iPhone16,1',
                'os_version' => '17.0',
                'app_version' => '1.0.0'
            ],
            'test@example.com',
            'password'
        );

        // Assert
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertEquals(1800, $result['expires_in']); // 30 minutes
        $this->assertEquals($user->email, $result['user']['email']);

        // Verify auth code is deleted
        $this->assertFalse(Cache::has("auth_code:{$authCode}"));

        // Verify device is registered
        $this->assertDatabaseHas('mobile_devices', [
            'device_id' => 'device_123',
            'user_id' => $user->id,
            'device_type' => 'ios'
        ]);
    }

    public function test_exchange_code_with_invalid_verifier()
    {
        $authCode = 'test_auth_code';
        $codeChallenge = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';
        
        Cache::put("auth_code:{$authCode}", [
            'client_id' => 'mobile_app',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'scopes' => ['read', 'write'],
            'expires_at' => now()->addMinutes(10)
        ], 600);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid code verifier');

        $this->authService->exchangeCodeForTokens(
            $authCode,
            'invalid_verifier',
            'device_123',
            [],
            'test@example.com',
            'password'
        );
    }

    public function test_refresh_token_success()
    {
        // Arrange
        $user = User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id' => $user->id,
            'device_id' => 'device_123'
        ]);

        $refreshToken = $this->generateTestRefreshToken($user, $device);

        // Act
        $result = $this->authService->refreshAccessToken(
            $refreshToken,
            'device_123',
            'test_fingerprint'
        );

        // Assert
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertEquals(1800, $result['expires_in']);

        // Verify device last_seen_at is updated
        $device->refresh();
        $this->assertNotNull($device->last_seen_at);
    }

    public function test_refresh_token_with_device_mismatch()
    {
        $user = User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id' => $user->id,
            'device_id' => 'device_123'
        ]);

        $refreshToken = $this->generateTestRefreshToken($user, $device);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Device mismatch');

        $this->authService->refreshAccessToken(
            $refreshToken,
            'wrong_device',
            'test_fingerprint'
        );
    }

    private function generateTestRefreshToken(User $user, MobileDevice $device): string
    {
        $payload = [
            'user_id' => $user->id,
            'device_id' => $device->device_id,
            'device_fingerprint' => $device->fingerprint,
            'iat' => time(),
            'exp' => time() + (30 * 24 * 60 * 60), // 30 days
            'jti' => \Illuminate\Support\Str::uuid()->toString()
        ];
        
        return \Firebase\JWT\JWT::encode($payload, env('JWT_SECRET'), 'HS256');
    }
}
```

#### Security Middleware Tests

```php
<?php
// tests/Unit/MobileSecurityMiddlewareTest.php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\MobileSecurityMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use App\Models\MobileDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MobileSecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new MobileSecurityMiddleware();
    }

    public function test_valid_request_signature_passes()
    {
        $request = $this->createSignedRequest('POST', '/api/v1/mobile/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_invalid_signature_fails()
    {
        $request = Request::create('/api/v1/mobile/auth/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $request->headers->set('X-Timestamp', time());
        $request->headers->set('X-Device-Id', 'device_123');
        $request->headers->set('X-Signature', 'invalid_signature');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid request signature', $responseData['error']);
    }

    public function test_expired_timestamp_fails()
    {
        $oldTimestamp = time() - 400; // 400 seconds ago (> 300 second tolerance)
        
        $request = $this->createSignedRequest('POST', '/api/v1/mobile/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ], $oldTimestamp);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Request expired or invalid timestamp', $responseData['error']);
    }

    public function test_device_binding_verification()
    {
        $user = User::factory()->create();
        $device = MobileDevice::factory()->create([
            'user_id' => $user->id,
            'device_id' => 'device_123'
        ]);

        $request = $this->createSignedRequest('GET', '/api/v1/mobile/profile');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_unregistered_device_fails()
    {
        $user = User::factory()->create();
        
        $request = $this->createSignedRequest('GET', '/api/v1/mobile/profile', [], null, 'unregistered_device');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Device verification failed', $responseData['error']);
    }

    public function test_compromised_device_detection()
    {
        $request = $this->createSignedRequest('POST', '/api/v1/mobile/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        // Add compromised device info
        $deviceInfo = json_encode([
            'jailbroken' => true,
            'rooted' => false,
            'debugger' => false,
            'emulator' => false
        ]);
        $request->headers->set('X-Device-Info', $deviceInfo);

        // Mock configuration to block compromised devices
        config(['mobile.block_compromised_devices' => true]);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Device security check failed', $responseData['error']);
    }

    private function createSignedRequest(
        string $method,
        string $path,
        array $data = [],
        ?int $timestamp = null,
        string $deviceId = 'device_123'
    ): Request {
        $timestamp = $timestamp ?? time();
        $body = empty($data) ? '' : json_encode($data);
        
        $canonicalRequest = "{$method}|{$path}|{$timestamp}|{$deviceId}|{$body}";
        $signature = hash_hmac('sha256', $canonicalRequest, config('mobile.security.hmac_secret'));

        $request = Request::create($path, $method, $data);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Timestamp', $timestamp);
        $request->headers->set('X-Device-Id', $deviceId);
        $request->headers->set('X-Signature', $signature);

        return $request;
    }
}
```

### 2. Feature Tests

#### Authentication API Tests

```php
<?php
// tests/Feature/MobileAuthApiTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MobileDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class MobileAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        config([
            'mobile.security.hmac_secret' => 'test_hmac_secret_for_testing_only',
            'mobile.block_compromised_devices' => false
        ]);
    }

    public function test_complete_oauth_flow()
    {
        // Step 1: Get authorization code
        $authorizeResponse = $this->postSignedJson('/api/v1/mobile/auth/authorize', [
            'client_id' => 'mobile_app',
            'code_challenge' => 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM',
            'code_challenge_method' => 'S256'
        ]);

        $authorizeResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'authorization_code',
                    'expires_in'
                ]
            ]);

        $authCode = $authorizeResponse->json('data.authorization_code');

        // Step 2: Exchange code for tokens
        $tokenResponse = $this->postSignedJson('/api/v1/mobile/auth/token', [
            'code' => $authCode,
            'code_verifier' => 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk',
            'device_id' => 'test_device_001',
            'email' => 'user@tenant1.com',
            'password' => 'password',
            'device_type' => 'ios',
            'device_name' => 'iPhone Test',
            'device_model' => 'iPhone16,1',
            'os_version' => '17.0',
            'app_version' => '1.0.0',
            'screen_resolution' => '1179x2556',
            'timezone' => 'America/New_York',
            'language' => 'en-US'
        ]);

        $tokenResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token_type',
                    'access_token',
                    'refresh_token',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ]
                ]
            ]);

        // Verify device was registered
        $this->assertDatabaseHas('mobile_devices', [
            'device_id' => 'test_device_001',
            'device_type' => 'ios'
        ]);
    }

    public function test_direct_login_flow()
    {
        $response = $this->postSignedJson('/api/v1/mobile/auth/login', [
            'email' => 'user@tenant1.com',
            'password' => 'password',
            'device_id' => 'test_device_002',
            'device_info' => [
                'device_type' => 'android',
                'device_name' => 'Samsung Galaxy S24',
                'device_model' => 'SM-S921U',
                'os_version' => '14',
                'app_version' => '1.0.0',
                'screen_resolution' => '1440x3088',
                'timezone' => 'America/New_York',
                'language' => 'en-US'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token_type',
                    'access_token',
                    'refresh_token',
                    'expires_in',
                    'user'
                ]
            ]);

        $accessToken = $response->json('data.access_token');
        $this->assertNotEmpty($accessToken);

        // Test protected endpoint with token
        $profileResponse = $this->getSignedJson('/api/v1/mobile/profile', [
            'Authorization' => "Bearer {$accessToken}"
        ]);

        $profileResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);
    }

    public function test_token_refresh_flow()
    {
        // First, get tokens via direct login
        $loginResponse = $this->postSignedJson('/api/v1/mobile/auth/login', [
            'email' => 'user@tenant1.com',
            'password' => 'password',
            'device_id' => 'test_device_003',
            'device_info' => [
                'device_type' => 'ios',
                'device_name' => 'iPhone Test',
                'device_model' => 'iPhone16,1',
                'os_version' => '17.0',
                'app_version' => '1.0.0'
            ]
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        // Test token refresh
        $refreshResponse = $this->postSignedJson('/api/v1/mobile/auth/refresh', [
            'refresh_token' => $refreshToken,
            'device_id' => 'test_device_003',
            'device_fingerprint' => 'test_fingerprint'
        ]);

        $refreshResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token_type',
                    'access_token',
                    'refresh_token',
                    'expires_in'
                ]
            ]);

        // Verify new tokens are different
        $newAccessToken = $refreshResponse->json('data.access_token');
        $newRefreshToken = $refreshResponse->json('data.refresh_token');
        
        $this->assertNotEquals($loginResponse->json('data.access_token'), $newAccessToken);
        $this->assertNotEquals($refreshToken, $newRefreshToken);
    }

    public function test_logout_flow()
    {
        // Login first
        $loginResponse = $this->postSignedJson('/api/v1/mobile/auth/login', [
            'email' => 'user@tenant1.com',
            'password' => 'password',
            'device_id' => 'test_device_004',
            'device_info' => [
                'device_type' => 'ios',
                'device_name' => 'iPhone Test',
                'device_model' => 'iPhone16,1',
                'os_version' => '17.0',
                'app_version' => '1.0.0'
            ]
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        // Test logout
        $logoutResponse = $this->postSignedJson('/api/v1/mobile/auth/logout', [
            'revoke_all_device_tokens' => false
        ], [
            'Authorization' => "Bearer {$accessToken}"
        ]);

        $logoutResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);

        // Verify token is no longer valid
        $profileResponse = $this->getSignedJson('/api/v1/mobile/profile', [
            'Authorization' => "Bearer {$accessToken}"
        ]);

        $profileResponse->assertStatus(401);
    }

    public function test_rate_limiting()
    {
        $deviceId = 'rate_limit_test_device';
        
        // Make requests up to the limit
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postSignedJson('/api/v1/mobile/auth/login', [
                'email' => 'user@tenant1.com',
                'password' => 'wrong_password',
                'device_id' => $deviceId,
                'device_info' => ['device_type' => 'test']
            ], [], $deviceId);
            
            // First 10 should be accepted (though they'll fail auth)
            $this->assertContains($response->getStatusCode(), [200, 401]);
        }

        // 11th request should be rate limited
        $response = $this->postSignedJson('/api/v1/mobile/auth/login', [
            'email' => 'user@tenant1.com',
            'password' => 'wrong_password',
            'device_id' => $deviceId,
            'device_info' => ['device_type' => 'test']
        ], [], $deviceId);

        $response->assertStatus(429)
            ->assertJsonStructure([
                'success',
                'error',
                'retry_after'
            ]);
    }

    private function postSignedJson(
        string $uri,
        array $data = [],
        array $headers = [],
        string $deviceId = 'test_device_default'
    ) {
        $timestamp = time();
        $body = json_encode($data);
        $path = parse_url($uri, PHP_URL_PATH);
        
        $canonicalRequest = "POST|{$path}|{$timestamp}|{$deviceId}|{$body}";
        $signature = hash_hmac('sha256', $canonicalRequest, config('mobile.security.hmac_secret'));

        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'X-Timestamp' => $timestamp,
            'X-Device-Id' => $deviceId,
            'X-Signature' => $signature,
            'X-Device-Info' => json_encode([
                'jailbroken' => false,
                'rooted' => false,
                'debugger' => false,
                'emulator' => false
            ])
        ];

        return $this->postJson($uri, $data, array_merge($defaultHeaders, $headers));
    }

    private function getSignedJson(
        string $uri,
        array $headers = [],
        string $deviceId = 'test_device_default'
    ) {
        $timestamp = time();
        $path = parse_url($uri, PHP_URL_PATH);
        
        $canonicalRequest = "GET|{$path}|{$timestamp}|{$deviceId}|";
        $signature = hash_hmac('sha256', $canonicalRequest, config('mobile.security.hmac_secret'));

        $defaultHeaders = [
            'X-Timestamp' => $timestamp,
            'X-Device-Id' => $deviceId,
            'X-Signature' => $signature
        ];

        return $this->getJson($uri, array_merge($defaultHeaders, $headers));
    }
}
```

### 3. Database Tests

```php
<?php
// tests/Feature/DatabaseSchemaTest.php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_devices_table_structure()
    {
        $this->assertTrue(Schema::hasTable('mobile_devices'));

        $columns = [
            'id', 'user_id', 'device_id', 'device_type',
            'device_name', 'device_model', 'os_version',
            'app_version', 'push_token', 'fingerprint',
            'last_seen_at', 'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('mobile_devices', $column),
                "Column {$column} missing from mobile_devices table"
            );
        }

        // Test indexes
        $indexes = Schema::getIndexes('mobile_devices');
        $indexNames = array_column($indexes, 'name');
        
        $this->assertContains('mobile_devices_user_id_device_id_index', $indexNames);
        $this->assertContains('mobile_devices_last_seen_at_index', $indexNames);
    }

    public function test_oauth_tables_structure()
    {
        $this->assertTrue(Schema::hasTable('oauth_auth_codes'));
        $this->assertTrue(Schema::hasTable('oauth_refresh_tokens'));

        // Test oauth_auth_codes columns
        $authCodeColumns = [
            'id', 'user_id', 'client_id', 'scopes',
            'revoked', 'expires_at', 'code_challenge',
            'code_challenge_method', 'created_at', 'updated_at'
        ];

        foreach ($authCodeColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('oauth_auth_codes', $column),
                "Column {$column} missing from oauth_auth_codes table"
            );
        }

        // Test oauth_refresh_tokens columns
        $refreshTokenColumns = [
            'id', 'access_token_id', 'revoked', 'expires_at',
            'device_id', 'device_fingerprint', 'created_at', 'updated_at'
        ];

        foreach ($refreshTokenColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('oauth_refresh_tokens', $column),
                "Column {$column} missing from oauth_refresh_tokens table"
            );
        }
    }

    public function test_mobile_api_logs_table_structure()
    {
        $this->assertTrue(Schema::hasTable('mobile_api_logs'));

        $columns = [
            'id', 'user_id', 'device_id', 'endpoint',
            'method', 'status_code', 'response_time_ms',
            'ip_address', 'user_agent', 'created_at', 'updated_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('mobile_api_logs', $column),
                "Column {$column} missing from mobile_api_logs table"
            );
        }
    }
}
```

---

## 📱 Mobile Client Testing

### 1. Android Unit Tests

```kotlin
// app/src/test/java/com/tenant1/app/SecurityUtilsTest.kt

package com.tenant1.app

import com.tenant1.app.security.SecurityUtils
import org.junit.Test
import org.junit.Assert.*

class SecurityUtilsTest {

    @Test
    fun `generateCodeVerifier should return 64 character string`() {
        val verifier = SecurityUtils.generateCodeVerifier()
        assertEquals(64, verifier.length)
        
        // Should contain only valid characters
        val validChars = Regex("[A-Za-z0-9._~-]+")
        assertTrue(validChars.matches(verifier))
    }

    @Test
    fun `generateCodeChallenge should create valid S256 challenge`() {
        val verifier = "dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk"
        val challenge = SecurityUtils.generateCodeChallenge(verifier)
        
        // Expected challenge for this verifier
        val expectedChallenge = "E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM"
        assertEquals(expectedChallenge, challenge)
    }

    @Test
    fun `generateHMACSignature should be deterministic`() {
        val canonicalRequest = "POST|/auth/login|1234567890|device123|{}"
        val secret = "test_secret"
        
        val signature1 = SecurityUtils.generateHMACSignature(canonicalRequest, secret)
        val signature2 = SecurityUtils.generateHMACSignature(canonicalRequest, secret)
        
        assertEquals(signature1, signature2)
        assertEquals(64, signature1.length) // SHA256 hex string
    }

    @Test
    fun `createCanonicalRequest should format correctly`() {
        val canonical = SecurityUtils.createCanonicalRequest(
            method = "POST",
            path = "/auth/login",
            timestamp = "1234567890",
            deviceId = "device123",
            body = "{\"email\":\"test@example.com\"}"
        )
        
        val expected = "POST|/auth/login|1234567890|device123|{\"email\":\"test@example.com\"}"
        assertEquals(expected, canonical)
    }

    @Test
    fun `generateRandomString should return string of correct length`() {
        val random8 = SecurityUtils.generateRandomString(8)
        val random16 = SecurityUtils.generateRandomString(16)
        val random32 = SecurityUtils.generateRandomString(32)
        
        assertEquals(8, random8.length)
        assertEquals(16, random16.length)
        assertEquals(32, random32.length)
        
        // Should be different each time
        assertNotEquals(random8, SecurityUtils.generateRandomString(8))
    }

    @Test
    fun `device security checks should work in test environment`() {
        // In test environment, these should be false
        assertFalse(SecurityUtils.isDeviceRooted())
        assertFalse(SecurityUtils.isEmulator()) // Depends on test runner
        
        // Debugger detection might be true in test environment
        // assertFalse(SecurityUtils.isDebuggerAttached())
    }
}
```

```kotlin
// app/src/test/java/com/tenant1/app/AuthServiceTest.kt

package com.tenant1.app

import com.tenant1.app.auth.AuthService
import com.tenant1.app.api.ApiService
import com.tenant1.app.api.models.*
import com.tenant1.app.device.DeviceManager
import com.tenant1.app.storage.SecureStorageManager
import kotlinx.coroutines.test.runTest
import org.junit.Before
import org.junit.Test
import org.mockito.Mock
import org.mockito.MockitoAnnotations
import org.mockito.kotlin.*
import retrofit2.Response
import kotlin.test.assertTrue
import kotlin.test.assertEquals

class AuthServiceTest {

    @Mock
    private lateinit var mockApiService: ApiService

    @Mock
    private lateinit var mockSecureStorage: SecureStorageManager

    @Mock
    private lateinit var mockDeviceManager: DeviceManager

    private lateinit var authService: AuthService

    @Before
    fun setup() {
        MockitoAnnotations.openMocks(this)
        // Note: In real implementation, you'd inject these dependencies
        // authService = AuthService(mockContext, mockApiService, mockSecureStorage, mockDeviceManager)
    }

    @Test
    fun `direct login success should store tokens`() = runTest {
        // Arrange
        val email = "test@example.com"
        val password = "password"
        val mockDeviceInfo = DeviceInfo(
            deviceId = "device_123",
            deviceType = "android",
            deviceName = "Test Device",
            deviceModel = "TestModel",
            osVersion = "11",
            appVersion = "1.0.0",
            screenResolution = "1080x1920",
            timezone = "UTC",
            language = "en-US"
        )
        val mockResponse = ApiResponse(
            success = true,
            data = TokenResponse(
                tokenType = "Bearer",
                accessToken = "access_token",
                refreshToken = "refresh_token",
                expiresIn = 1800,
                user = User("1", "Test User", email)
            )
        )

        whenever(mockDeviceManager.getOrCreateDeviceId()).thenReturn("device_123")
        whenever(mockDeviceManager.getDeviceInfo()).thenReturn(mockDeviceInfo)
        whenever(mockApiService.directLogin(any())).thenReturn(
            Response.success(mockResponse)
        )

        // Act
        val result = authService.directLogin(email, password)

        // Assert
        assertTrue(result.isSuccess)
        verify(mockSecureStorage).storeTokens(
            accessToken = "access_token",
            refreshToken = "refresh_token",
            expiresIn = 1800
        )
        verify(mockSecureStorage).storeUserInfo(
            userId = "1",
            name = "Test User",
            email = email
        )
    }

    @Test
    fun `token refresh should update stored tokens`() = runTest {
        // Arrange
        val mockResponse = ApiResponse(
            success = true,
            data = RefreshTokenResponse(
                tokenType = "Bearer",
                accessToken = "new_access_token",
                refreshToken = "new_refresh_token",
                expiresIn = 1800
            )
        )

        whenever(mockSecureStorage.getRefreshToken()).thenReturn("old_refresh_token")
        whenever(mockDeviceManager.getOrCreateDeviceId()).thenReturn("device_123")
        whenever(mockDeviceManager.generateDeviceFingerprint()).thenReturn("fingerprint")
        whenever(mockApiService.refreshToken(any())).thenReturn(
            Response.success(mockResponse)
        )

        // Act
        val result = authService.refreshToken()

        // Assert
        assertTrue(result.isSuccess)
        verify(mockSecureStorage).storeTokens(
            accessToken = "new_access_token",
            refreshToken = "new_refresh_token",
            expiresIn = 1800
        )
    }

    @Test
    fun `logout should clear tokens`() = runTest {
        // Arrange
        whenever(mockApiService.logout()).thenReturn(
            Response.success(ApiResponse(success = true, data = null))
        )

        // Act
        val result = authService.logout()

        // Assert
        assertTrue(result.isSuccess)
        verify(mockSecureStorage).clearTokens()
    }
}
```

### 2. iOS Unit Tests

```swift
// AuthServiceTests.swift

import XCTest
@testable import Tenant1App

class AuthServiceTests: XCTestCase {
    
    var authService: AuthService!
    var mockNetworkManager: MockNetworkManager!
    var mockKeychainManager: MockKeychainManager!
    var mockDeviceManager: MockDeviceManager!
    
    override func setUp() {
        super.setUp()
        mockNetworkManager = MockNetworkManager()
        mockKeychainManager = MockKeychainManager()
        mockDeviceManager = MockDeviceManager()
        
        // In real implementation, inject these dependencies
        // authService = AuthService(networkManager: mockNetworkManager, ...)
    }
    
    override func tearDown() {
        authService = nil
        mockNetworkManager = nil
        mockKeychainManager = nil
        mockDeviceManager = nil
        super.tearDown()
    }
    
    func testDirectLoginSuccess() async throws {
        // Arrange
        let email = "test@example.com"
        let password = "password"
        let expectedResponse = TokenResponse(
            tokenType: "Bearer",
            accessToken: "access_token",
            refreshToken: "refresh_token",
            expiresIn: 1800,
            scope: "read write",
            user: User(id: "1", name: "Test User", email: email)
        )
        
        mockNetworkManager.mockResponse = expectedResponse
        mockDeviceManager.mockDeviceInfo = DeviceInfo(
            deviceId: "device_123",
            deviceType: "ios",
            deviceName: "iPhone Test",
            deviceModel: "iPhone16,1",
            osVersion: "17.0",
            appVersion: "1.0.0",
            screenResolution: "1179x2556",
            timezone: "America/New_York",
            language: "en-US"
        )
        
        // Act
        let result = try await authService.directLogin(email: email, password: password)
        
        // Assert
        XCTAssertEqual(result.user.email, email)
        XCTAssertEqual(result.accessToken, "access_token")
        XCTAssertTrue(mockKeychainManager.storeTokensCalled)
        XCTAssertTrue(mockKeychainManager.storeUserInfoCalled)
    }
    
    func testTokenRefreshSuccess() async throws {
        // Arrange
        mockKeychainManager.storedRefreshToken = "refresh_token"
        mockDeviceManager.mockDeviceId = "device_123"
        mockDeviceManager.mockFingerprint = "fingerprint"
        
        let expectedResponse = RefreshTokenResponse(
            tokenType: "Bearer",
            accessToken: "new_access_token",
            refreshToken: "new_refresh_token",
            expiresIn: 1800
        )
        
        mockNetworkManager.mockResponse = expectedResponse
        
        // Act
        let result = try await authService.refreshToken()
        
        // Assert
        XCTAssertTrue(result)
        XCTAssertTrue(mockKeychainManager.storeTokensCalled)
    }
    
    func testLogoutClearsTokens() async throws {
        // Arrange
        mockNetworkManager.mockSuccess = true
        
        // Act
        try await authService.logout()
        
        // Assert
        XCTAssertTrue(mockKeychainManager.clearTokensCalled)
        XCTAssertTrue(mockKeychainManager.clearUserInfoCalled)
    }
}

// MARK: - SecurityUtilsTests

class SecurityUtilsTests: XCTestCase {
    
    func testCodeVerifierGeneration() {
        let verifier = SecurityUtils.generateCodeVerifier()
        
        XCTAssertEqual(verifier.count, 64)
        
        // Should contain only valid characters
        let validChars = CharacterSet(charactersIn: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~")
        let verifierChars = CharacterSet(charactersIn: verifier)
        XCTAssertTrue(validChars.isSuperset(of: verifierChars))
    }
    
    func testCodeChallengeGeneration() {
        let verifier = "dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk"
        let challenge = SecurityUtils.generateCodeChallenge(from: verifier)
        
        // Expected challenge for this verifier (S256)
        let expectedChallenge = "E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM"
        XCTAssertEqual(challenge, expectedChallenge)
    }
    
    func testHMACSignatureDeterministic() {
        let canonicalRequest = "POST|/auth/login|1234567890|device123|{}"
        let secret = "test_secret"
        
        let signature1 = SecurityUtils.generateHMACSignature(canonicalRequest: canonicalRequest, secret: secret)
        let signature2 = SecurityUtils.generateHMACSignature(canonicalRequest: canonicalRequest, secret: secret)
        
        XCTAssertEqual(signature1, signature2)
        XCTAssertEqual(signature1.count, 64) // SHA256 hex string
    }
    
    func testCanonicalRequestFormat() {
        let canonical = SecurityUtils.createCanonicalRequest(
            method: "POST",
            path: "/auth/login",
            timestamp: "1234567890",
            deviceId: "device123",
            body: "{\"email\":\"test@example.com\"}"
        )
        
        let expected = "POST|/auth/login|1234567890|device123|{\"email\":\"test@example.com\"}"
        XCTAssertEqual(canonical, expected)
    }
    
    func testRandomStringGeneration() {
        let random8 = SecurityUtils.generateRandomString(length: 8)
        let random16 = SecurityUtils.generateRandomString(length: 16)
        let random32 = SecurityUtils.generateRandomString(length: 32)
        
        XCTAssertEqual(random8.count, 8)
        XCTAssertEqual(random16.count, 16)
        XCTAssertEqual(random32.count, 32)
        
        // Should be different each time
        XCTAssertNotEqual(random8, SecurityUtils.generateRandomString(length: 8))
    }
    
    func testDeviceSecurityChecks() {
        // In test environment
        XCTAssertFalse(SecurityUtils.isJailbroken()) // Should be false in simulator
        XCTAssertTrue(SecurityUtils.isEmulator()) // Should be true in simulator
        // Debugger detection might vary based on test runner
    }
}

// MARK: - Mock Classes

class MockNetworkManager {
    var mockResponse: Any?
    var mockSuccess = true
    var shouldFail = false
    
    func request<T: Codable>(_ endpoint: String, method: HTTPMethod, parameters: [String: Any]?) async throws -> T {
        if shouldFail {
            throw APIError(message: "Mock error")
        }
        
        guard let response = mockResponse as? T else {
            throw APIError(message: "Invalid mock response type")
        }
        
        return response
    }
    
    func requestWithoutData(_ endpoint: String, method: HTTPMethod, parameters: [String: Any]?) async throws -> Bool {
        return mockSuccess
    }
}

class MockKeychainManager {
    var storedAccessToken: String?
    var storedRefreshToken: String?
    var tokenExpired = false
    var storeTokensCalled = false
    var storeUserInfoCalled = false
    var clearTokensCalled = false
    var clearUserInfoCalled = false
    
    func getAccessToken() -> String? {
        return storedAccessToken
    }
    
    func getRefreshToken() -> String? {
        return storedRefreshToken
    }
    
    func isTokenExpired() -> Bool {
        return tokenExpired
    }
    
    func storeTokens(accessToken: String, refreshToken: String, expiresIn: TimeInterval) throws {
        storedAccessToken = accessToken
        storedRefreshToken = refreshToken
        storeTokensCalled = true
    }
    
    func storeUserInfo(userId: String, name: String, email: String) throws {
        storeUserInfoCalled = true
    }
    
    func clearTokens() throws {
        storedAccessToken = nil
        storedRefreshToken = nil
        clearTokensCalled = true
    }
    
    func clearUserInfo() throws {
        clearUserInfoCalled = true
    }
}

class MockDeviceManager {
    var mockDeviceId = "mock_device_123"
    var mockDeviceInfo: DeviceInfo?
    var mockFingerprint = "mock_fingerprint"
    
    func getOrCreateDeviceId() -> String {
        return mockDeviceId
    }
    
    func getDeviceInfo() -> DeviceInfo {
        return mockDeviceInfo ?? DeviceInfo(
            deviceId: mockDeviceId,
            deviceType: "ios",
            deviceName: "Mock iPhone",
            deviceModel: "iPhone16,1",
            osVersion: "17.0",
            appVersion: "1.0.0",
            screenResolution: "1179x2556",
            timezone: "UTC",
            language: "en-US"
        )
    }
    
    func generateDeviceFingerprint() -> String {
        return mockFingerprint
    }
}
```

---

## 🔒 Security Testing

### 1. Penetration Testing Script

```bash
#!/bin/bash
# security_tests.sh

BASE_URL="http://localhost:8001/api/v1/mobile"
DEVICE_ID="security_test_device"
HMAC_SECRET="your_test_hmac_secret"

echo "🔒 Mobile API Security Tests"
echo "================================"

# Test 1: Invalid HMAC Signature
echo "Test 1: Invalid HMAC Signature"
curl -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: $(date +%s)" \
  -H "X-Device-Id: $DEVICE_ID" \
  -H "X-Signature: invalid_signature" \
  -d '{"email":"test@example.com","password":"password"}' \
  -w "\nStatus: %{http_code}\n\n"

# Test 2: Expired Timestamp
echo "Test 2: Expired Timestamp"
OLD_TIMESTAMP=$(($(date +%s) - 400))
CANONICAL_REQUEST="POST|/api/v1/mobile/auth/login|$OLD_TIMESTAMP|$DEVICE_ID|{\"email\":\"test@example.com\",\"password\":\"password\"}"
SIGNATURE=$(echo -n "$CANONICAL_REQUEST" | openssl dgst -sha256 -hmac "$HMAC_SECRET" | cut -d' ' -f2)

curl -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: $OLD_TIMESTAMP" \
  -H "X-Device-Id: $DEVICE_ID" \
  -H "X-Signature: $SIGNATURE" \
  -d '{"email":"test@example.com","password":"password"}' \
  -w "\nStatus: %{http_code}\n\n"

# Test 3: Missing Security Headers
echo "Test 3: Missing Security Headers"
curl -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}' \
  -w "\nStatus: %{http_code}\n\n"

# Test 4: SQL Injection Attempt
echo "Test 4: SQL Injection Attempt"
TIMESTAMP=$(date +%s)
PAYLOAD='{"email":"test@example.com'\''OR 1=1--","password":"password"}'
CANONICAL_REQUEST="POST|/api/v1/mobile/auth/login|$TIMESTAMP|$DEVICE_ID|$PAYLOAD"
SIGNATURE=$(echo -n "$CANONICAL_REQUEST" | openssl dgst -sha256 -hmac "$HMAC_SECRET" | cut -d' ' -f2)

curl -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: $TIMESTAMP" \
  -H "X-Device-Id: $DEVICE_ID" \
  -H "X-Signature: $SIGNATURE" \
  -d "$PAYLOAD" \
  -w "\nStatus: %{http_code}\n\n"

# Test 5: Rate Limiting
echo "Test 5: Rate Limiting"
for i in {1..12}; do
  TIMESTAMP=$(date +%s)
  PAYLOAD='{"email":"test@example.com","password":"wrong_password"}'
  CANONICAL_REQUEST="POST|/api/v1/mobile/auth/login|$TIMESTAMP|$DEVICE_ID|$PAYLOAD"
  SIGNATURE=$(echo -n "$CANONICAL_REQUEST" | openssl dgst -sha256 -hmac "$HMAC_SECRET" | cut -d' ' -f2)
  
  echo "Request $i:"
  curl -X POST "$BASE_URL/auth/login" \
    -H "Content-Type: application/json" \
    -H "X-Timestamp: $TIMESTAMP" \
    -H "X-Device-Id: $DEVICE_ID" \
    -H "X-Signature: $SIGNATURE" \
    -d "$PAYLOAD" \
    -w "Status: %{http_code}\n" \
    -s -o /dev/null
  
  sleep 1
done

echo "🔒 Security tests completed"
```

### 2. Performance Testing

```bash
#!/bin/bash
# performance_tests.sh

BASE_URL="http://localhost:8001/api/v1/mobile"
CONCURRENT_USERS=10
REQUESTS_PER_USER=50

echo "🚀 Mobile API Performance Tests"
echo "================================"

# Function to generate signed request
generate_signed_request() {
  local method=$1
  local path=$2
  local body=$3
  local device_id="perf_test_device_$4"
  local timestamp=$(date +%s)
  
  local canonical_request="$method|$path|$timestamp|$device_id|$body"
  local signature=$(echo -n "$canonical_request" | openssl dgst -sha256 -hmac "your_test_hmac_secret" | cut -d' ' -f2)
  
  curl -X "$method" "$BASE_URL$path" \
    -H "Content-Type: application/json" \
    -H "X-Timestamp: $timestamp" \
    -H "X-Device-Id: $device_id" \
    -H "X-Signature: $signature" \
    -d "$body" \
    -w "%{time_total},%{http_code}\n" \
    -s -o /dev/null
}

# Test login endpoint performance
echo "Testing login endpoint performance..."
for i in $(seq 1 $CONCURRENT_USERS); do
  (
    for j in $(seq 1 $REQUESTS_PER_USER); do
      generate_signed_request "POST" "/auth/login" '{"email":"user@tenant1.com","password":"password","device_id":"perf_device_'$i'","device_info":{"device_type":"test"}}' $i
    done
  ) &
done

wait

echo "Performance tests completed"
```

---

## 🎯 Integration Testing

### 1. Cross-Platform Integration Tests

```javascript
// tests/integration/cross-platform.test.js

const { Builder, By, until } = require('selenium-webdriver');
const { expect } = require('chai');

describe('Cross-Platform Mobile API Integration', () => {
  let androidDriver, iosDriver;

  before(async () => {
    // Set up Android emulator
    androidDriver = await new Builder()
      .forBrowser('chrome')
      .usingServer('http://localhost:4723/wd/hub')
      .withCapabilities({
        platformName: 'Android',
        deviceName: 'Android Emulator',
        app: '/path/to/tenant1-android.apk'
      })
      .build();

    // Set up iOS simulator
    iosDriver = await new Builder()
      .forBrowser('safari')
      .usingServer('http://localhost:4723/wd/hub')
      .withCapabilities({
        platformName: 'iOS',
        deviceName: 'iPhone 15',
        app: '/path/to/tenant1-ios.app'
      })
      .build();
  });

  after(async () => {
    await androidDriver.quit();
    await iosDriver.quit();
  });

  it('should login successfully on both platforms', async () => {
    // Test Android login
    const androidEmailField = await androidDriver.findElement(By.id('email_field'));
    const androidPasswordField = await androidDriver.findElement(By.id('password_field'));
    const androidLoginButton = await androidDriver.findElement(By.id('login_button'));

    await androidEmailField.sendKeys('user@tenant1.com');
    await androidPasswordField.sendKeys('password');
    await androidLoginButton.click();

    await androidDriver.wait(until.elementLocated(By.id('profile_screen')), 10000);

    // Test iOS login
    const iosEmailField = await iosDriver.findElement(By.accessibilityId('Email'));
    const iosPasswordField = await iosDriver.findElement(By.accessibilityId('Password'));
    const iosLoginButton = await iosDriver.findElement(By.accessibilityId('Login'));

    await iosEmailField.sendKeys('user@tenant1.com');
    await iosPasswordField.sendKeys('password');
    await iosLoginButton.click();

    await iosDriver.wait(until.elementLocated(By.accessibilityId('Profile')), 10000);

    // Both platforms should be logged in
    expect(await androidDriver.findElement(By.id('profile_screen')).isDisplayed()).to.be.true;
    expect(await iosDriver.findElement(By.accessibilityId('Profile')).isDisplayed()).to.be.true;
  });

  it('should handle offline scenarios', async () => {
    // Simulate network disconnection
    await androidDriver.setNetworkConnection(0); // Airplane mode
    
    // Try to make API call
    const refreshButton = await androidDriver.findElement(By.id('refresh_button'));
    await refreshButton.click();
    
    // Should show offline message
    const offlineMessage = await androidDriver.wait(
      until.elementLocated(By.id('offline_message')),
      5000
    );
    expect(await offlineMessage.isDisplayed()).to.be.true;
    
    // Restore network
    await androidDriver.setNetworkConnection(6); // WiFi + Data
  });
});
```

### 2. Load Testing

```python
# tests/load/locustfile.py

from locust import HttpUser, task, between
import json
import time
import hashlib
import hmac

class MobileAPIUser(HttpUser):
    wait_time = between(1, 3)
    
    def on_start(self):
        self.device_id = f"load_test_device_{self.user_id}"
        self.hmac_secret = "your_test_hmac_secret"
        self.access_token = None
        self.login()
    
    def generate_signature(self, method, path, body=""):
        timestamp = str(int(time.time()))
        canonical_request = f"{method}|{path}|{timestamp}|{self.device_id}|{body}"
        signature = hmac.new(
            self.hmac_secret.encode(),
            canonical_request.encode(),
            hashlib.sha256
        ).hexdigest()
        
        return {
            "X-Timestamp": timestamp,
            "X-Device-Id": self.device_id,
            "X-Signature": signature,
            "X-Device-Info": json.dumps({
                "jailbroken": False,
                "rooted": False,
                "debugger": False,
                "emulator": False
            })
        }
    
    def login(self):
        body = json.dumps({
            "email": "user@tenant1.com",
            "password": "password",
            "device_id": self.device_id,
            "device_info": {
                "device_type": "test",
                "device_name": "Load Test Device",
                "device_model": "LoadTester",
                "os_version": "1.0",
                "app_version": "1.0.0"
            }
        })
        
        headers = self.generate_signature("POST", "/api/v1/mobile/auth/login", body)
        headers["Content-Type"] = "application/json"
        
        response = self.client.post(
            "/api/v1/mobile/auth/login",
            data=body,
            headers=headers,
            name="Login"
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                self.access_token = data["data"]["access_token"]
    
    @task(3)
    def get_profile(self):
        if not self.access_token:
            return
            
        headers = self.generate_signature("GET", "/api/v1/mobile/profile")
        headers["Authorization"] = f"Bearer {self.access_token}"
        
        self.client.get(
            "/api/v1/mobile/profile",
            headers=headers,
            name="Get Profile"
        )
    
    @task(1)
    def refresh_token(self):
        if not self.access_token:
            return
            
        # Simulate token refresh
        body = json.dumps({
            "refresh_token": "mock_refresh_token",
            "device_id": self.device_id,
            "device_fingerprint": "test_fingerprint"
        })
        
        headers = self.generate_signature("POST", "/api/v1/mobile/auth/refresh", body)
        headers["Content-Type"] = "application/json"
        
        self.client.post(
            "/api/v1/mobile/auth/refresh",
            data=body,
            headers=headers,
            name="Refresh Token"
        )

# Run with: locust -f locustfile.py --host=http://localhost:8001
```

---

## 📊 Test Automation

### 1. GitHub Actions CI/CD

```yaml
# .github/workflows/mobile-api-tests.yml

name: Mobile API Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  backend-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mariadb:10.9
        env:
          MYSQL_ROOT_PASSWORD: root_password
          MYSQL_DATABASE: tenant1_test
          MYSQL_USER: test_user
          MYSQL_PASSWORD: test_password
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        extensions: mbstring, dom, fileinfo, mysql
        tools: composer:v2
    
    - name: Install dependencies
      run: |
        cd tenant1-app
        composer install --prefer-dist --no-progress
    
    - name: Copy environment file
      run: |
        cd tenant1-app
        cp .env.testing .env
    
    - name: Generate application key
      run: |
        cd tenant1-app
        php artisan key:generate
    
    - name: Run migrations
      run: |
        cd tenant1-app
        php artisan migrate --force
    
    - name: Run backend tests
      run: |
        cd tenant1-app
        php artisan test --testsuite=Unit,Feature
    
    - name: Run security tests
      run: |
        cd tenant1-app
        ./tests/security_tests.sh

  android-tests:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup JDK 17
      uses: actions/setup-java@v3
      with:
        java-version: '17'
        distribution: 'temurin'
    
    - name: Setup Android SDK
      uses: android-actions/setup-android@v2
    
    - name: Cache Gradle packages
      uses: actions/cache@v3
      with:
        path: |
          ~/.gradle/caches
          ~/.gradle/wrapper
        key: ${{ runner.os }}-gradle-${{ hashFiles('**/*.gradle*', '**/gradle-wrapper.properties') }}
        restore-keys: |
          ${{ runner.os }}-gradle-
    
    - name: Run Android unit tests
      run: |
        cd android-app
        ./gradlew testDebugUnitTest
    
    - name: Run Android lint
      run: |
        cd android-app
        ./gradlew lintDebug

  ios-tests:
    runs-on: macos-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup Xcode
      uses: maxim-lobanov/setup-xcode@v1
      with:
        xcode-version: latest-stable
    
    - name: Install dependencies
      run: |
        cd ios-app
        pod install
    
    - name: Run iOS unit tests
      run: |
        cd ios-app
        xcodebuild test \
          -workspace Tenant1App.xcworkspace \
          -scheme Tenant1App \
          -destination 'platform=iOS Simulator,name=iPhone 15,OS=17.0'

  integration-tests:
    needs: [backend-tests, android-tests, ios-tests]
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup Node.js
      uses: actions/setup-node@v3
      with:
        node-version: '18'
        cache: 'npm'
    
    - name: Install dependencies
      run: npm install
    
    - name: Start test server
      run: |
        docker-compose -f docker-compose.test.yml up -d
        sleep 30 # Wait for services to be ready
    
    - name: Run integration tests
      run: npm run test:integration
    
    - name: Run load tests
      run: |
        pip install locust
        locust -f tests/load/locustfile.py --host=http://localhost:8001 \
          --users=10 --spawn-rate=2 --run-time=60s --headless
```

### 2. Test Reporting

```javascript
// tests/reporting/test-reporter.js

const fs = require('fs');
const path = require('path');

class TestReporter {
  constructor() {
    this.results = {
      timestamp: new Date().toISOString(),
      backend: {},
      android: {},
      ios: {},
      integration: {},
      security: {},
      performance: {}
    };
  }

  addBackendResults(results) {
    this.results.backend = {
      total: results.total,
      passed: results.passed,
      failed: results.failed,
      coverage: results.coverage,
      duration: results.duration
    };
  }

  addMobileResults(platform, results) {
    this.results[platform] = {
      unit_tests: results.unitTests,
      ui_tests: results.uiTests,
      security_tests: results.securityTests,
      duration: results.duration
    };
  }

  addSecurityResults(results) {
    this.results.security = {
      vulnerabilities: results.vulnerabilities,
      passed_checks: results.passedChecks,
      failed_checks: results.failedChecks,
      risk_level: results.riskLevel
    };
  }

  addPerformanceResults(results) {
    this.results.performance = {
      avg_response_time: results.avgResponseTime,
      max_response_time: results.maxResponseTime,
      requests_per_second: results.requestsPerSecond,
      error_rate: results.errorRate
    };
  }

  generateReport() {
    const reportPath = path.join(__dirname, '..', 'reports', `test-report-${Date.now()}.json`);
    
    // Ensure reports directory exists
    const reportsDir = path.dirname(reportPath);
    if (!fs.existsSync(reportsDir)) {
      fs.mkdirSync(reportsDir, { recursive: true });
    }

    // Write JSON report
    fs.writeFileSync(reportPath, JSON.stringify(this.results, null, 2));

    // Generate HTML report
    this.generateHTMLReport();

    console.log(`Test report generated: ${reportPath}`);
    return reportPath;
  }

  generateHTMLReport() {
    const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Mobile API Test Report</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f0f0f0; padding: 20px; border-radius: 5px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .passed { color: green; }
        .failed { color: red; }
        .warning { color: orange; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
      </style>
    </head>
    <body>
      <div class="header">
        <h1>Mobile API Test Report</h1>
        <p>Generated: ${this.results.timestamp}</p>
      </div>

      <div class="section">
        <h2>Backend Tests</h2>
        <table>
          <tr><th>Metric</th><th>Value</th></tr>
          <tr><td>Total Tests</td><td>${this.results.backend.total || 'N/A'}</td></tr>
          <tr><td>Passed</td><td class="passed">${this.results.backend.passed || 'N/A'}</td></tr>
          <tr><td>Failed</td><td class="failed">${this.results.backend.failed || 'N/A'}</td></tr>
          <tr><td>Coverage</td><td>${this.results.backend.coverage || 'N/A'}%</td></tr>
          <tr><td>Duration</td><td>${this.results.backend.duration || 'N/A'}s</td></tr>
        </table>
      </div>

      <div class="section">
        <h2>Mobile Tests</h2>
        <h3>Android</h3>
        <table>
          <tr><th>Test Type</th><th>Status</th></tr>
          <tr><td>Unit Tests</td><td class="${this.results.android.unit_tests?.status === 'passed' ? 'passed' : 'failed'}">${this.results.android.unit_tests?.status || 'N/A'}</td></tr>
          <tr><td>UI Tests</td><td class="${this.results.android.ui_tests?.status === 'passed' ? 'passed' : 'failed'}">${this.results.android.ui_tests?.status || 'N/A'}</td></tr>
        </table>

        <h3>iOS</h3>
        <table>
          <tr><th>Test Type</th><th>Status</th></tr>
          <tr><td>Unit Tests</td><td class="${this.results.ios.unit_tests?.status === 'passed' ? 'passed' : 'failed'}">${this.results.ios.unit_tests?.status || 'N/A'}</td></tr>
          <tr><td>UI Tests</td><td class="${this.results.ios.ui_tests?.status === 'passed' ? 'passed' : 'failed'}">${this.results.ios.ui_tests?.status || 'N/A'}</td></tr>
        </table>
      </div>

      <div class="section">
        <h2>Security Tests</h2>
        <table>
          <tr><th>Metric</th><th>Value</th></tr>
          <tr><td>Passed Checks</td><td class="passed">${this.results.security.passed_checks || 'N/A'}</td></tr>
          <tr><td>Failed Checks</td><td class="failed">${this.results.security.failed_checks || 'N/A'}</td></tr>
          <tr><td>Risk Level</td><td class="${this.getRiskClass(this.results.security.risk_level)}">${this.results.security.risk_level || 'N/A'}</td></tr>
        </table>
      </div>

      <div class="section">
        <h2>Performance Tests</h2>
        <table>
          <tr><th>Metric</th><th>Value</th></tr>
          <tr><td>Avg Response Time</td><td>${this.results.performance.avg_response_time || 'N/A'}ms</td></tr>
          <tr><td>Max Response Time</td><td>${this.results.performance.max_response_time || 'N/A'}ms</td></tr>
          <tr><td>Requests/Second</td><td>${this.results.performance.requests_per_second || 'N/A'}</td></tr>
          <tr><td>Error Rate</td><td class="${this.getErrorRateClass(this.results.performance.error_rate)}">${this.results.performance.error_rate || 'N/A'}%</td></tr>
        </table>
      </div>
    </body>
    </html>`;

    const htmlPath = path.join(__dirname, '..', 'reports', `test-report-${Date.now()}.html`);
    fs.writeFileSync(htmlPath, html);
  }

  getRiskClass(riskLevel) {
    switch (riskLevel?.toLowerCase()) {
      case 'low': return 'passed';
      case 'medium': return 'warning';
      case 'high': return 'failed';
      default: return '';
    }
  }

  getErrorRateClass(errorRate) {
    if (errorRate === undefined) return '';
    return errorRate < 1 ? 'passed' : errorRate < 5 ? 'warning' : 'failed';
  }
}

module.exports = TestReporter;
```

---

## 🎯 Best Practices

### 1. Test Organization
- **Pyramid Structure**: More unit tests, fewer integration tests, minimal E2E tests
- **Test Categories**: Security, Performance, Functional, Compatibility
- **Parallel Execution**: Run tests concurrently when possible
- **Environment Isolation**: Separate test data and environments

### 2. Security Testing
- **Regular Penetration Testing**: Monthly automated security scans
- **Dependency Scanning**: Check for vulnerable dependencies
- **Static Analysis**: Code security analysis in CI/CD
- **Dynamic Testing**: Runtime security validation

### 3. Performance Testing
- **Baseline Metrics**: Establish performance baselines
- **Load Testing**: Regular load tests with realistic traffic
- **Monitoring**: Continuous performance monitoring
- **Optimization**: Performance regression detection

### 4. Mobile Testing
- **Real Devices**: Test on actual devices, not just emulators
- **Network Conditions**: Test various network conditions
- **Battery Impact**: Monitor battery usage during tests
- **Accessibility**: Ensure mobile apps are accessible

This comprehensive testing guide ensures that your mobile API system maintains high quality, security, and performance standards throughout development and deployment.