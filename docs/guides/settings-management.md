# Settings Management System

The SSO system includes a comprehensive settings management module that allows administrators to configure system behavior through a web interface without requiring code changes.

## Overview

The settings system provides database-driven configuration management with:
- **Real-time configuration**: Changes take effect immediately (with optional caching)
- **Type safety**: Automatic validation and type conversion
- **Permission-based access**: Secure admin-only configuration
- **Modern UI**: Intuitive web interface with grouped settings
- **Performance optimization**: 1-hour caching for frequently accessed settings

## Architecture

### Database Storage
Settings are stored in the `settings` table with the following structure:

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) UNIQUE NOT NULL,
    value TEXT,
    type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    `group` VARCHAR(100) DEFAULT 'general',
    description TEXT,
    validation_rules TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Setting Categories

Settings are organized into logical groups:

#### 🔑 JWT Token Settings (`jwt` group)
- **Access Token TTL**: Duration of JWT access tokens (default: 60 minutes)
- **Refresh Token TTL**: Duration of JWT refresh tokens (default: 14 days)
- **Required Claims**: JWT claims that must be present
- **Blacklist Grace Period**: Time before tokens are fully invalidated

#### 🔒 Session Management (`session` group)
- **Session Lifetime**: Browser session duration (default: 120 minutes)
- **Expire on Close**: Whether sessions end when browser closes
- **Session Encryption**: Enable/disable session data encryption

#### 🛡️ Security Settings (`security` group)
- **Max Login Attempts**: Failed login attempts before lockout (default: 5)
- **Lockout Duration**: Account lockout time after failed attempts (default: 15 minutes)
- **Password Reset TTL**: Password reset token validity (default: 60 minutes)

#### ⚙️ System Configuration (`system` group)
- **Application Name**: Display name for the SSO system
- **Maintenance Mode**: Enable/disable system maintenance mode

## Usage Guide

### Accessing Settings

1. **Login** as a user with `system.settings` permission (Super Admin role)
2. **Navigate** to Admin → Settings in the sidebar
3. **Configure** settings using the web interface

### Modifying Settings

The settings interface is organized into expandable cards by category:

1. **Select a category** (JWT, Session, Security, or System)
2. **Modify values** using appropriate input controls:
   - Text inputs for strings
   - Number inputs for integers
   - Toggle switches for booleans
   - Text areas for JSON objects
3. **Save changes** using the "Save Settings" button
4. **Clear cache** if immediate effect is required

### Setting Data Types

#### String Settings
```php
Setting::set('system.app_name', 'My Custom SSO');
$appName = Setting::get('system.app_name', 'Default SSO');
```

#### Integer Settings
```php
Setting::set('jwt.access_token_ttl', 120); // 2 hours
$ttl = Setting::getJwtAccessTokenTtl(); // Returns integer
```

#### Boolean Settings
```php
Setting::set('session.expire_on_close', true);
$expires = Setting::get('session.expire_on_close', false); // Returns boolean
```

#### JSON Settings
```php
Setting::set('jwt.required_claims', ['iss', 'iat', 'exp', 'nbf', 'sub', 'jti']);
$claims = Setting::get('jwt.required_claims', []); // Returns array
```

## Developer Integration

### Using Settings in Code

The `Setting` model provides convenient methods for accessing configuration:

```php
use App\Models\Setting;

// JWT Token Configuration
$accessTtl = Setting::getJwtAccessTokenTtl();    // Default: 60 minutes
$refreshTtl = Setting::getJwtRefreshTokenTtl();  // Default: 20160 minutes (14 days)

// Session Configuration
$sessionLifetime = Setting::getSessionLifetime(); // Default: 120 minutes

// Generic Setting Access
$value = Setting::get('custom.setting', 'default_value');
Setting::set('custom.setting', 'new_value');

// Get All Settings Grouped
$allSettings = Setting::getAllGrouped();
```

### JWT Integration

All JWT token creation automatically uses the configured TTL values:

```php
// In controllers, JWT tokens automatically use settings
$token = JWTAuth::customClaims($customClaims)
    ->setTTL(Setting::getJwtAccessTokenTtl())
    ->fromUser($user);

// Refresh tokens also use configured TTL
$refreshToken = JWTAuth::setTTL(Setting::getJwtRefreshTokenTtl())
    ->refresh(JWTAuth::getToken());
```

### Adding New Settings

To add new settings, update the `SettingsSeeder`:

```php
// database/seeders/SettingsSeeder.php
Setting::create([
    'key' => 'custom.new_setting',
    'value' => 'default_value',
    'type' => 'string',
    'group' => 'custom',
    'description' => 'Description of the new setting',
    'validation_rules' => 'required|string|max:255'
]);
```

## Caching Strategy

### Performance Optimization
- Settings are cached for **1 hour** by default
- Cache keys use the pattern: `setting:{key}`
- Grouped settings are cached as: `settings:grouped`

### Cache Management
```php
// Manual cache clearing
Setting::clearCache();

// Or via the web interface
// Admin → Settings → Clear Cache button
```

### Cache Invalidation
Cache is automatically cleared when:
- Settings are updated via the web interface
- Settings are modified programmatically using `Setting::set()`
- Manual cache clear is triggered

## Permissions

### Required Permissions
- **View Settings**: `system.settings` permission
- **Modify Settings**: `system.settings` permission

### Default Access
The following roles have settings access by default:
- **Super Admin**: Full access to all settings

### Granting Access
To grant settings access to additional roles:

1. Navigate to **Admin → Roles**
2. Edit the target role
3. Add the **system.settings** permission
4. Save the role configuration

## API Reference

### SettingsController Endpoints

#### GET /admin/settings
Display the settings management interface.

**Requirements**: `system.settings` permission

#### PUT /admin/settings
Update multiple settings at once.

**Request Body**:
```json
{
  "settings": {
    "jwt.access_token_ttl": 90,
    "session.lifetime": 180,
    "system.app_name": "Custom SSO"
  }
}
```

#### POST /admin/settings/reset/{key}
Reset a specific setting to its default value.

**Parameters**: 
- `key`: The setting key to reset

#### POST /admin/settings/clear-cache
Clear the settings cache to apply changes immediately.

## Security Considerations

### Access Control
- Settings modification requires elevated permissions
- All changes are logged and auditable
- Permission checks are enforced at the controller level

### Validation
- All settings undergo validation before storage
- Type safety is enforced automatically
- Invalid values are rejected with clear error messages

### Data Integrity
- Settings use database transactions for consistency
- Cache invalidation ensures data freshness
- Backup and restore capabilities through database management

## Troubleshooting

### Common Issues

#### "Access Denied" Error
**Cause**: User lacks `system.settings` permission
**Solution**: Grant the permission via Role Management

#### Changes Not Taking Effect
**Cause**: Settings are cached
**Solution**: Use "Clear Cache" button in the settings interface

#### Validation Errors
**Cause**: Invalid data type or value
**Solution**: Check the setting description and provide valid input

#### Settings Not Loading
**Cause**: Database migration not run or seeder not executed
**Solution**: 
```bash
docker exec central-sso php artisan migrate
docker exec central-sso php artisan db:seed --class=SettingsSeeder
```

### Debug Commands

```bash
# Check settings table contents
docker exec sso-mariadb mysql -u sso_user -psso_password sso_main \
  -e "SELECT \`key\`, value, type, \`group\` FROM settings ORDER BY \`group\`, \`key\`;"

# Verify user permissions
docker exec central-sso php artisan tinker \
  --execute="User::where('email', 'superadmin@sso.com')->first()->hasPermissionTo('system.settings')"

# Clear application cache
docker exec central-sso php artisan cache:clear
```

## Best Practices

### Setting Design
1. **Use descriptive keys**: `jwt.access_token_ttl` vs `jwt_ttl`
2. **Group related settings**: Use consistent group names
3. **Provide defaults**: Always include sensible default values
4. **Add descriptions**: Help administrators understand each setting

### Performance
1. **Cache frequently accessed settings**: Use the built-in caching
2. **Batch setting updates**: Update multiple settings in one operation
3. **Clear cache when needed**: Don't rely on cache expiration for critical changes

### Security
1. **Validate input**: Always define validation rules for new settings
2. **Use appropriate permissions**: Don't grant settings access unnecessarily
3. **Audit changes**: Monitor who modifies settings and when

## Migration Guide

### From Hardcoded Configuration
To migrate from hardcoded configuration values:

1. **Identify configuration values** currently in code
2. **Create setting entries** in the SettingsSeeder
3. **Update code** to use `Setting::get()` instead of hardcoded values
4. **Test thoroughly** to ensure backward compatibility

### Example Migration
```php
// Before (hardcoded)
$ttl = 60; // minutes

// After (configurable)
$ttl = Setting::getJwtAccessTokenTtl();
```

This settings system provides a robust foundation for configurable application behavior while maintaining security, performance, and ease of use.