<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

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
        'phone',
        'date_of_birth',
        'gender',
        'nationality',
        'bio',
        'avatar_url',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'job_title',
        'department',
        'employee_id',
        'hire_date',
        'timezone',
        'language',
        'notification_preferences',
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
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'notification_preferences' => 'array',
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
        return $this->hasRole('Super Admin') || $this->is_admin;
    }

    /**
     * Get family members
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(UserFamilyMember::class);
    }

    /**
     * Get user contacts
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(UserContact::class);
    }

    /**
     * Get user addresses
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Get user social media links
     */
    public function socialMedia(): HasMany
    {
        return $this->hasMany(UserSocialMedia::class)->ordered();
    }

    /**
     * Get primary contact of a specific type
     */
    public function primaryContact($type)
    {
        return $this->contacts()->ofType($type)->primary()->first();
    }

    /**
     * Get primary address
     */
    public function primaryAddress()
    {
        return $this->addresses()->primary()->first();
    }

    /**
     * Get public social media links
     */
    public function publicSocialMedia(): HasMany
    {
        return $this->hasMany(UserSocialMedia::class)->public()->ordered();
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state_province,
            $this->postal_code,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get avatar URL or generate initials
     */
    public function getAvatarAttribute(): string
    {
        if ($this->avatar_url) {
            return $this->avatar_url;
        }
        
        // Generate initials-based avatar
        $initials = strtoupper(substr($this->name, 0, 2));
        return "https://ui-avatars.com/api/?name=" . urlencode($this->name) . "&background=6366f1&color=fff&size=200";
    }

    /**
     * Get age from date of birth
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Configure activity logging
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_admin', 'phone', 'job_title', 'department'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('users')
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => "User {$this->name} created",
                'updated' => "User {$this->name} updated",
                'deleted' => "User {$this->name} deleted",
                default => "User {$this->name} {$eventName}",
            });
    }
}
