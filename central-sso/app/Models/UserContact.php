<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserContact extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'label',
        'value',
        'is_primary',
        'is_public',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope to get primary contacts
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // Scope to get public contacts
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // Scope to get contacts by type
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Available contact types
    public static function getContactTypes(): array
    {
        return [
            'email' => 'Email',
            'phone' => 'Phone',
            'mobile' => 'Mobile',
            'work_phone' => 'Work Phone',
            'home_phone' => 'Home Phone',
            'fax' => 'Fax',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'skype' => 'Skype',
            'other' => 'Other',
        ];
    }
}
