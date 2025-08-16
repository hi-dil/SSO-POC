<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ActiveSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'session_id',
        'login_method',
        'ip_address',
        'user_agent',
        'last_activity',
        'expires_at',
        'activity_data',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'activity_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Create or update an active session
     */
    public static function createOrUpdate(
        int $userId,
        ?string $tenantId = null,
        string $loginMethod = 'direct',
        ?string $sessionId = null,
        ?array $activityData = null
    ): self {
        $sessionId = $sessionId ?: session()->getId();
        $now = now();
        $expiresAt = $now->copy()->addMinutes(config('session.lifetime', 120));

        return self::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'login_method' => $loginMethod,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'last_activity' => $now,
                'expires_at' => $expiresAt,
                'activity_data' => $activityData ?: [],
            ]
        );
    }

    /**
     * Update activity timestamp
     */
    public function updateActivity(?array $activityData = null): void
    {
        $this->update([
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
            'activity_data' => $activityData ?: $this->activity_data,
        ]);
    }

    /**
     * Remove session (logout)
     */
    public static function removeSession(string $sessionId): void
    {
        self::where('session_id', $sessionId)->delete();
    }

    /**
     * Clean up expired sessions
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now())
            ->orWhere('last_activity', '<', now()->subHours(24))
            ->delete();
    }

    /**
     * Get currently active sessions
     */
    public static function getActiveSessions()
    {
        return self::with(['user', 'tenant'])
            ->where('expires_at', '>', now())
            ->where('last_activity', '>', now()->subMinutes(30)) // Active in last 30 minutes
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Get active sessions count
     */
    public static function getActiveCount(): int
    {
        return self::where('expires_at', '>', now())
            ->where('last_activity', '>', now()->subMinutes(30))
            ->count();
    }

    /**
     * Get active sessions by tenant
     */
    public static function getActiveByTenant(): array
    {
        return self::with('tenant')
            ->where('expires_at', '>', now())
            ->where('last_activity', '>', now()->subMinutes(30))
            ->whereNotNull('tenant_id')
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, count(*) as count')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tenant_id => [
                    'count' => $item->count,
                    'tenant' => $item->tenant,
                ]];
            })
            ->toArray();
    }

    /**
     * Get user's active sessions
     */
    public static function getUserSessions(int $userId)
    {
        return self::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Check if user has active session
     */
    public static function userHasActiveSession(int $userId, ?string $tenantId = null): bool
    {
        $query = self::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->where('last_activity', '>', now()->subMinutes(30));

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->exists();
    }

    /**
     * Get session statistics
     */
    public static function getSessionStatistics(): array
    {
        $activeCount = self::getActiveCount();
        $byTenant = self::getActiveByTenant();
        
        $totalSessions = self::where('expires_at', '>', now())->count();
        
        $byMethod = self::where('expires_at', '>', now())
            ->where('last_activity', '>', now()->subMinutes(30))
            ->groupBy('login_method')
            ->selectRaw('login_method, count(*) as count')
            ->pluck('count', 'login_method')
            ->toArray();

        return [
            'active_users' => $activeCount,
            'total_sessions' => $totalSessions,
            'by_tenant' => $byTenant,
            'by_method' => $byMethod,
        ];
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())
            ->where('last_activity', '>', now()->subMinutes(30));
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->expires_at > now() && 
               $this->last_activity > now()->subMinutes(30);
    }

    /**
     * Get session duration in minutes
     */
    public function getDurationAttribute(): int
    {
        return $this->created_at->diffInMinutes($this->last_activity);
    }
}