<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
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
