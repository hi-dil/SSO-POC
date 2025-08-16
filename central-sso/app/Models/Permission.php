<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'guard_name',
        'category',
        'is_system',
        'meta',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * A permission can belong to multiple roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
    }

    /**
     * Get all users that have this permission through roles
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToManyThrough(
            User::class,
            Role::class,
            'permission_role',
            'role_user',
            'permission_id',
            'role_id',
            'id',
            'id'
        );
    }

    /**
     * Scope to get permissions by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get only system permissions
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get only custom permissions
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

        static::creating(function ($permission) {
            if (empty($permission->slug)) {
                $permission->slug = \Str::slug($permission->name);
            }
        });

        static::deleting(function ($permission) {
            if ($permission->is_system) {
                throw new \Exception('System permissions cannot be deleted');
            }
        });
    }
}
