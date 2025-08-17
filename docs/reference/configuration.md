# Configuration Reference

Complete guide to environment variables, settings, and configuration options for the multi-tenant SSO system.

## 🏗️ Configuration Overview

The SSO system uses environment variables for configuration across three main areas:
- **Application Settings**: Core application behavior and features
- **Security Configuration**: Authentication, encryption, and access control
- **Infrastructure Settings**: Database, caching, and external services

## 🐳 Docker Compose Configuration

### Configuration Files
The system provides multiple configuration approaches:

- **`.env.docker`** - Pre-configured template with all Docker Compose variables
- **`.env`** - Your customized environment file (copy from .env.docker)
- **`.env.example`** - Comprehensive reference with all possible variables

### Docker Compose Variables

#### Container Configuration
```bash
# Container Names (configurable for multiple deployments)
CENTRAL_SSO_CONTAINER=central-sso
TENANT1_CONTAINER=tenant1-app
TENANT2_CONTAINER=tenant2-app
MARIADB_CONTAINER=sso-mariadb

# Port Mappings
CENTRAL_SSO_PORT=8000      # External port for Central SSO
TENANT1_PORT=8001          # External port for Tenant 1
TENANT2_PORT=8002          # External port for Tenant 2
MARIADB_EXTERNAL_PORT=3307 # External port for MariaDB
```

#### Service-Specific Application Variables
```bash
# Central SSO Application
CENTRAL_SSO_APP_NAME="Central SSO"
CENTRAL_SSO_APP_URL=http://localhost:8000
CENTRAL_SSO_APP_KEY=base64:GENERATED_KEY
CENTRAL_SSO_SESSION_COOKIE=central_sso_session

# Tenant 1 Application
TENANT1_APP_NAME="Tenant 1 Application"
TENANT1_APP_URL=http://localhost:8001
TENANT1_APP_KEY=base64:GENERATED_KEY
TENANT1_SESSION_COOKIE=tenant1_session

# Tenant 2 Application
TENANT2_APP_NAME="Tenant 2 Application"
TENANT2_APP_URL=http://localhost:8002
TENANT2_APP_KEY=base64:GENERATED_KEY
TENANT2_SESSION_COOKIE=tenant2_session
```

#### Database Configuration
```bash
# MariaDB Version
MARIADB_VERSION=10.9

# Shared Database Settings
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_USERNAME=sso_user
DB_PASSWORD=sso_password

# Database Names (per service)
CENTRAL_SSO_DB_DATABASE=sso_main
TENANT1_DB_DATABASE=tenant1_db
TENANT2_DB_DATABASE=tenant2_db

# MariaDB Root Access
MYSQL_ROOT_PASSWORD=root_password
```

#### Volume Configuration
```bash
# Data Persistence
MARIADB_DATA_VOLUME=mariadb_data
```

### Environment Variable Inheritance
Docker Compose uses variable substitution with fallback defaults:
```yaml
container_name: ${CENTRAL_SSO_CONTAINER:-central-sso}
ports:
  - "${CENTRAL_SSO_PORT:-8000}:8000"
environment:
  - APP_KEY=${CENTRAL_SSO_APP_KEY}
```

This allows the system to:
- Work out-of-the-box with sensible defaults
- Be easily customized by setting environment variables
- Support multiple deployment environments

## 📋 Environment Variables Reference

### Application Configuration

#### Core Application Settings
```bash
# Application Identity
APP_NAME="Multi-Tenant SSO"           # Application display name
APP_ENV=production                     # Environment: local, staging, production
APP_KEY=base64:GENERATED_KEY          # Laravel encryption key (32 chars)
APP_DEBUG=false                       # Debug mode (true only for development)
APP_URL=https://sso.your-domain.com   # Base application URL
APP_TIMEZONE=UTC                      # Application timezone

# Tenant Configuration
TENANT_SLUG=central-sso               # Current tenant identifier
TENANT_DOMAIN=sso.your-domain.com     # Tenant domain for routing
TENANT_NAME="Central SSO Server"      # Human-readable tenant name
```

#### Logging Configuration
```bash
# Logging Settings
LOG_CHANNEL=stack                     # Logging driver: stack, single, daily
LOG_DEPRECATIONS_CHANNEL=null         # Deprecation warnings channel
LOG_LEVEL=info                        # Log level: debug, info, warning, error

# Custom Log Channels
LOG_AUDIT_CHANNEL=audit              # Audit log channel
LOG_SECURITY_CHANNEL=security        # Security event channel
LOG_PERFORMANCE_CHANNEL=performance   # Performance monitoring channel
```

### Database Configuration

#### Primary Database (MariaDB)
```bash
# Database Connection
DB_CONNECTION=mysql                   # Database driver
DB_HOST=mariadb                      # Database host (container name or IP)
DB_PORT=3306                         # Database port
DB_DATABASE=sso_main                 # Central SSO database name
DB_USERNAME=sso_user                 # Database username
DB_PASSWORD=secure_password          # Database password
DB_CHARSET=utf8mb4                   # Character set
DB_COLLATION=utf8mb4_unicode_ci      # Collation

# Connection Pool Settings
DB_POOL_MIN=5                        # Minimum pool connections
DB_POOL_MAX=50                       # Maximum pool connections
DB_TIMEOUT=60                        # Connection timeout (seconds)
DB_RETRY_AFTER=30                    # Retry after failure (seconds)
```

#### Tenant Databases
```bash
# Tenant-specific database settings
TENANT1_DB_DATABASE=tenant1_db       # Tenant 1 database
TENANT1_DB_USERNAME=tenant1_user     # Tenant 1 database user
TENANT1_DB_PASSWORD=tenant1_password # Tenant 1 database password

TENANT2_DB_DATABASE=tenant2_db       # Tenant 2 database
TENANT2_DB_USERNAME=tenant2_user     # Tenant 2 database user
TENANT2_DB_PASSWORD=tenant2_password # Tenant 2 database password
```

### Caching and Sessions

#### Redis Configuration
```bash
# Redis Settings
REDIS_HOST=redis                     # Redis host (container name or IP)
REDIS_PASSWORD=redis_password        # Redis password (optional)
REDIS_PORT=6379                      # Redis port
REDIS_DB=0                          # Redis database number

# Cache Configuration
CACHE_DRIVER=redis                   # Cache driver: redis, file, array
CACHE_PREFIX=sso_cache              # Cache key prefix
CACHE_TTL=3600                      # Default cache TTL (seconds)
```

#### Session Management
```bash
# Session Configuration
SESSION_DRIVER=redis                 # Session driver: redis, database, file
SESSION_LIFETIME=120                 # Session lifetime (minutes)
SESSION_ENCRYPT=true                 # Encrypt session data
SESSION_HTTP_ONLY=true              # HTTP only cookies
SESSION_SAME_SITE=lax               # SameSite cookie attribute
SESSION_SECURE_COOKIE=true          # Secure cookies (HTTPS only)
SESSION_DOMAIN=.your-domain.com     # Cookie domain for cross-tenant sharing
```

### JWT Authentication

#### JWT Core Settings
```bash
# JWT Configuration
JWT_SECRET=your_32_character_jwt_secret_here  # JWT signing secret (32+ chars)
JWT_TTL=60                                    # Token lifetime (minutes)
JWT_REFRESH_TTL=20160                        # Refresh token lifetime (minutes)
JWT_ALGO=HS256                               # JWT algorithm: HS256, RS256
JWT_REQUIRED_CLAIMS=iss,iat,exp,nbf,sub,jti  # Required JWT claims

# Token Management
JWT_BLACKLIST_ENABLED=true                   # Enable token blacklisting
JWT_BLACKLIST_GRACE_PERIOD=10               # Grace period for blacklist (minutes)
JWT_PERSIST_CLAIMS=false                    # Persist custom claims
JWT_LOCK_SUBJECT=true                       # Lock token to subject
```

#### Custom JWT Claims
```bash
# Custom Claims Configuration
JWT_CUSTOM_CLAIMS_ENABLED=true              # Enable custom claims
JWT_INCLUDE_USER_CLAIMS=true                # Include user data in token
JWT_INCLUDE_TENANT_CLAIMS=true              # Include tenant data in token
JWT_INCLUDE_PERMISSION_CLAIMS=true          # Include permissions in token
```

### SSO Integration

#### Central SSO Configuration
```bash
# Central SSO Settings
CENTRAL_SSO_URL=https://sso.your-domain.com  # Central SSO server URL
CENTRAL_SSO_API_TIMEOUT=30                   # API request timeout (seconds)
CENTRAL_SSO_RETRY_ATTEMPTS=3                 # Retry attempts for failed requests
CENTRAL_SSO_VERIFY_SSL=true                  # Verify SSL certificates

# Tenant Application Settings
TENANT_API_KEY=tenant1_secure_api_key_here   # Tenant-specific API key
HMAC_SECRET=your_64_character_hmac_secret    # HMAC signing secret (64+ chars)
SSO_CALLBACK_URL=https://tenant1.your-domain.com/auth/callback  # SSO callback URL
```

#### Cross-Tenant Settings
```bash
# Cross-Tenant Configuration
ALLOW_CROSS_TENANT_ACCESS=true              # Enable cross-tenant SSO
CROSS_TENANT_SESSION_SHARING=true           # Share sessions across tenants
TENANT_ISOLATION_MODE=strict                # Isolation mode: strict, relaxed
DEFAULT_TENANT_ROLE=user                    # Default role for new tenant users
```

### Security Configuration

#### Authentication Security
```bash
# Password Requirements
PASSWORD_MIN_LENGTH=8                       # Minimum password length
PASSWORD_REQUIRE_UPPERCASE=true             # Require uppercase letters
PASSWORD_REQUIRE_LOWERCASE=true             # Require lowercase letters
PASSWORD_REQUIRE_NUMBERS=true               # Require numbers
PASSWORD_REQUIRE_SYMBOLS=true               # Require symbols
PASSWORD_HISTORY_COUNT=5                    # Password history to check
PASSWORD_MAX_AGE_DAYS=90                   # Password expiration (days)

# Account Security
ACCOUNT_LOCKOUT_ENABLED=true                # Enable account lockout
ACCOUNT_LOCKOUT_ATTEMPTS=5                  # Failed attempts before lockout
ACCOUNT_LOCKOUT_DURATION=300               # Lockout duration (seconds)
ACCOUNT_LOCKOUT_RESET_TIME=900             # Time to reset failed attempts
```

#### Rate Limiting
```bash
# Rate Limiting Configuration
THROTTLE_LOGIN_MAX_ATTEMPTS=5               # Max login attempts per window
THROTTLE_LOGIN_DECAY_MINUTES=5              # Login throttle window (minutes)
THROTTLE_API_MAX_REQUESTS=60                # Max API requests per window
THROTTLE_API_WINDOW_MINUTES=1               # API throttle window (minutes)
THROTTLE_GLOBAL_MAX_REQUESTS=1000          # Global rate limit per window
THROTTLE_GLOBAL_WINDOW_MINUTES=60          # Global throttle window (minutes)

# IP-based Rate Limiting
THROTTLE_BY_IP=true                         # Enable IP-based throttling
THROTTLE_WHITELIST_IPS=127.0.0.1,::1      # Whitelisted IPs (comma-separated)
THROTTLE_BLACKLIST_IPS=                     # Blacklisted IPs (comma-separated)
```

#### Encryption and Hashing
```bash
# Encryption Settings
ENCRYPTION_CIPHER=AES-256-CBC               # Encryption cipher
BCRYPT_ROUNDS=12                           # Bcrypt hashing rounds
ARGON_MEMORY=65536                         # Argon2 memory usage (KB)
ARGON_TIME=4                               # Argon2 time cost
ARGON_THREADS=3                            # Argon2 thread count

# Data Protection
HASH_SENSITIVE_DATA=true                   # Hash sensitive data in logs
ENCRYPT_PERSONAL_DATA=true                 # Encrypt personal data at rest
DATA_RETENTION_DAYS=2555                   # Data retention period (7 years)
```

### External Services

#### Email Configuration
```bash
# Email Settings
MAIL_MAILER=smtp                           # Mail driver: smtp, ses, mailgun
MAIL_HOST=smtp.mailtrap.io                 # SMTP host
MAIL_PORT=2525                             # SMTP port
MAIL_USERNAME=your_username                # SMTP username
MAIL_PASSWORD=your_password                # SMTP password
MAIL_ENCRYPTION=tls                        # Encryption: tls, ssl, null
MAIL_FROM_ADDRESS=noreply@your-domain.com  # From email address
MAIL_FROM_NAME="Multi-Tenant SSO"          # From name

# Email Templates
MAIL_WELCOME_TEMPLATE=emails.welcome       # Welcome email template
MAIL_PASSWORD_RESET_TEMPLATE=emails.reset  # Password reset template
MAIL_LOCKOUT_TEMPLATE=emails.lockout       # Account lockout template
```

#### Cloud Services
```bash
# AWS Configuration (if using AWS services)
AWS_ACCESS_KEY_ID=your_access_key          # AWS access key
AWS_SECRET_ACCESS_KEY=your_secret_key      # AWS secret key
AWS_DEFAULT_REGION=us-east-1               # AWS region
AWS_BUCKET=your-s3-bucket                  # S3 bucket for file storage
AWS_USE_PATH_STYLE_ENDPOINT=false          # Use path-style endpoints

# Cloudflare Configuration
CLOUDFLARE_API_TOKEN=your_cloudflare_token # Cloudflare API token
CLOUDFLARE_ZONE_ID=your_zone_id           # Cloudflare zone ID
CLOUDFLARE_TUNNEL_TOKEN=your_tunnel_token  # Cloudflare tunnel token
```

### Monitoring and Analytics

#### Application Monitoring
```bash
# Monitoring Settings
TELESCOPE_ENABLED=true                     # Enable Laravel Telescope
TELESCOPE_DOMAIN=telescope.your-domain.com # Telescope subdomain
TELESCOPE_PATH=telescope                   # Telescope path
TELESCOPE_MIDDLEWARE=auth                  # Telescope middleware

# Performance Monitoring
PERFORMANCE_MONITORING=true               # Enable performance monitoring
SLOW_QUERY_THRESHOLD=1000                # Slow query threshold (ms)
MEMORY_LIMIT_WARNING=80                  # Memory usage warning threshold (%)
CPU_LIMIT_WARNING=80                     # CPU usage warning threshold (%)
```

#### Analytics and Reporting
```bash
# Analytics Configuration
ANALYTICS_ENABLED=true                    # Enable analytics collection
ANALYTICS_RETENTION_DAYS=365             # Analytics data retention
ANALYTICS_EXPORT_ENABLED=true           # Enable data export
ANALYTICS_REAL_TIME=true                 # Enable real-time analytics

# Audit Logging
AUDIT_LOG_ENABLED=true                   # Enable audit logging
AUDIT_LOG_DRIVER=database               # Audit log driver: database, file
AUDIT_LOG_RETENTION_DAYS=2555           # Audit log retention (7 years)
AUDIT_INCLUDE_REQUEST_DATA=true         # Include request data in audit logs
```

### Development and Testing

#### Development Settings
```bash
# Development Configuration
APP_DEBUG=true                           # Enable debug mode (dev only)
DEBUGBAR_ENABLED=true                   # Enable debug bar (dev only)
LOG_QUERIES=true                        # Log database queries (dev only)
MAIL_MAILER=log                         # Use log mail driver (dev only)

# Testing Configuration
TESTING_DATABASE=sso_testing            # Testing database name
TESTING_REDIS_DB=1                      # Testing Redis database
TESTING_CACHE_DRIVER=array              # Testing cache driver
TESTING_SESSION_DRIVER=array            # Testing session driver
```

#### Feature Flags
```bash
# Feature Toggles
FEATURE_USER_REGISTRATION=true          # Enable user registration
FEATURE_PASSWORD_RESET=true             # Enable password reset
FEATURE_MULTI_TENANT_USERS=true         # Enable multi-tenant users
FEATURE_ADVANCED_PERMISSIONS=true       # Enable advanced permissions
FEATURE_API_RATE_LIMITING=true         # Enable API rate limiting
FEATURE_REAL_TIME_NOTIFICATIONS=true   # Enable real-time notifications
```

## 🔧 Configuration Management

### Environment-Specific Configuration

#### Local Development (.env.local)
```bash
# Local Development Overrides
APP_ENV=local
APP_DEBUG=true
DB_HOST=localhost
CACHE_DRIVER=file
MAIL_MAILER=log
TELESCOPE_ENABLED=true
```

#### Staging Environment (.env.staging)
```bash
# Staging Environment
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://staging-sso.your-domain.com
DB_HOST=staging-mariadb
CACHE_DRIVER=redis
MAIL_MAILER=smtp
```

#### Production Environment (.env.production)
```bash
# Production Environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sso.your-domain.com
DB_HOST=production-mariadb
CACHE_DRIVER=redis
MAIL_MAILER=ses
TELESCOPE_ENABLED=false
```

### Configuration Validation

#### Required Variables Check
```bash
# Script to validate required environment variables
#!/bin/bash

REQUIRED_VARS=(
    "APP_KEY"
    "JWT_SECRET"
    "DB_PASSWORD"
    "HMAC_SECRET"
    "TENANT_API_KEY"
)

for var in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!var}" ]; then
        echo "ERROR: $var is not set"
        exit 1
    fi
done

echo "All required variables are set"
```

#### Security Configuration Audit
```bash
# Check security configuration
php artisan config:security-audit

# Example output:
# ✓ APP_DEBUG is disabled in production
# ✓ JWT_SECRET is properly configured
# ✓ Strong password requirements enabled
# ⚠ HTTPS enforcement recommended
# ✗ Rate limiting not configured
```

### Configuration Best Practices

#### Security Guidelines
1. **Never commit .env files** to version control
2. **Use strong secrets** (32+ characters for JWT_SECRET, 64+ for HMAC_SECRET)
3. **Disable debug mode** in production (APP_DEBUG=false)
4. **Enable HTTPS enforcement** for production environments
5. **Use secure session settings** (SESSION_SECURE_COOKIE=true)
6. **Configure rate limiting** to prevent abuse
7. **Set up proper CORS** for API access

#### Performance Optimization
1. **Use Redis for caching** and sessions in production
2. **Configure connection pooling** for database
3. **Set appropriate cache TTL** values
4. **Enable opcache** for PHP optimization
5. **Use CDN** for static assets
6. **Configure proper logging levels** to avoid performance impact

#### Monitoring and Maintenance
1. **Enable comprehensive logging** for production issues
2. **Set up health checks** and monitoring endpoints
3. **Configure audit logging** for compliance
4. **Set data retention policies** appropriately
5. **Monitor configuration drift** between environments
6. **Automate configuration validation** in CI/CD pipelines

---

## 🔗 Related Documentation

- **[CLI Commands](cli-commands.md)** - Command-line tools for configuration management
- **[Troubleshooting Guide](troubleshooting.md)** - Configuration-related issues and solutions
- **[Security Guide](../guides/security.md)** - Security configuration best practices
- **[Deployment Guide](../deployment/README.md)** - Environment-specific deployment configuration