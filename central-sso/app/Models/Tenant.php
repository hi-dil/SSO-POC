<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'domain',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function getSlugAttribute()
    {
        return $this->data['slug'] ?? $this->id;
    }

    public function getNameAttribute()
    {
        return $this->data['name'] ?? $this->id;
    }

    public function getDomainAttribute()
    {
        return $this->data['domain'] ?? null;
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_users')
                    ->withTimestamps();
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (!$tenant->id) {
                $tenant->id = \Illuminate\Support\Str::uuid();
            }
        });
    }
}