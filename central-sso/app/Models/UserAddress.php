<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'label',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'is_primary',
        'is_public',
        'notes',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_public' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope to get primary address
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // Scope to get public addresses
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // Scope to get addresses by type
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Get formatted address as string
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state_province,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    // Available address types
    public static function getAddressTypes(): array
    {
        return [
            'home' => 'Home',
            'work' => 'Work',
            'billing' => 'Billing',
            'shipping' => 'Shipping',
            'mailing' => 'Mailing',
            'emergency' => 'Emergency',
            'other' => 'Other',
        ];
    }
}
