<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'validation_rules',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get a setting value by key with caching
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value and clear cache
     */
    public static function set(string $key, $value): bool
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return false;
        }

        $setting->value = $value;
        $result = $setting->save();

        // Clear cache
        Cache::forget("setting.{$key}");
        Cache::forget('settings.all');

        return $result;
    }

    /**
     * Get all settings grouped by group
     */
    public static function getAllGrouped(): array
    {
        return Cache::remember('settings.all', 3600, function () {
            $settings = static::orderBy('group')->orderBy('sort_order')->get();
            
            $grouped = [];
            foreach ($settings as $setting) {
                $grouped[$setting->group][] = [
                    'key' => $setting->key,
                    'value' => static::castValue($setting->value, $setting->type),
                    'raw_value' => $setting->value,
                    'type' => $setting->type,
                    'label' => $setting->label,
                    'description' => $setting->description,
                    'validation_rules' => $setting->validation_rules,
                    'is_public' => $setting->is_public,
                ];
            }
            
            return $grouped;
        });
    }

    /**
     * Cast value to appropriate type
     */
    protected static function castValue($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            'array' => is_array($value) ? $value : json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("setting.{$setting->key}");
        }
        Cache::forget('settings.all');
    }

    /**
     * Get JWT access token TTL in minutes
     */
    public static function getJwtAccessTokenTtl(): int
    {
        return static::get('jwt.access_token_ttl', 60);
    }

    /**
     * Get JWT refresh token TTL in minutes  
     */
    public static function getJwtRefreshTokenTtl(): int
    {
        return static::get('jwt.refresh_token_ttl', 20160);
    }

    /**
     * Get session lifetime in minutes
     */
    public static function getSessionLifetime(): int
    {
        return static::get('session.lifetime', 120);
    }

    /**
     * Boot method to clear cache when model is updated
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($model) {
            Cache::forget("setting.{$model->key}");
            Cache::forget('settings.all');
        });

        static::deleted(function ($model) {
            Cache::forget("setting.{$model->key}");
            Cache::forget('settings.all');
        });
    }
}
