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
        'is_active',
        'max_users',
        'description',
        'logo_url',
        'data',
        'settings',
        // Custom SSO fields
        'plan',
        'industry',
        'region',
        'employee_count',
        'created_year',
        'features',
        'billing_status',
        'billing_cycle',
        'trial_ends_at',
    ];

    protected $casts = [
        'data' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'features' => 'array',
        'employee_count' => 'integer',
        'created_year' => 'integer',
        'trial_ends_at' => 'date',
    ];

    // User count relationship helper
    public function getUserCountAttribute()
    {
        return $this->users()->count();
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