# Settings Configuration Reference

This document provides a complete reference for all available settings in the SSO system, their default values, validation rules, and impact on system behavior.

## Configuration Categories

### 🔑 JWT Token Settings (`jwt` group)

#### `jwt.access_token_ttl`
- **Type**: Integer
- **Default**: 60 (minutes)
- **Description**: Duration for JWT access tokens before expiration
- **Impact**: Controls how long users stay authenticated without refresh
- **Validation**: `required|integer|min:1|max:1440` (1 minute to 24 hours)
- **Recommended Values**:
  - Development: 60 minutes
  - Production: 15-30 minutes
  - High Security: 5-15 minutes

#### `jwt.refresh_token_ttl`
- **Type**: Integer
- **Default**: 20160 (minutes = 14 days)
- **Description**: Duration for JWT refresh tokens before expiration
- **Impact**: Controls how long users can refresh their tokens without re-authentication
- **Validation**: `required|integer|min:60|max:525600` (1 hour to 1 year)
- **Recommended Values**:
  - Development: 20160 minutes (14 days)
  - Production: 10080 minutes (7 days)
  - High Security: 1440 minutes (24 hours)

#### `jwt.required_claims`
- **Type**: JSON Array
- **Default**: `["iss", "iat", "exp", "nbf", "sub", "jti"]`
- **Description**: Required JWT claims for token validation
- **Impact**: Ensures token integrity and security
- **Validation**: `required|json`
- **Standard Claims**:
  - `iss`: Issuer (who issued the token)
  - `iat`: Issued At (when token was created)
  - `exp`: Expiration (when token expires)
  - `nbf`: Not Before (token not valid before this time)
  - `sub`: Subject (user identifier)
  - `jti`: JWT ID (unique token identifier)

#### `jwt.blacklist_grace_period`
- **Type**: Integer
- **Default**: 5 (minutes)
- **Description**: Grace period for blacklisted tokens
- **Impact**: Prevents immediate token rejection during logout
- **Validation**: `required|integer|min:0|max:60`

### 🔒 Session Management (`session` group)

#### `session.lifetime`
- **Type**: Integer
- **Default**: 120 (minutes)
- **Description**: Browser session duration for web interface
- **Impact**: Controls how long admin users stay logged in
- **Validation**: `required|integer|min:15|max:43200` (15 minutes to 30 days)
- **Recommended Values**:
  - Development: 120 minutes
  - Production: 60 minutes
  - High Security: 30 minutes

#### `session.expire_on_close`
- **Type**: Boolean
- **Default**: false
- **Description**: Whether sessions expire when browser closes
- **Impact**: User experience vs security trade-off
- **Validation**: `required|boolean`
- **Use Cases**:
  - `true`: High security environments
  - `false`: Better user experience

#### `session.encrypt`
- **Type**: Boolean
- **Default**: false
- **Description**: Enable session data encryption
- **Impact**: Additional security for sensitive session data
- **Validation**: `required|boolean`
- **Note**: May impact performance slightly

### 🛡️ Security Settings (`security` group)

#### `security.max_login_attempts`
- **Type**: Integer
- **Default**: 5
- **Description**: Maximum failed login attempts before account lockout
- **Impact**: Brute force attack protection
- **Validation**: `required|integer|min:3|max:20`
- **Recommended Values**:
  - Standard: 5 attempts
  - High Security: 3 attempts
  - Lenient: 10 attempts

#### `security.lockout_duration`
- **Type**: Integer
- **Default**: 15 (minutes)
- **Description**: Duration of account lockout after max failed attempts
- **Impact**: Balance between security and user convenience
- **Validation**: `required|integer|min:1|max:1440` (1 minute to 24 hours)
- **Recommended Values**:
  - Standard: 15 minutes
  - High Security: 60 minutes
  - Lenient: 5 minutes

#### `security.password_reset_ttl`
- **Type**: Integer
- **Default**: 60 (minutes)
- **Description**: Password reset token validity duration
- **Impact**: Security vs user convenience for password resets
- **Validation**: `required|integer|min:15|max:1440`
- **Recommended Values**:
  - Standard: 60 minutes
  - High Security: 30 minutes
  - Extended: 120 minutes

### ⚙️ System Configuration (`system` group)

#### `system.app_name`
- **Type**: String
- **Default**: "Central SSO"
- **Description**: Display name for the SSO application
- **Impact**: Branding and user interface
- **Validation**: `required|string|max:100`

#### `system.maintenance_mode`
- **Type**: Boolean
- **Default**: false
- **Description**: Enable system-wide maintenance mode
- **Impact**: Blocks user access except for admins
- **Validation**: `required|boolean`
- **Use Cases**:
  - System updates
  - Database maintenance
  - Emergency situations

## Configuration Best Practices

### Security Configuration

#### High Security Environment
```json
{
  "jwt.access_token_ttl": 15,
  "jwt.refresh_token_ttl": 1440,
  "session.lifetime": 30,
  "session.expire_on_close": true,
  "session.encrypt": true,
  "security.max_login_attempts": 3,
  "security.lockout_duration": 60,
  "security.password_reset_ttl": 30
}
```

#### Development Environment
```json
{
  "jwt.access_token_ttl": 120,
  "jwt.refresh_token_ttl": 43200,
  "session.lifetime": 480,
  "session.expire_on_close": false,
  "session.encrypt": false,
  "security.max_login_attempts": 10,
  "security.lockout_duration": 5,
  "security.password_reset_ttl": 120
}
```

#### Balanced Production Environment
```json
{
  "jwt.access_token_ttl": 60,
  "jwt.refresh_token_ttl": 10080,
  "session.lifetime": 120,
  "session.expire_on_close": false,
  "session.encrypt": true,
  "security.max_login_attempts": 5,
  "security.lockout_duration": 15,
  "security.password_reset_ttl": 60
}
```

## Impact Analysis

### JWT Token TTL Impact

#### Short TTL (5-15 minutes)
- **Pros**: Maximum security, limited exposure window
- **Cons**: Frequent token refresh required, potential UX friction
- **Use Case**: High-security environments, financial systems

#### Medium TTL (30-60 minutes)
- **Pros**: Good security-UX balance, reasonable refresh frequency
- **Cons**: Moderate exposure window
- **Use Case**: Standard business applications

#### Long TTL (120+ minutes)
- **Pros**: Excellent UX, minimal token refresh
- **Cons**: Extended exposure window if compromised
- **Use Case**: Low-risk environments, development

### Session Lifetime Impact

#### Short Sessions (15-30 minutes)
- **Pros**: Minimal security exposure, forces regular re-authentication
- **Cons**: User convenience impact, potential productivity loss
- **Use Case**: Highly sensitive systems

#### Standard Sessions (60-120 minutes)
- **Pros**: Good balance of security and usability
- **Cons**: Moderate exposure window
- **Use Case**: Most business applications

#### Extended Sessions (240+ minutes)
- **Pros**: Maximum user convenience, minimal interruption
- **Cons**: Extended security exposure
- **Use Case**: Trusted environments, development

## Monitoring Recommendations

### Key Metrics to Track

#### Authentication Metrics
```php
// Monitor these settings-related metrics
- Average token lifetime usage
- Token refresh frequency
- Session timeout frequency
- Failed authentication due to expired tokens
- Cache hit rate for settings
```

#### Security Metrics
```php
// Track security-related events
- Account lockout frequency
- Password reset usage
- Failed login attempt patterns
- Session hijacking attempts
```

#### Performance Metrics
```php
// Monitor system performance
- Settings cache performance
- Database query frequency for settings
- Settings page load time
- Bulk update operation time
```

## Migration and Upgrade Path

### Version Compatibility
```php
// Settings system version compatibility
Version 1.0: Basic string/integer settings
Version 1.1: Added boolean and JSON support
Version 1.2: Added validation rules and grouping
Version 1.3: Added JWT and session integration
```

### Upgrade Procedure
```bash
# Standard upgrade process
1. Backup current settings: mysqldump settings table
2. Run migrations: php artisan migrate
3. Update seeders: php artisan db:seed --class=SettingsSeeder
4. Clear cache: Settings interface → Clear Cache
5. Verify configuration: Test critical authentication flows
```

### Rollback Procedure
```bash
# Emergency rollback
1. Restore settings table from backup
2. Clear application cache
3. Restart application services
4. Verify system functionality
```

This configuration reference ensures administrators can optimize the SSO system for their specific security, performance, and usability requirements.