<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security configuration for secure tenant integration
    | with the central SSO system. All settings are production-ready and
    | provide enterprise-grade protection.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Authentication
    |--------------------------------------------------------------------------
    |
    | Configuration for secure API communication with central SSO server.
    | API keys should be obtained from your SSO administrator.
    |
    */
    'api_key' => env('TENANT_API_KEY'),
    'hmac_secret' => env('HMAC_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | SSL/TLS Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for encrypted communication with central SSO server.
    | Always enable SSL verification in production environments.
    |
    */
    'ssl_verify' => env('SSL_VERIFY', true),
    'ssl_enabled' => env('SSL_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Central SSO Server
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to the central SSO authentication server.
    | Adjust timeouts and retry attempts based on your network conditions.
    |
    */
    'central_sso' => [
        'url' => env('CENTRAL_SSO_URL', 'http://localhost:8000'),
        'timeout' => env('SSO_TIMEOUT', 30),
        'retry_attempts' => env('SSO_RETRY_ATTEMPTS', 3),
        'health_check_interval' => env('SSO_HEALTH_CHECK_INTERVAL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Protection against brute force attacks and API abuse.
    | Configure limits based on your security requirements.
    |
    */
    'rate_limiting' => [
        'login_attempts' => env('LOGIN_RATE_LIMIT', 5),
        'login_window' => env('LOGIN_RATE_WINDOW', 300), // 5 minutes
        'api_requests_per_minute' => env('API_RATE_LIMIT', 60),
        'enabled' => env('RATE_LIMITING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit & Logging
    |--------------------------------------------------------------------------
    |
    | Comprehensive audit configuration for compliance and monitoring.
    | Enable detailed logging for production environments.
    |
    */
    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'log_failed_attempts' => env('AUDIT_LOG_FAILED', true),
        'log_successful_attempts' => env('AUDIT_LOG_SUCCESS', true),
        'log_sso_events' => env('AUDIT_LOG_SSO', true),
        'log_security_events' => env('AUDIT_LOG_SECURITY', true),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Signing
    |--------------------------------------------------------------------------
    |
    | HMAC request signing configuration for request integrity protection.
    | These settings ensure requests cannot be tampered with in transit.
    |
    */
    'request_signing' => [
        'enabled' => env('REQUEST_SIGNING_ENABLED', true),
        'algorithm' => 'sha256',
        'timestamp_tolerance' => env('TIMESTAMP_TOLERANCE', 300), // 5 minutes
        'include_body_hash' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Enhanced session security configuration for production environments.
    | These settings provide additional protection for user sessions.
    |
    */
    'session' => [
        'regenerate_on_login' => true,
        'timeout_minutes' => env('SESSION_TIMEOUT', 120), // 2 hours
        'secure_cookies' => env('SESSION_SECURE_COOKIE', false),
        'same_site' => env('SESSION_SAME_SITE', 'lax'),
        'encrypt_session_data' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configuration for HTTP security headers to protect against common
    | web vulnerabilities like XSS, clickjacking, and CSRF attacks.
    |
    */
    'headers' => [
        'hsts' => [
            'enabled' => env('HSTS_ENABLED', false),
            'max_age' => 31536000, // 1 year
            'include_subdomains' => true,
            'preload' => false,
        ],
        'csp' => [
            'enabled' => env('CSP_ENABLED', false),
            'policy' => env('CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"),
        ],
        'frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for security monitoring and alerting systems.
    | Set up notifications for critical security events.
    |
    */
    'monitoring' => [
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        'failed_login_threshold' => env('FAILED_LOGIN_THRESHOLD', 10),
        'alert_email' => env('SECURITY_ALERT_EMAIL'),
        'webhook_url' => env('SECURITY_WEBHOOK_URL'),
        'log_level' => env('SECURITY_LOG_LEVEL', 'warning'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing
    |--------------------------------------------------------------------------
    |
    | Settings specific to development and testing environments.
    | These should be disabled or secured in production.
    |
    */
    'development' => [
        'disable_ssl_verification' => env('APP_ENV') === 'local',
        'mock_sso_responses' => env('MOCK_SSO_RESPONSES', false),
        'verbose_logging' => env('VERBOSE_SECURITY_LOGGING', false),
        'test_mode' => env('APP_ENV') === 'testing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags for gradually rolling out security features or
    | temporarily disabling them during maintenance.
    |
    */
    'features' => [
        'api_key_auth' => env('FEATURE_API_KEY_AUTH', true),
        'hmac_signing' => env('FEATURE_HMAC_SIGNING', true),
        'rate_limiting' => env('FEATURE_RATE_LIMITING', true),
        'audit_logging' => env('FEATURE_AUDIT_LOGGING', true),
        'health_checks' => env('FEATURE_HEALTH_CHECKS', true),
        'security_headers' => env('FEATURE_SECURITY_HEADERS', true),
    ],
];