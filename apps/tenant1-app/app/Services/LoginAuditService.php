<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LoginAuditService
{
    private SecureSSOService $ssoService;
    private string $tenantSlug;

    public function __construct(SecureSSOService $ssoService)
    {
        $this->ssoService = $ssoService;
        $this->tenantSlug = env('TENANT_SLUG', 'tenant1');
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
        $this->ssoService->recordLoginAudit(
            $userId,
            $email,
            $loginMethod,
            $isSuccessful,
            $failureReason
        );
    }

    /**
     * Record a logout event to the central SSO audit system
     */
    public function recordLogout(?string $sessionId = null): void
    {
        // Note: SecureSSOService doesn't have recordLogoutAudit method yet
        // For now, just log locally
        Log::info('User logged out', [
            'session_id' => $sessionId ?: session()->getId(),
            'tenant' => $this->tenantSlug
        ]);
    }
}