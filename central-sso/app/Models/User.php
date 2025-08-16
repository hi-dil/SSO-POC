<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
     * A user can have multiple roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
                    ->where('model_type', User::class)
                    ->withPivot('tenant_id');
    }

    /**
     * Get roles for a specific tenant
     */
    public function rolesInTenant($tenantId): BelongsToMany
    {
        return $this->roles()->wherePivot('tenant_id', $tenantId);
    }

    /**
     * Get all roles across all tenants
     */
    public function globalRoles(): BelongsToMany
    {
        return $this->roles()->wherePivotNull('tenant_id');
    }

    /**
     * Check if user has specific role (or any of the roles if array is provided)
     */
    public function hasRole(string|array $role, $tenantId = null): bool
    {
        // If array is provided, check if user has any of the roles
        if (is_array($role)) {
            return $this->hasAnyRole($role, $tenantId);
        }
        
        $query = $this->roles()->where('slug', $role);
        
        if ($tenantId !== null) {
            $query->wherePivot('tenant_id', $tenantId);
        }
        
        return $query->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles, $tenantId = null): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role, $tenantId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roles, $tenantId = null): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role, $tenantId)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission, $tenantId = null): bool
    {
        $query = $this->roles();
        
        if ($tenantId !== null) {
            $query->wherePivot('tenant_id', $tenantId);
        }
        
        return $query->whereHas('permissions', function ($q) use ($permission) {
            $q->where('slug', $permission);
        })->exists();
    }

    /**
     * Assign role to user
     */
    public function assignRole(Role|string $role, $tenantId = null): self
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        if (!$this->hasRole($role->slug, $tenantId)) {
            // Insert into model_has_roles with proper model_type
            \DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => get_class($this),
                'model_id' => $this->id,
                'tenant_id' => $tenantId
            ]);
        }

        return $this;
    }

    /**
     * Remove role from user
     */
    public function removeRole(Role|string $role, $tenantId = null): self
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        // Delete from model_has_roles with proper conditions
        \DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_type', get_class($this))
            ->where('model_id', $this->id)
            ->where('tenant_id', $tenantId)
            ->delete();

        return $this;
    }

    /**
     * Sync roles for user in specific tenant
     */
    public function syncRoles(array $roles, $tenantId = null): self
    {
        // First remove all existing roles for this tenant
        $this->roles()->wherePivot('tenant_id', $tenantId)->detach();

        // Then assign new roles
        foreach ($roles as $role) {
            $this->assignRole($role, $tenantId);
        }

        return $this;
    }

    /**
     * Get all permissions for user through roles
     */
    public function getAllPermissions($tenantId = null)
    {
        $roles = $tenantId !== null 
            ? $this->rolesInTenant($tenantId) 
            : $this->roles;

        return $roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id');
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin') || $this->is_admin;
    }
}
