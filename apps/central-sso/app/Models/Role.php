<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'guard_name',
        'is_system',
        'meta',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * Get users for this role in a specific tenant (keeping for backward compatibility)
     */
    public function usersInTenant($tenantId): BelongsToMany
    {
        return $this->users()->wherePivot('tenant_id', $tenantId);
    }

    /**
     * Scope to get only system roles
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get only custom roles
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($role) {
            if (empty($role->slug)) {
                $role->slug = \Str::slug($role->name);
            }
        });

        static::deleting(function ($role) {
            if ($role->is_system) {
                throw new \Exception('System roles cannot be deleted');
            }
        });
    }
}
