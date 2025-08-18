<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocialMedia extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'username',
        'url',
        'display_name',
        'is_public',
        'order',
        'notes',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope to get public social media
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // Scope to get social media by platform
    public function scopeOfPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    // Scope to order by display order
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('platform');
    }

    // Get platform icon class
    public function getPlatformIconAttribute(): string
    {
        $icons = [
            'facebook' => 'fab fa-facebook',
            'twitter' => 'fab fa-twitter',
            'x' => 'fab fa-x-twitter',
            'linkedin' => 'fab fa-linkedin',
            'instagram' => 'fab fa-instagram',
            'github' => 'fab fa-github',
            'youtube' => 'fab fa-youtube',
            'tiktok' => 'fab fa-tiktok',
            'snapchat' => 'fab fa-snapchat',
            'discord' => 'fab fa-discord',
            'telegram' => 'fab fa-telegram',
            'whatsapp' => 'fab fa-whatsapp',
            'pinterest' => 'fab fa-pinterest',
            'reddit' => 'fab fa-reddit',
            'medium' => 'fab fa-medium',
            'behance' => 'fab fa-behance',
            'dribbble' => 'fab fa-dribbble',
            'website' => 'fas fa-globe',
            'blog' => 'fas fa-blog',
            'portfolio' => 'fas fa-briefcase',
        ];

        return $icons[$this->platform] ?? 'fas fa-link';
    }

    // Get platform color
    public function getPlatformColorAttribute(): string
    {
        $colors = [
            'facebook' => '#1877F2',
            'twitter' => '#1DA1F2',
            'x' => '#000000',
            'linkedin' => '#0A66C2',
            'instagram' => '#E4405F',
            'github' => '#181717',
            'youtube' => '#FF0000',
            'tiktok' => '#000000',
            'snapchat' => '#FFFC00',
            'discord' => '#5865F2',
            'telegram' => '#26A5E4',
            'whatsapp' => '#25D366',
            'pinterest' => '#BD081C',
            'reddit' => '#FF4500',
            'medium' => '#000000',
            'behance' => '#1769FF',
            'dribbble' => '#EA4C89',
            'website' => '#6B7280',
            'blog' => '#6B7280',
            'portfolio' => '#6B7280',
        ];

        return $colors[$this->platform] ?? '#6B7280';
    }

    // Available social media platforms
    public static function getSocialPlatforms(): array
    {
        return [
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'x' => 'X (Twitter)',
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'github' => 'GitHub',
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
            'snapchat' => 'Snapchat',
            'discord' => 'Discord',
            'telegram' => 'Telegram',
            'whatsapp' => 'WhatsApp',
            'pinterest' => 'Pinterest',
            'reddit' => 'Reddit',
            'medium' => 'Medium',
            'behance' => 'Behance',
            'dribbble' => 'Dribbble',
            'website' => 'Website',
            'blog' => 'Blog',
            'portfolio' => 'Portfolio',
            'other' => 'Other',
        ];
    }
}
