<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The guard name for Spatie permissions
     */
    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'tenants' => $this->tenants->pluck('slug')->toArray(),
        ];
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
                    ->withTimestamps();
    }

    public function hasAccessToTenant($tenantSlug)
    {
        return $this->tenants->contains(function ($tenant) use ($tenantSlug) {
            return $tenant->slug === $tenantSlug;
        });
    }

    /**
     * Get roles for a specific tenant (keeping for backward compatibility)
     */
    public function rolesInTenant($tenantId): BelongsToMany
    {
        return $this->roles()->wherePivot('tenant_id', $tenantId);
    }

    /**
     * Get all roles across all tenants (keeping for backward compatibility)
     */
    public function globalRoles(): BelongsToMany
    {
        return $this->roles()->wherePivotNull('tenant_id');
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin') || $this->is_admin;
    }
}
