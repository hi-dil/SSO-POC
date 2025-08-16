<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Login Audit Service
 * 
 * Handles comprehensive audit logging for authentication events
 * Integrates with central SSO audit system for enterprise compliance
 */
class LoginAuditService
{
    private SecureSSOService $ssoService;
    private string $tenantSlug;
    private bool $auditEnabled;
    private bool $logFailedAttempts;
    private bool $logSuccessfulAttempts;

    public function __construct(SecureSSOService $ssoService)
    {
        $this->ssoService = $ssoService;
        $this->tenantSlug = config('app.tenant_slug');
        $this->auditEnabled = config('security.audit.enabled', true);
        $this->logFailedAttempts = config('security.audit.log_failed_attempts', true);
        $this->logSuccessfulAttempts = config('security.audit.log_successful_attempts', true);
    }

    /**
     * Record a login event to the central SSO audit system
     */
    public function recordLogin(
        int $userId,
        string $email,
        string $loginMethod = 'direct',
        bool $isSuccessful = true,
        ?string $failureReason = null
    ): void {
        // Check if we should log this type of event
        if (!$this->shouldLog($isSuccessful)) {
            return;
        }

        // Record to central SSO audit system
        if ($this->auditEnabled) {
            $this->ssoService->recordLoginAudit(
                $userId,
                $email,
                $loginMethod,
                $isSuccessful,
                $failureReason
            );
        }

        // Also log locally for immediate access
        $this->logLocally([
            'event' => 'login_attempt',
            'user_id' => $userId,
            'email' => $email,
            'tenant' => $this->tenantSlug,
            'login_method' => $loginMethod,
            'is_successful' => $isSuccessful,
            'failure_reason' => $failureReason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Record a logout event
     */
    public function recordLogout(?string $sessionId = null): void
    {
        $logData = [
            'event' => 'logout',
            'user_id' => auth()->id(),
            'email' => auth()->user()?->email,
            'tenant' => $this->tenantSlug,
            'session_id' => $sessionId ?: session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        $this->logLocally($logData);
    }

    /**
     * Record SSO processing events
     */
    public function recordSSOProcessing(string $event, array $data = []): void
    {
        $logData = array_merge([
            'event' => "sso_{$event}",
            'tenant' => $this->tenantSlug,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
        ], $data);

        $this->logLocally($logData);
    }

    /**
     * Record security events (rate limiting, invalid tokens, etc.)
     */
    public function recordSecurityEvent(string $eventType, array $data = []): void
    {
        $logData = array_merge([
            'event' => "security_{$eventType}",
            'tenant' => $this->tenantSlug,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'severity' => 'warning',
        ], $data);

        Log::warning("Security event: {$eventType}", $logData);
    }

    /**
     * Get audit statistics for monitoring
     */
    public function getAuditStats(int $hours = 24): array
    {
        // This would typically query a local audit table
        // For the template, we'll return mock data
        return [
            'total_logins' => 0,
            'successful_logins' => 0,
            'failed_logins' => 0,
            'unique_users' => 0,
            'unique_ips' => 0,
            'time_period' => "{$hours} hours",
            'tenant' => $this->tenantSlug,
        ];
    }

    /**
     * Determine if we should log this event type
     */
    private function shouldLog(bool $isSuccessful): bool
    {
        if (!$this->auditEnabled) {
            return false;
        }

        if ($isSuccessful && !$this->logSuccessfulAttempts) {
            return false;
        }

        if (!$isSuccessful && !$this->logFailedAttempts) {
            return false;
        }

        return true;
    }

    /**
     * Log event locally for immediate access and debugging
     */
    private function logLocally(array $logData): void
    {
        $level = $logData['is_successful'] ?? true ? 'info' : 'warning';
        $message = $this->formatLogMessage($logData);

        Log::log($level, $message, $logData);
    }

    /**
     * Format log message for readability
     */
    private function formatLogMessage(array $logData): string
    {
        $event = $logData['event'] ?? 'unknown';
        $email = $logData['email'] ?? 'unknown';
        $success = $logData['is_successful'] ?? null;

        switch ($event) {
            case 'login_attempt':
                if ($success === true) {
                    return "Successful login: {$email}";
                } elseif ($success === false) {
                    $reason = $logData['failure_reason'] ?? 'unknown';
                    return "Failed login: {$email} - {$reason}";
                }
                return "Login attempt: {$email}";

            case 'logout':
                return "User logout: {$email}";

            default:
                return ucfirst(str_replace('_', ' ', $event));
        }
    }
}