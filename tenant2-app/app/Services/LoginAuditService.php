<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoginAuditService
{
    private string $centralSSOUrl;
    private string $tenantSlug;

    public function __construct()
    {
        $this->centralSSOUrl = env('CENTRAL_SSO_URL', 'http://central-sso:8000');
        $this->tenantSlug = env('TENANT_SLUG', 'tenant2');
    }

    /**
     * Record a login event to the central SSO audit system
     */
    public function recordLogin(
        int $userId,
        string $email,
        string $loginMethod = 'sso',
        bool $isSuccessful = true,
        ?string $failureReason = null
    ): void {
        try {
            $response = Http::timeout(5)->post($this->centralSSOUrl . '/api/audit/login', [
                'user_id' => $userId,
                'email' => $email,
                'tenant_id' => $this->tenantSlug,
                'login_method' => $loginMethod,
                'is_successful' => $isSuccessful,
                'failure_reason' => $failureReason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => session()->getId(),
            ]);

            if (!$response->successful()) {
                Log::warning('Failed to record login audit to central SSO', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'user_id' => $userId,
                    'tenant' => $this->tenantSlug
                ]);
            } else {
                Log::info('Login audit recorded to central SSO', [
                    'user_id' => $userId,
                    'tenant' => $this->tenantSlug,
                    'method' => $loginMethod
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error recording login audit to central SSO', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant' => $this->tenantSlug
            ]);
        }
    }

    /**
     * Record a logout event to the central SSO audit system
     */
    public function recordLogout(?string $sessionId = null): void
    {
        try {
            $sessionId = $sessionId ?: session()->getId();
            
            $response = Http::timeout(5)->post($this->centralSSOUrl . '/api/audit/logout', [
                'session_id' => $sessionId,
                'tenant_id' => $this->tenantSlug,
            ]);

            if (!$response->successful()) {
                Log::warning('Failed to record logout audit to central SSO', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'session_id' => $sessionId,
                    'tenant' => $this->tenantSlug
                ]);
            } else {
                Log::info('Logout audit recorded to central SSO', [
                    'session_id' => $sessionId,
                    'tenant' => $this->tenantSlug
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error recording logout audit to central SSO', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'tenant' => $this->tenantSlug
            ]);
        }
    }
}