<?php

namespace App\Services;

use App\Models\LoginAudit;
use App\Models\ActiveSession;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LoginAuditService
{
    /**
     * Record a successful login
     */
    public function recordLogin(
        User $user,
        ?string $tenantId = null,
        string $loginMethod = 'direct',
        ?string $sessionId = null
    ): LoginAudit {
        // For API login, we'll generate a session ID if not provided
        if ($loginMethod === 'api' && !$sessionId) {
            $sessionId = 'api_' . uniqid() . '_' . $user->id;
        }

        // Create login audit record
        $audit = LoginAudit::createLoginRecord(
            $user->id,
            $tenantId,
            $loginMethod,
            $sessionId,
            true
        );

        // Create or update active session
        ActiveSession::createOrUpdate(
            $user->id,
            $tenantId,
            $loginMethod,
            $sessionId,
            [
                'login_audit_id' => $audit->id,
                'login_time' => now()->toISOString(),
            ]
        );

        return $audit;
    }

    /**
     * Record a failed login attempt
     */
    public function recordFailedLogin(
        ?string $email = null,
        ?string $tenantId = null,
        string $loginMethod = 'direct',
        string $failureReason = 'Invalid credentials'
    ): LoginAudit {
        $userId = null;
        
        // Try to find user by email for tracking purposes
        if ($email) {
            $user = User::where('email', $email)->first();
            $userId = $user ? $user->id : null;
        }

        return LoginAudit::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'login_method' => $loginMethod,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'login_at' => now(),
            'is_successful' => false,
            'failure_reason' => $failureReason,
        ]);
    }

    /**
     * Record logout
     */
    public function recordLogout(?string $sessionId = null): void
    {
        $sessionId = $sessionId ?: session()->getId();

        // Find and update login audit record
        $audit = LoginAudit::where('session_id', $sessionId)
            ->whereNull('logout_at')
            ->orderBy('login_at', 'desc')
            ->first();

        if ($audit) {
            $audit->markLogout();
        }

        // Remove active session
        ActiveSession::removeSession($sessionId);
    }

    /**
     * Update user activity
     */
    public function updateActivity(
        ?string $sessionId = null,
        ?string $tenantId = null,
        ?array $activityData = null
    ): void {
        $sessionId = $sessionId ?: session()->getId();

        $session = ActiveSession::where('session_id', $sessionId)->first();
        if ($session) {
            // Update tenant context if provided
            if ($tenantId && $session->tenant_id !== $tenantId) {
                $session->tenant_id = $tenantId;
            }

            $session->updateActivity($activityData);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStatistics(): array
    {
        // Clean up expired sessions first
        ActiveSession::cleanupExpired();

        $activeStats = ActiveSession::getSessionStatistics();
        $loginStats = LoginAudit::getStatistics(
            now()->subDays(30), // Last 30 days
            now()
        );

        // Get recent activity
        $recentLogins = LoginAudit::getRecentActivity(10);
        $activeSessions = ActiveSession::getActiveSessions()->take(10);

        // Calculate trends
        $todayLogins = LoginAudit::where('login_at', '>=', now()->startOfDay())
            ->where('is_successful', true)
            ->count();

        $yesterdayLogins = LoginAudit::where('login_at', '>=', now()->subDay()->startOfDay())
            ->where('login_at', '<', now()->startOfDay())
            ->where('is_successful', true)
            ->count();

        return [
            'active_users' => $activeStats['active_users'],
            'total_sessions' => $activeStats['total_sessions'],
            'today_logins' => $todayLogins,
            'total_logins_30_days' => $loginStats['total_logins'],
            'unique_users_30_days' => $loginStats['unique_users'],
            'login_trend' => $todayLogins - $yesterdayLogins,
            'active_by_tenant' => $activeStats['by_tenant'],
            'active_by_method' => $activeStats['by_method'],
            'logins_by_tenant' => $loginStats['by_tenant'],
            'logins_by_method' => $loginStats['by_method'],
            'recent_logins' => $recentLogins,
            'active_sessions' => $activeSessions,
        ];
    }

    /**
     * Get user activity summary
     */
    public function getUserActivity(int $userId): array
    {
        $user = User::findOrFail($userId);
        
        $loginCount = LoginAudit::where('user_id', $userId)
            ->where('is_successful', true)
            ->count();

        $lastLogin = LoginAudit::where('user_id', $userId)
            ->where('is_successful', true)
            ->orderBy('login_at', 'desc')
            ->first();

        $activeSessions = ActiveSession::getUserSessions($userId);
        
        $recentLogins = LoginAudit::where('user_id', $userId)
            ->where('is_successful', true)
            ->with('tenant')
            ->orderBy('login_at', 'desc')
            ->take(10)
            ->get();

        return [
            'user' => $user,
            'total_logins' => $loginCount,
            'last_login' => $lastLogin,
            'active_sessions' => $activeSessions,
            'recent_logins' => $recentLogins,
            'is_currently_active' => ActiveSession::userHasActiveSession($userId),
        ];
    }

    /**
     * Get tenant activity summary
     */
    public function getTenantActivity(string $tenantId): array
    {
        $loginCount = LoginAudit::where('tenant_id', $tenantId)
            ->where('is_successful', true)
            ->count();

        $activeUsers = ActiveSession::where('tenant_id', $tenantId)
            ->active()
            ->count();

        $recentLogins = LoginAudit::where('tenant_id', $tenantId)
            ->where('is_successful', true)
            ->with('user')
            ->orderBy('login_at', 'desc')
            ->take(10)
            ->get();

        $topUsers = LoginAudit::where('tenant_id', $tenantId)
            ->where('is_successful', true)
            ->where('login_at', '>=', now()->subDays(30))
            ->with('user')
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as login_count')
            ->orderBy('login_count', 'desc')
            ->take(5)
            ->get();

        return [
            'tenant_id' => $tenantId,
            'total_logins' => $loginCount,
            'active_users' => $activeUsers,
            'recent_logins' => $recentLogins,
            'top_users' => $topUsers,
        ];
    }

    /**
     * Clean up old audit records
     */
    public function cleanup(int $daysToKeep = 90): array
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        $auditDeleted = LoginAudit::where('login_at', '<', $cutoffDate)->delete();
        $sessionsDeleted = ActiveSession::cleanupExpired();

        return [
            'audit_records_deleted' => $auditDeleted,
            'expired_sessions_deleted' => $sessionsDeleted,
        ];
    }
}