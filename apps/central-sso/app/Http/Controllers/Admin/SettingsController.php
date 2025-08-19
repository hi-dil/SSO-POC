<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->middleware('can:system.settings');
        $this->auditService = $auditService;
    }

    /**
     * Display the settings management page
     */
    public function index()
    {
        $settings = Setting::getAllGrouped();
        
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $settings = $request->input('settings', []);
        $errors = [];
        $updated = [];

        foreach ($settings as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            
            if (!$setting) {
                continue;
            }

            // Validate the value according to the setting's validation rules
            if ($setting->validation_rules) {
                $validator = Validator::make(
                    [$key => $value],
                    [$key => $setting->validation_rules]
                );

                if ($validator->fails()) {
                    $errors[$key] = $validator->errors()->first($key);
                    continue;
                }
            }

            // Store old value for audit
            $oldValue = $setting->value;

            // Cast the value appropriately before saving
            $castValue = $this->castValueForStorage($value, $setting->type);
            
            if (Setting::set($key, $castValue)) {
                $updated[] = $setting->label;
                
                // Log the setting change
                $this->auditService->logSettings(
                    'setting_updated',
                    "Setting '{$setting->label}' updated",
                    $setting->fresh(),
                    [
                        'setting_key' => $key,
                        'old_value' => $oldValue,
                        'new_value' => $castValue,
                        'setting_group' => $setting->group,
                        'setting_type' => $setting->type,
                    ]
                );
            }
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $message = count($updated) > 0 
            ? 'Settings updated successfully: ' . implode(', ', $updated)
            : 'No settings were updated.';

        return back()->with('success', $message);
    }

    /**
     * Cast value for storage in database
     */
    private function castValueForStorage($value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? 'true' : 'false',
            'json', 'array' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Reset a setting to its default value
     */
    public function reset(Request $request, string $key)
    {
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return response()->json(['error' => 'Setting not found'], 404);
        }

        // Store old value for audit
        $oldValue = $setting->value;

        // Get default value from seeder or fallback
        $defaultValue = $this->getDefaultValue($key);
        
        if (Setting::set($key, $defaultValue)) {
            // Log the setting reset
            $this->auditService->logSettings(
                'setting_reset',
                "Setting '{$setting->label}' reset to default value",
                $setting->fresh(),
                [
                    'setting_key' => $key,
                    'old_value' => $oldValue,
                    'default_value' => $defaultValue,
                    'setting_group' => $setting->group,
                    'setting_type' => $setting->type,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Setting '{$setting->label}' reset to default value",
                'value' => $defaultValue
            ]);
        }

        return response()->json(['error' => 'Failed to reset setting'], 500);
    }

    /**
     * Get default value for a setting
     */
    private function getDefaultValue(string $key): string
    {
        $defaults = [
            'jwt.access_token_ttl' => '60',
            'jwt.refresh_token_ttl' => '20160',
            'jwt.blacklist_grace_period' => '5',
            'session.lifetime' => '120',
            'session.expire_on_close' => 'false',
            'session.encrypt' => 'false',
            'security.max_login_attempts' => '5',
            'security.lockout_duration' => '15',
            'security.password_reset_ttl' => '60',
            'system.maintenance_mode' => 'false',
            'system.app_name' => 'Central SSO',
        ];

        return $defaults[$key] ?? '';
    }

    /**
     * Clear all settings cache
     */
    public function clearCache()
    {
        Setting::clearCache();
        
        // Log the cache clear operation
        $this->auditService->logSettings(
            'cache_cleared',
            'Settings cache cleared',
            null,
            ['operation' => 'cache_clear']
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Settings cache cleared successfully'
        ]);
    }
}
