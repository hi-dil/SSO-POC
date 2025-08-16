<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'login_method',
        'ip_address',
        'user_agent',
        'session_id',
        'login_at',
        'logout_at',
        'session_duration',
        'is_successful',
        'failure_reason',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'is_successful' => 'boolean',
        'session_duration' => 'integer',
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
     * Create a new login audit record
     */
    public static function createLoginRecord(
        int $userId,
        ?string $tenantId = null,
        string $loginMethod = 'direct',
        ?string $sessionId = null,
        bool $isSuccessful = true,
        ?string $failureReason = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'login_method' => $loginMethod,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => $sessionId ?: session()->getId(),
            'login_at' => now(),
            'is_successful' => $isSuccessful,
            'failure_reason' => $failureReason,
        ]);
    }

    /**
     * Mark logout and calculate session duration
     */
    public function markLogout(): void
    {
        $logoutTime = now();
        $duration = $this->login_at->diffInSeconds($logoutTime);
        
        $this->update([
            'logout_at' => $logoutTime,
            'session_duration' => $duration,
        ]);
    }

    /**
     * Get recent login activities
     */
    public static function getRecentActivity(int $limit = 50)
    {
        return self::with(['user', 'tenant'])
            ->where('is_successful', true)
            ->orderBy('login_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get login statistics for a date range
     */
    public static function getStatistics(
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null
    ): array {
        $query = self::where('is_successful', true);
        
        if ($startDate) {
            $query->where('login_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('login_at', '<=', $endDate);
        }

        $totalLogins = $query->count();
        $uniqueUsers = $query->distinct('user_id')->count();
        
        $byMethod = $query->groupBy('login_method')
            ->selectRaw('login_method, count(*) as count')
            ->pluck('count', 'login_method')
            ->toArray();

        $byTenant = $query->whereNotNull('tenant_id')
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, count(*) as count')
            ->with('tenant:id,name,slug')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->tenant_id => [
                    'count' => $item->count,
                    'tenant' => $item->tenant,
                ]];
            })
            ->toArray();

        return [
            'total_logins' => $totalLogins,
            'unique_users' => $uniqueUsers,
            'by_method' => $byMethod,
            'by_tenant' => $byTenant,
        ];
    }

    /**
     * Get failed login attempts
     */
    public static function getFailedAttempts(int $limit = 50)
    {
        return self::with(['user'])
            ->where('is_successful', false)
            ->orderBy('login_at', 'desc')
            ->limit($limit)
            ->get();
    }
}