<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Key Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for API key authentication used for server-to-server
    | communication between the central SSO and tenant applications.
    |
    */

    'api_keys' => [
        
        // API keys for tenant applications
        'tenant1' => env('TENANT1_API_KEY'),
        'tenant2' => env('TENANT2_API_KEY'),
        
        // Master API key for administrative access
        'master' => env('MASTER_API_KEY'),
        
        // API key hashing algorithm
        'hash_algorithm' => env('API_KEY_HASH_ALGO', 'sha256'),
        
        // API key prefix for identification
        'key_prefix' => env('API_KEY_PREFIX', 'sso'),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Signing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for HMAC request signing to ensure request integrity
    | and prevent replay attacks in server-to-server communication.
    |
    */

    'request_signing' => [
        
        // HMAC secret for request signing
        'hmac_secret' => env('HMAC_SECRET'),
        
        // HMAC algorithm
        'hmac_algorithm' => env('HMAC_ALGORITHM', 'sha256'),
        
        // Request timeout in minutes
        'request_timeout_minutes' => env('REQUEST_TIMEOUT_MINUTES', 5),
        
        // Headers to include in signature
        'signed_headers' => [
            'content-type',
            'x-timestamp',
            'x-tenant-id',
            'x-request-id'
        ],
        
        // Enable signature verification
        'verify_signatures' => env('VERIFY_REQUEST_SIGNATURES', true),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for API rate limiting to prevent abuse and ensure
    | fair usage across tenant applications.
    |
    */

    'rate_limiting' => [
        
        // Default rate limit per minute
        'default_per_minute' => env('RATE_LIMIT_PER_MINUTE', 60),
        
        // Authentication endpoint specific limits
        'auth_per_minute' => env('AUTH_RATE_LIMIT_PER_MINUTE', 10),
        
        // Audit endpoint specific limits
        'audit_per_minute' => env('AUDIT_RATE_LIMIT_PER_MINUTE', 100),
        
        // Rate limit by IP address
        'by_ip' => env('RATE_LIMIT_BY_IP', true),
        
        // Rate limit by API key
        'by_api_key' => env('RATE_LIMIT_BY_API_KEY', true),
        
        // Rate limit store (redis, database, cache)
        'store' => env('RATE_LIMIT_STORE', 'cache'),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL/TLS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SSL/TLS encryption in server-to-server communication.
    |
    */

    'ssl' => [
        
        // SSL certificate path
        'cert_path' => env('SSL_CERT_PATH', '/etc/ssl/certs/'),
        
        // SSL private key path
        'key_path' => env('SSL_KEY_PATH', '/etc/ssl/private/'),
        
        // Verify SSL certificates
        'verify' => env('SSL_VERIFY', true),
        
        // CA bundle path for verification
        'ca_bundle' => env('SSL_CA_BUNDLE'),
        
        // SSL cipher list
        'ciphers' => env('SSL_CIPHERS', 'ECDHE+AESGCM:ECDHE+CHACHA20:DHE+AESGCM:DHE+CHACHA20:!aNULL:!MD5:!DSS'),
        
        // Minimum TLS version
        'min_version' => env('SSL_MIN_VERSION', '1.2'),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for security headers to be added to API responses.
    |
    */

    'headers' => [
        
        // Enable security headers
        'enabled' => env('ENABLE_SECURITY_HEADERS', true),
        
        // X-Content-Type-Options
        'content_type_options' => 'nosniff',
        
        // X-Frame-Options
        'frame_options' => 'DENY',
        
        // X-XSS-Protection
        'xss_protection' => '1; mode=block',
        
        // Referrer-Policy
        'referrer_policy' => 'strict-origin-when-cross-origin',
        
        // Content-Security-Policy for API responses
        'content_security_policy' => "default-src 'none'; frame-ancestors 'none';",
        
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Cross-Origin Resource Sharing for API endpoints.
    |
    */

    'cors' => [
        
        // Allowed origins for CORS
        'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', ''))),
        
        // Allowed methods
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        
        // Allowed headers
        'allowed_headers' => [
            'Accept',
            'Authorization',
            'Content-Type',
            'X-Requested-With',
            'X-API-Key',
            'X-Timestamp',
            'X-Signature',
            'X-Request-ID',
            'X-Tenant-ID'
        ],
        
        // Exposed headers
        'exposed_headers' => [
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset'
        ],
        
        // Max age for preflight cache
        'max_age' => 86400,
        
        // Support credentials
        'supports_credentials' => false,
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit and Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for security audit logging and monitoring.
    |
    */

    'audit' => [
        
        // Enable security audit logging
        'enabled' => env('SECURITY_AUDIT_ENABLED', true),
        
        // Log channel for security events
        'log_channel' => env('SECURITY_LOG_CHANNEL', 'security'),
        
        // Events to log
        'log_events' => [
            'api_key_auth_success',
            'api_key_auth_failure',
            'signature_verification_success',
            'signature_verification_failure',
            'rate_limit_exceeded',
            'suspicious_activity'
        ],
        
        // Retention period for audit logs (days)
        'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Intrusion Detection Configuration
    |--------------------------------------------------------------------------
    |
    | Basic intrusion detection settings for identifying suspicious activity.
    |
    */

    'intrusion_detection' => [
        
        // Enable intrusion detection
        'enabled' => env('INTRUSION_DETECTION_ENABLED', true),
        
        // Failed authentication attempts before blocking
        'max_failed_attempts' => env('MAX_FAILED_ATTEMPTS', 10),
        
        // Time window for failed attempts (minutes)
        'attempt_window_minutes' => env('ATTEMPT_WINDOW_MINUTES', 15),
        
        // Block duration (minutes)
        'block_duration_minutes' => env('BLOCK_DURATION_MINUTES', 60),
        
        // Whitelist IPs (comma-separated)
        'whitelist_ips' => array_filter(explode(',', env('WHITELIST_IPS', ''))),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings specific to tenant applications.
    |
    */

    'tenants' => [
        
        // Default tenant configuration
        'default' => [
            'rate_limit' => 60,
            'allowed_ips' => [],
            'require_signature' => true,
            'api_version' => 'v1'
        ],
        
        // Tenant-specific overrides
        'tenant1' => [
            'rate_limit' => 100,
            'require_signature' => true,
        ],
        
        'tenant2' => [
            'rate_limit' => 80,
            'require_signature' => true,
        ],
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Development and Testing
    |--------------------------------------------------------------------------
    |
    | Security settings for development and testing environments.
    |
    */

    'development' => [
        
        // Allow insecure connections in development
        'allow_insecure' => env('ALLOW_INSECURE_DEV', false),
        
        // Skip signature verification in testing
        'skip_signature_verification' => env('SKIP_SIGNATURE_VERIFICATION', false),
        
        // Test API keys
        'test_api_keys' => [
            'test_tenant1_key' => 'test_12345',
            'test_tenant2_key' => 'test_67890'
        ],
        
    ],

];