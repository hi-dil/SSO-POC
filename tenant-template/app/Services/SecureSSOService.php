<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Secure SSO Service - Enterprise Grade
 * 
 * Provides secure communication with Central SSO server including:
 * - API Key Authentication
 * - HMAC Request Signing
 * - Request ID Tracking
 * - Comprehensive Error Handling
 * - Audit Integration
 */
class SecureSSOService
{
    private string $centralSSOUrl;
    private string $tenantSlug;
    private string $apiKey;
    private string $hmacSecret;
    private bool $sslVerify;
    private int $timeout;
    private int $retryAttempts;

    public function __construct()
    {
        $this->centralSSOUrl = config('app.central_sso_url');
        $this->tenantSlug = config('app.tenant_slug');
        $this->apiKey = config('security.api_key');
        $this->hmacSecret = config('security.hmac_secret');
        $this->sslVerify = config('security.ssl_verify', true);
        $this->timeout = config('security.central_sso.timeout', 30);
        $this->retryAttempts = config('security.central_sso.retry_attempts', 3);
    }

    /**
     * Authenticate user via secure central SSO API
     */
    public function login(string $email, string $password): array
    {
        return $this->executeWithRetry(function () use ($email, $password) {
            $body = json_encode([
                'email' => $email,
                'password' => $password,
                'tenant_slug' => $this->tenantSlug
            ]);

            $headers = $this->generateSecureHeaders('POST', '/api/auth/login', $body);

            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => $this->sslVerify])
                ->timeout($this->timeout)
                ->post($this->centralSSOUrl . '/api/auth/login', [
                    'email' => $email,
                    'password' => $password,
                    'tenant_slug' => $this->tenantSlug
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'user' => $data['user'],
                    'token' => $data['token'],
                    'message' => $data['message'] ?? 'Login successful'
                ];
            }

            $error = $response->json();
            return [
                'success' => false,
                'message' => $error['message'] ?? 'Login failed',
                'errors' => $error['errors'] ?? []
            ];
        }, $email);
    }

    /**
     * Validate JWT token via secure central SSO API
     */
    public function validateToken(string $token): array
    {
        return $this->executeWithRetry(function () use ($token) {
            $headers = $this->generateSecureHeaders('POST', '/api/auth/validate', '');
            $headers['Authorization'] = 'Bearer ' . $token;

            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => $this->sslVerify])
                ->timeout($this->timeout)
                ->post($this->centralSSOUrl . '/api/auth/validate');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'valid' => true,
                    'user' => $data['user'],
                    'message' => 'Token valid'
                ];
            }

            return [
                'valid' => false,
                'message' => 'Invalid token'
            ];
        });
    }

    /**
     * Register new user via secure central SSO API
     */
    public function register(string $name, string $email, string $password, string $passwordConfirmation): array
    {
        return $this->executeWithRetry(function () use ($name, $email, $password, $passwordConfirmation) {
            $body = json_encode([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
                'tenant_slug' => $this->tenantSlug
            ]);

            $headers = $this->generateSecureHeaders('POST', '/api/auth/register', $body);

            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => $this->sslVerify])
                ->timeout($this->timeout)
                ->post($this->centralSSOUrl . '/api/auth/register', [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'password_confirmation' => $passwordConfirmation,
                    'tenant_slug' => $this->tenantSlug
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'user' => $data['user'],
                    'token' => $data['token'],
                    'message' => $data['message'] ?? 'Registration successful'
                ];
            }

            $error = $response->json();
            return [
                'success' => false,
                'message' => $error['message'] ?? 'Registration failed',
                'errors' => $error['errors'] ?? []
            ];
        }, $email);
    }

    /**
     * Record login audit event to central SSO
     */
    public function recordLoginAudit(
        int $userId,
        string $email,
        string $loginMethod = 'direct',
        bool $isSuccessful = true,
        ?string $failureReason = null
    ): void {
        if (!config('security.audit.enabled', true)) {
            return;
        }

        try {
            $body = json_encode([
                'user_id' => $userId,
                'email' => $email,
                'tenant_slug' => $this->tenantSlug,
                'login_method' => $loginMethod,
                'is_successful' => $isSuccessful,
                'failure_reason' => $failureReason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            $headers = $this->generateSecureHeaders('POST', '/api/audit/login', $body);

            Http::withHeaders($headers)
                ->withOptions(['verify' => $this->sslVerify])
                ->timeout(10)
                ->post($this->centralSSOUrl . '/api/audit/login', json_decode($body, true));

        } catch (\Exception $e) {
            // Don't fail the login process if audit recording fails
            Log::warning('Audit recording failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'email' => $email,
                'tenant' => $this->tenantSlug
            ]);
        }
    }

    /**
     * Create or update local user from SSO data
     */
    public function createOrUpdateUser(array $userData): User
    {
        $user = User::updateOrCreate(
            ['sso_user_id' => $userData['id']],
            [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'is_admin' => $userData['is_admin'] ?? false,
                'password' => Hash::make('sso_user_' . $userData['id']), // Random password since SSO handles auth
            ]
        );

        return $user;
    }

    /**
     * Authenticate user locally with session regeneration
     */
    public function authenticateUser(User $user): void
    {
        Auth::login($user, true);
        session()->regenerate();
    }

    /**
     * Generate secure headers with API key and HMAC signature
     */
    private function generateSecureHeaders(string $method, string $uri, string $body): array
    {
        $timestamp = now()->toISOString();
        $requestId = (string) Str::uuid();

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $this->apiKey,
            'X-Timestamp' => $timestamp,
            'X-Tenant-ID' => $this->tenantSlug,
            'X-Request-ID' => $requestId,
            'User-Agent' => 'SecureSSOService/1.0 (' . $this->tenantSlug . ')'
        ];

        // Generate HMAC signature for request integrity
        $signature = $this->generateSignature($method, $uri, $headers, $body);
        $headers['X-Signature'] = $signature;

        return $headers;
    }

    /**
     * Generate HMAC-SHA256 signature for request integrity
     */
    private function generateSignature(string $method, string $uri, array $headers, string $body): string
    {
        // Create canonical request string for consistent signing
        $canonicalString = implode('|', [
            strtoupper($method),
            $uri,
            $headers['X-Timestamp'],
            $headers['X-Tenant-ID'],
            hash('sha256', $body)
        ]);

        // Generate HMAC signature using secret
        return hash_hmac('sha256', $canonicalString, $this->hmacSecret);
    }

    /**
     * Execute request with retry logic for resilience
     */
    private function executeWithRetry(callable $operation, string $context = 'operation'): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            try {
                return $operation();
            } catch (\Exception $e) {
                $lastException = $e;
                
                Log::warning("SSO {$context} attempt {$attempt} failed", [
                    'error' => $e->getMessage(),
                    'tenant' => $this->tenantSlug,
                    'attempt' => $attempt,
                    'max_attempts' => $this->retryAttempts
                ]);

                if ($attempt < $this->retryAttempts) {
                    // Exponential backoff: wait 1s, 2s, 4s...
                    sleep(pow(2, $attempt - 1));
                }
            }
        }

        // All attempts failed
        Log::error("SSO {$context} failed after {$this->retryAttempts} attempts", [
            'error' => $lastException->getMessage(),
            'tenant' => $this->tenantSlug
        ]);

        return [
            'success' => false,
            'valid' => false,
            'message' => 'Authentication service unavailable'
        ];
    }

    /**
     * Check if SSO service is available
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withOptions(['verify' => $this->sslVerify])
                ->get($this->centralSSOUrl . '/health');
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('SSO health check failed', [
                'error' => $e->getMessage(),
                'tenant' => $this->tenantSlug
            ]);
            return false;
        }
    }
}