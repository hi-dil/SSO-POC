<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFamilyMember extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'relationship',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'occupation',
        'notes',
        'is_emergency_contact',
        'is_dependent',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_emergency_contact' => 'boolean',
        'is_dependent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public static function getRelationshipOptions(): array
    {
        return [
            'spouse' => 'Spouse',
            'child' => 'Child',
            'parent' => 'Parent',
            'sibling' => 'Sibling',
            'grandparent' => 'Grandparent',
            'grandchild' => 'Grandchild',
            'uncle_aunt' => 'Uncle/Aunt',
            'nephew_niece' => 'Nephew/Niece',
            'cousin' => 'Cousin',
            'other' => 'Other',
        ];
    }
}
