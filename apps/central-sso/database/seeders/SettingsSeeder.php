<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // JWT Settings
            [
                'key' => 'jwt.access_token_ttl',
                'value' => '60',
                'type' => 'integer',
                'group' => 'jwt',
                'label' => 'Access Token TTL (minutes)',
                'description' => 'Time to live for JWT access tokens in minutes. Default is 60 minutes.',
                'validation_rules' => 'required|integer|min:1|max:1440',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'jwt.refresh_token_ttl',
                'value' => '20160',
                'type' => 'integer',
                'group' => 'jwt',
                'label' => 'Refresh Token TTL (minutes)',
                'description' => 'Time to live for JWT refresh tokens in minutes. Default is 20160 minutes (2 weeks).',
                'validation_rules' => 'required|integer|min:60|max:43200',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'jwt.blacklist_grace_period',
                'value' => '5',
                'type' => 'integer',
                'group' => 'jwt',
                'label' => 'Blacklist Grace Period (minutes)',
                'description' => 'Grace period before tokens are actually blacklisted. Default is 5 minutes.',
                'validation_rules' => 'required|integer|min:0|max:60',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'key' => 'jwt.required_claims',
                'value' => '["iss","iat","exp","nbf","sub","jti"]',
                'type' => 'json',
                'group' => 'jwt',
                'label' => 'Required JWT Claims',
                'description' => 'List of required claims that must be present in JWT tokens.',
                'validation_rules' => 'required|json',
                'is_public' => false,
                'sort_order' => 4,
            ],

            // Session Settings
            [
                'key' => 'session.lifetime',
                'value' => '120',
                'type' => 'integer',
                'group' => 'session',
                'label' => 'Session Lifetime (minutes)',
                'description' => 'Session lifetime in minutes. Default is 120 minutes (2 hours).',
                'validation_rules' => 'required|integer|min:5|max:1440',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'session.expire_on_close',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'session',
                'label' => 'Expire on Browser Close',
                'description' => 'Whether sessions should expire when the browser is closed.',
                'validation_rules' => 'required|boolean',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'session.encrypt',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'session',
                'label' => 'Encrypt Session Data',
                'description' => 'Whether session data should be encrypted.',
                'validation_rules' => 'required|boolean',
                'is_public' => false,
                'sort_order' => 3,
            ],

            // Security Settings
            [
                'key' => 'security.max_login_attempts',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Max Login Attempts',
                'description' => 'Maximum number of failed login attempts before account lockout.',
                'validation_rules' => 'required|integer|min:3|max:20',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'key' => 'security.lockout_duration',
                'value' => '15',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Lockout Duration (minutes)',
                'description' => 'Duration of account lockout after max login attempts exceeded.',
                'validation_rules' => 'required|integer|min:1|max:1440',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'security.password_reset_ttl',
                'value' => '60',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Password Reset TTL (minutes)',
                'description' => 'Time to live for password reset tokens in minutes.',
                'validation_rules' => 'required|integer|min:10|max:1440',
                'is_public' => false,
                'sort_order' => 3,
            ],

            // System Settings
            [
                'key' => 'system.maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'system',
                'label' => 'Maintenance Mode',
                'description' => 'Enable maintenance mode to block user access.',
                'validation_rules' => 'required|boolean',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'system.app_name',
                'value' => 'Central SSO',
                'type' => 'string',
                'group' => 'system',
                'label' => 'Application Name',
                'description' => 'The name of the application displayed to users.',
                'validation_rules' => 'required|string|max:100',
                'is_public' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($settings as $setting) {
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
