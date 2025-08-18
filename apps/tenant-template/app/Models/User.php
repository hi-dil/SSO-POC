<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Secure User Model
 * 
 * Enhanced user model with SSO integration and security features
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'sso_user_id',
        'last_login_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'sso_user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Determine if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Determine if the user was created via SSO
     */
    public function isSSOUser(): bool
    {
        return !empty($this->sso_user_id);
    }

    /**
     * Get the user's display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email;
    }

    /**
     * Get the user's avatar URL (placeholder implementation)
     */
    public function getAvatarUrlAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Scope to get only admin users
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope to get only SSO users
     */
    public function scopeSSOUsers($query)
    {
        return $query->whereNotNull('sso_user_id');
    }

    /**
     * Scope to get users who logged in recently
     */
    public function scopeRecentlyActive($query, int $days = 30)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Update the last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get user permissions (placeholder - implement based on your needs)
     */
    public function getPermissions(): array
    {
        $permissions = [];

        if ($this->isAdmin()) {
            $permissions = [
                'admin.dashboard',
                'admin.users.view',
                'admin.users.create',
                'admin.users.edit',
                'admin.users.delete',
                'admin.audit.view',
                'admin.settings.view',
                'admin.settings.edit',
            ];
        } else {
            $permissions = [
                'dashboard.view',
                'profile.view',
                'profile.edit',
                'settings.view',
            ];
        }

        return $permissions;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    /**
     * Get the tenant slug for this user (from config)
     */
    public function getTenantSlugAttribute(): string
    {
        return config('app.tenant_slug', 'unknown');
    }

    /**
     * Convert the model to an array for API responses
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'is_sso_user' => $this->isSSOUser(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'avatar_url' => $this->avatar_url,
            'tenant_slug' => $this->tenant_slug,
            'permissions' => $this->getPermissions(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}