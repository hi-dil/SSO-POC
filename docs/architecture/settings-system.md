# Settings System Architecture

This document provides a technical overview of the settings management system architecture, implementation details, and design decisions.

## System Overview

The settings system is a database-driven configuration management solution that provides:
- Dynamic configuration without code deployments
- Type-safe setting storage and retrieval
- Performance optimization through intelligent caching
- Secure admin interface with permission-based access

## Architecture Components

### 1. Database Layer

#### Settings Table Schema
```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) UNIQUE NOT NULL,
    value TEXT NULL,
    type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    `group` VARCHAR(100) DEFAULT 'general',
    description TEXT NULL,
    validation_rules TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_settings_group (`group`),
    INDEX idx_settings_key_type (`key`, type)
);
```

#### Design Decisions
- **Unique Key Constraint**: Prevents duplicate settings
- **Flexible Value Storage**: TEXT field accommodates various data types
- **Type Enumeration**: Ensures data integrity and enables automatic casting
- **Group Organization**: Logical grouping for admin interface
- **Validation Rules**: JSON storage for Laravel validation rules
- **Audit Trail**: Created/updated timestamps for change tracking

### 2. Model Layer (`App\Models\Setting`)

#### Core Responsibilities
- **Data Access**: CRUD operations with automatic type casting
- **Caching**: Intelligent cache management for performance
- **Validation**: Input validation using stored rules
- **Type Conversion**: Automatic casting between string storage and native types

#### Key Methods

```php
class Setting extends Model
{
    // Static configuration access
    public static function get(string $key, mixed $default = null): mixed
    public static function set(string $key, mixed $value): bool
    
    // JWT-specific helpers
    public static function getJwtAccessTokenTtl(): int
    public static function getJwtRefreshTokenTtl(): int
    
    // Session-specific helpers
    public static function getSessionLifetime(): int
    
    // Bulk operations
    public static function getAllGrouped(): array
    public static function clearCache(): void
    
    // Instance methods
    public function getValueAttribute(): mixed
    public function setValueAttribute(mixed $value): void
}
```

#### Caching Strategy

```php
// Cache hierarchy
setting:{key}           // Individual setting cache (1 hour TTL)
settings:grouped        // Grouped settings cache (1 hour TTL)

// Cache implementation
public static function get(string $key, mixed $default = null): mixed
{
    return Cache::remember("setting:{$key}", 3600, function () use ($key, $default) {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    });
}
```

### 3. Controller Layer (`App\Http\Controllers\Admin\SettingsController`)

#### Responsibilities
- **HTTP Request Handling**: Process web requests for settings management
- **Permission Enforcement**: Ensure proper access control
- **Validation**: Validate setting updates before persistence
- **Cache Management**: Coordinate cache invalidation

#### Endpoint Architecture

```php
class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:system.settings');
    }
    
    // GET /admin/settings - Display settings interface
    public function index(): View
    
    // PUT /admin/settings - Bulk update settings
    public function update(Request $request): RedirectResponse
    
    // POST /admin/settings/reset/{key} - Reset single setting
    public function reset(string $key): JsonResponse
    
    // POST /admin/settings/clear-cache - Clear settings cache
    public function clearCache(): JsonResponse
}
```

### 4. View Layer

#### Component Structure
```
resources/views/admin/settings/
├── index.blade.php          # Main settings interface
└── partials/
    ├── setting-group.blade.php    # Reusable group component
    └── setting-input.blade.php    # Type-specific input component
```

#### UI Architecture
- **Card-based Layout**: Each setting group in a distinct card
- **Type-specific Inputs**: Different UI components for each data type
- **Real-time Validation**: Client-side validation with server confirmation
- **Progressive Enhancement**: JavaScript enhancements over functional base

### 5. Integration Layer

#### JWT Token Integration

The settings system integrates with JWT token creation at multiple points:

```php
// MainAuthController.php
$ttl = Setting::getJwtAccessTokenTtl();
$token = JWTAuth::customClaims($customClaims)
    ->setTTL($ttl)
    ->fromUser($user);

// Api/AuthController.php (login & register)
$ttl = Setting::getJwtAccessTokenTtl();
$token = JWTAuth::customClaims($customClaims)
    ->setTTL($ttl)
    ->fromUser($user);

// Api/AuthController.php (refresh)
$refreshTtl = Setting::getJwtRefreshTokenTtl();
$token = JWTAuth::setTTL($refreshTtl)->refresh(JWTAuth::getToken());

// SSOController.php (3 instances)
$ttl = Setting::getJwtAccessTokenTtl();
$token = JWTAuth::customClaims($customClaims)
    ->setTTL($ttl)
    ->fromUser($user);
```

## Data Flow

### Setting Retrieval Flow
```
1. Application Code → Setting::get($key)
2. Check Cache → Cache::get("setting:{$key}")
3. If Cache Miss → Database Query
4. Type Casting → Convert string to native type
5. Cache Storage → Cache::put("setting:{$key}", $value, 3600)
6. Return Value → Native PHP type
```

### Setting Update Flow
```
1. Admin Interface → HTTP PUT /admin/settings
2. Permission Check → Can:system.settings
3. Validation → Validate against stored rules
4. Database Update → Update settings table
5. Cache Invalidation → Clear affected caches
6. Response → Success/Error feedback
```

## Performance Characteristics

### Caching Strategy
- **Cache Duration**: 1 hour TTL for all settings
- **Cache Granularity**: Individual settings + grouped collections
- **Cache Invalidation**: Automatic on updates, manual clear available
- **Cache Backend**: Laravel's configured cache driver (Redis/Memcached/File)

### Database Optimization
- **Indexed Queries**: Key and group columns are indexed
- **Minimal Queries**: Bulk operations reduce database calls
- **Connection Reuse**: Leverages Laravel's connection pooling

### Memory Usage
- **Lazy Loading**: Settings loaded only when accessed
- **Efficient Storage**: Minimal memory footprint for cached values
- **Garbage Collection**: Automatic cache expiration prevents memory leaks

## Security Architecture

### Access Control
```php
// Permission-based access control
Route::middleware(['auth', 'can:system.settings'])->group(function () {
    Route::get('admin/settings', [SettingsController::class, 'index']);
    Route::put('admin/settings', [SettingsController::class, 'update']);
});

// Role-based permission assignment
Role::findByName('Super Admin')->givePermissionTo('system.settings');
```

### Input Validation
```php
// Multi-layer validation
1. Client-side validation (JavaScript)
2. Laravel request validation (Controller)
3. Setting-specific validation rules (Model)
4. Type casting validation (Database)
```

### Data Integrity
- **Transaction Safety**: Updates wrapped in database transactions
- **Rollback Capability**: Failed updates don't leave partial state
- **Audit Trail**: All changes logged with timestamps
- **Backup Recovery**: Standard database backup/restore procedures

## Extensibility

### Adding New Setting Types

To add support for new data types:

1. **Update Database Enum**:
```sql
ALTER TABLE settings MODIFY type ENUM('string', 'integer', 'boolean', 'json', 'float', 'date');
```

2. **Extend Model Type Casting**:
```php
protected function castValue(string $value, string $type): mixed
{
    return match($type) {
        'string' => $value,
        'integer' => (int) $value,
        'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        'json' => json_decode($value, true),
        'float' => (float) $value,        // New type
        'date' => Carbon::parse($value),   // New type
        default => $value
    };
}
```

3. **Update View Components**:
```blade
@elseif($setting['type'] === 'float')
    <input type="number" step="0.01" ...>
@elseif($setting['type'] === 'date')
    <input type="date" ...>
```

### Adding New Setting Groups

1. **Create Settings in Seeder**:
```php
Setting::create([
    'key' => 'email.smtp_host',
    'value' => 'localhost',
    'type' => 'string',
    'group' => 'email',
    'description' => 'SMTP server hostname'
]);
```

2. **Add Group UI Logic**:
```blade
@elseif($groupName === 'email')
    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
    </svg>
    Email Configuration
```

## Monitoring and Observability

### Metrics Collection
```php
// Example metrics to monitor
- setting_cache_hit_rate
- setting_update_frequency
- setting_validation_errors
- admin_settings_page_views
```

### Logging Strategy
```php
// Automatic logging for:
Log::info('Setting updated', [
    'key' => $key,
    'old_value' => $oldValue,
    'new_value' => $newValue,
    'user_id' => auth()->id(),
    'ip_address' => request()->ip()
]);
```

### Health Checks
```php
// Settings system health indicators
- Database connectivity
- Cache accessibility
- Permission system integrity
- Setting validation completeness
```

## Testing Strategy

### Unit Tests
```php
class SettingModelTest extends TestCase
{
    public function test_get_returns_default_for_missing_setting()
    public function test_set_stores_value_correctly()
    public function test_type_casting_works_correctly()
    public function test_cache_invalidation_on_update()
}
```

### Integration Tests
```php
class SettingsControllerTest extends TestCase
{
    public function test_admin_can_access_settings_page()
    public function test_unauthorized_user_cannot_access_settings()
    public function test_settings_update_requires_permission()
    public function test_cache_clear_works_correctly()
}
```

### Performance Tests
```php
class SettingsPerformanceTest extends TestCase
{
    public function test_cache_improves_read_performance()
    public function test_bulk_updates_are_efficient()
    public function test_memory_usage_stays_within_bounds()
}
```

## Deployment Considerations

### Database Migrations
```bash
# Deploy settings system
docker exec central-sso php artisan migrate
docker exec central-sso php artisan db:seed --class=SettingsSeeder
```

### Cache Warming
```php
// Optional: Pre-warm cache after deployment
Artisan::call('cache:clear');
Setting::getAllGrouped(); // Loads all settings into cache
```

### Configuration Management
```php
// Environment-specific default overrides
if (app()->environment('production')) {
    Setting::set('jwt.access_token_ttl', 30); // Shorter TTL in production
}
```

This architecture provides a robust, scalable, and maintainable foundation for dynamic application configuration management.