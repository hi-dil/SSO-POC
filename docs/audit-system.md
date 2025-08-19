# 🔍 Audit Logging System Documentation

## Overview

The SSO system includes a comprehensive audit logging system that tracks all user activities, system changes, and administrative actions. This system provides complete visibility into who did what, when, and how within the application.

## 🏗️ Architecture

### Components

1. **Spatie Activity Log Package** - Core logging functionality
2. **AuditService** - Centralized service for structured logging
3. **Module Organization** - Organized by functional areas
4. **Database Storage** - MariaDB with optimized indexes
5. **Admin Interface** - Real-time viewing, filtering, and export

### Database Schema

The audit system uses the `activity_log` table with the following structure:

```sql
CREATE TABLE activity_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_name VARCHAR(255),
    description TEXT NOT NULL,
    subject_type VARCHAR(255),
    subject_id BIGINT UNSIGNED,
    causer_type VARCHAR(255),
    causer_id BIGINT UNSIGNED,
    properties JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    -- Performance indexes
    INDEX activity_log_causer_created_at_index (causer_id, causer_type, created_at),
    INDEX activity_log_subject_created_at_index (subject_id, subject_type, created_at),
    INDEX activity_log_log_name_index (log_name),
    INDEX activity_log_created_at_index (created_at)
);
```

## 🎯 Module Organization

The audit system is organized into functional modules:

### 1. Authentication Module
- **User login/logout events**
- **Failed login attempts** 
- **Session management**
- **Password changes**

**Submodules:**
- `login_success` - Successful user authentication
- `login_failed` - Failed authentication attempts
- `logout` - User logout events
- `password_changed` - Password update events
- `session_expired` - Session timeout events

### 2. User Management Module
- **User CRUD operations**
- **Profile updates**
- **Account status changes**
- **Role assignments**

**Submodules:**
- `user_created` - New user creation
- `user_updated` - User profile updates
- `user_deleted` - User account deletion
- `tenant_assigned` - Tenant access granted
- `tenant_removed` - Tenant access revoked
- `profile_updated` - Personal profile changes
- `contact_added` - Contact information added
- `address_updated` - Address information updated

### 3. Tenant Management Module
- **Tenant CRUD operations**
- **Configuration changes**
- **User assignments**
- **Feature toggles**

**Submodules:**
- `tenant_created` - New tenant creation
- `tenant_updated` - Tenant configuration changes
- `tenant_deleted` - Tenant deletion
- `tenant_activated` - Tenant activation
- `tenant_deactivated` - Tenant deactivation
- `user_assigned` - User assigned to tenant
- `user_removed` - User removed from tenant

### 4. Settings Module
- **System configuration changes**
- **Feature flag updates**
- **Cache operations**
- **Configuration resets**

**Submodules:**
- `setting_updated` - Configuration value changed
- `setting_reset` - Setting reset to default
- `cache_cleared` - Cache invalidation
- `config_exported` - Configuration export
- `config_imported` - Configuration import

### 5. Roles & Permissions Module
- **Role management**
- **Permission assignments**
- **Access control changes**

**Submodules:**
- `role_created` - New role creation
- `role_updated` - Role modification
- `role_deleted` - Role deletion
- `permission_granted` - Permission granted
- `permission_revoked` - Permission revoked
- `role_assigned` - Role assigned to user
- `role_removed` - Role removed from user

### 6. Security Module
- **Security events**
- **Access violations**
- **API key management**

**Submodules:**
- `access_denied` - Unauthorized access attempts
- `api_key_created` - API key generation
- `api_key_revoked` - API key revocation
- `security_alert` - Security-related alerts
- `suspicious_activity` - Flagged activities

### 7. System Module
- **System-level events**
- **Background processes**
- **Maintenance operations**

**Submodules:**
- `system_startup` - Application startup
- `system_shutdown` - Application shutdown
- `maintenance_started` - Maintenance mode enabled
- `maintenance_ended` - Maintenance mode disabled
- `background_job` - Background job execution
- `log_archived` - Log archival operations

## 🛠️ AuditService Usage

### Basic Usage

```php
use App\Services\AuditService;

class ExampleController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function updateUser(Request $request, User $user)
    {
        // Your business logic here
        $user->update($request->validated());

        // Log the activity
        $this->auditService->logUserManagement(
            'user_updated',
            "User '{$user->name}' profile updated",
            $user,
            [
                'updated_fields' => array_keys($request->validated()),
                'old_email' => $user->getOriginal('email'),
                'new_email' => $user->email
            ]
        );
    }
}
```

### Module-Specific Methods

#### 1. User Management Logging
```php
$this->auditService->logUserManagement(
    string $submodule,
    string $description,
    ?Model $subject = null,
    array $properties = [],
    ?Model $causer = null
);
```

#### 2. Tenant Management Logging
```php
$this->auditService->logTenantManagement(
    string $submodule,
    string $description,
    ?Model $subject = null,
    array $properties = [],
    ?Model $causer = null
);
```

#### 3. Settings Logging
```php
$this->auditService->logSettings(
    string $submodule,
    string $description,
    ?Model $subject = null,
    array $properties = [],
    ?Model $causer = null
);
```

#### 4. Authentication Logging
```php
$this->auditService->logAuthentication(
    string $submodule,
    string $description,
    ?Model $subject = null,
    array $properties = [],
    ?Model $causer = null
);
```

#### 5. Security Logging
```php
$this->auditService->logSecurity(
    string $submodule,
    string $description,
    ?Model $subject = null,
    array $properties = [],
    ?Model $causer = null
);
```

#### 6. Roles & Permissions Logging
```php
$this->auditService->logRolesPermissions(
    string $submodule,
    string $description,
    ?Model $subject = null,
    array $properties = [],
    ?Model $causer = null
);
```

#### 7. System Logging
```php
$this->auditService->logSystem(
    string $submodule,
    string $description,
    ?Model $subject = null,
    array $properties = [],
    ?Model $causer = null
);
```

### Generic Logging
```php
$this->auditService->log(
    string $module,
    string $submodule,
    string $description,
    ?Model $subject = null,
    array $properties = [],
    ?Model $causer = null
);
```

## 🎛️ Admin Interface

### Accessing Audit Logs

1. **URL**: `/admin/audit-logs`
2. **Permission Required**: `audit.view`
3. **Features**:
   - Real-time activity feed
   - Advanced filtering
   - Export capabilities
   - Detailed activity views

### Dashboard Features

#### 📊 Statistics Cards
- **Total Activities**: Overall activity count
- **Today's Activities**: Activities in the last 24 hours
- **Active Users**: Unique users with recent activity
- **Top Module**: Most active module

#### 🔍 Advanced Filtering
- **Module Filter**: Filter by functional area
- **Submodule Filter**: Filter by specific activity type
- **User Filter**: Filter by specific user ID
- **Date Range**: Custom date range selection
- **Subject Type**: Filter by affected model type

#### ⚡ Real-time Features
- **Auto-refresh**: 30-second automatic updates
- **Manual refresh**: On-demand refresh button
- **Live timestamps**: Relative time updates

#### 📤 Export Options
- **CSV Export**: Spreadsheet-compatible format
- **JSON Export**: Machine-readable format
- **Filtered Export**: Export respects current filters
- **Metadata Included**: Full audit trail data

### Activity Detail View

Each audit entry provides comprehensive information:

- **Basic Information**: ID, description, timestamp
- **Module Context**: Module and submodule classification
- **User Information**: Who performed the action
- **Subject Details**: What was affected
- **Change Tracking**: Before/after values for updates
- **Metadata**: IP address, user agent, request details
- **Quick Actions**: Related activity searches

## 🔐 Security & Permissions

### Permission Structure

```php
// Audit-specific permissions
'audit.view'    => 'View audit logs and activity history'
'audit.export'  => 'Export audit logs to CSV or JSON'
'audit.manage'  => 'Manage audit log retention and cleanup'
```

### Role Assignments

- **Super Admin**: Full audit access (view, export, manage)
- **Admin**: View and export audit logs
- **Manager**: View-only access to audit logs
- **User**: No audit access (own activities logged)

### Data Protection

1. **Sensitive Data Filtering**: Personal data is masked or excluded
2. **Retention Policies**: Configurable data retention periods
3. **Access Controls**: Role-based access to audit data
4. **Export Restrictions**: Controlled export capabilities

## 📈 Performance Optimization

### Database Indexes

The system includes optimized indexes for common query patterns:

```sql
-- Optimized for causer-based queries
INDEX activity_log_causer_created_at_index (causer_id, causer_type, created_at)

-- Optimized for subject-based queries  
INDEX activity_log_subject_created_at_index (subject_id, subject_type, created_at)

-- Optimized for module filtering
INDEX activity_log_log_name_index (log_name)

-- Optimized for date range queries
INDEX activity_log_created_at_index (created_at)
```

### Caching Strategy

1. **Statistics Caching**: Activity statistics cached for 5 minutes
2. **Filter Options**: Module/submodule lists cached for 30 minutes
3. **Query Results**: Paginated results cached briefly
4. **User Counts**: Active user counts cached for 10 minutes

### Cleanup Operations

#### Manual Cleanup
```php
// Via admin interface
POST /admin/audit-logs/cleanup
{
    "days": 90  // Delete logs older than 90 days
}
```

#### Automated Cleanup
```bash
# Via artisan command
php artisan activitylog:clean --days=90
```

#### Cleanup Policies
- **Minimum Retention**: 30 days
- **Maximum Retention**: 365 days
- **Default Retention**: 90 days
- **Cleanup Logging**: Cleanup operations are logged

## 🔧 Configuration

### Environment Variables

```env
# Enable/disable activity logging
ACTIVITY_LOGGER_ENABLED=true

# Database connection for audit logs
ACTIVITY_LOGGER_DB_CONNECTION=mysql

# Cache settings
AUDIT_CACHE_ENABLED=true
AUDIT_STATISTICS_CACHE_TTL=300
AUDIT_FILTERS_CACHE_TTL=1800
```

### Module Configuration

Modules and submodules are configured in `config/audit-modules.php`:

```php
return [
    'modules' => [
        'user_management' => [
            'name' => 'User Management',
            'icon' => 'fas fa-users',
            'color' => 'blue',
            'description' => 'User account and profile management',
            'submodules' => [
                'user_created' => [
                    'name' => 'User Created',
                    'description' => 'New user account creation'
                ],
                // ... more submodules
            ]
        ],
        // ... more modules
    ]
];
```

## 🚀 API Integration

### Activity Queries

```php
use App\Http\Controllers\Admin\AuditLogController;

// Get paginated activities
GET /admin/audit-logs/api/activities?module=user_management&per_page=50

// Get activity statistics
GET /admin/audit-logs/api/statistics?start_date=2025-01-01

// Get user-specific activities
GET /admin/audit-logs/users/{userId}
```

### Export API

```php
// Export activities
POST /admin/audit-logs/export
{
    "format": "csv",
    "module": "user_management",
    "start_date": "2025-01-01",
    "end_date": "2025-01-31"
}
```

## 🧪 Testing

### Unit Testing

```php
use App\Services\AuditService;
use Spatie\Activitylog\Models\Activity;

class AuditServiceTest extends TestCase
{
    public function test_logs_user_management_activity()
    {
        $user = User::factory()->create();
        $auditService = app(AuditService::class);
        
        $auditService->logUserManagement(
            'user_created',
            "User '{$user->name}' created",
            $user
        );
        
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user_management',
            'description' => "User '{$user->name}' created",
            'subject_type' => User::class,
            'subject_id' => $user->id
        ]);
    }
}
```

### Integration Testing

```php
class AuditIntegrationTest extends TestCase
{
    public function test_user_creation_generates_audit_log()
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password'
            ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user_management',
            'description' => "User 'Test User' created"
        ]);
    }
}
```

## 🔍 Troubleshooting

### Common Issues

#### 1. No Audit Entries Being Created
**Symptoms**: Activities not appearing in audit logs
**Solutions**:
- Check `ACTIVITY_LOGGER_ENABLED=true` in `.env`
- Verify database connection
- Check model has `LogsActivity` trait
- Ensure user is authenticated

#### 2. Permission Denied on Audit Logs
**Symptoms**: 403 errors when accessing audit interface
**Solutions**:
- Run: `php artisan db:seed --class=AuditPermissionSeeder`
- Verify user has `audit.view` permission
- Check role assignments

#### 3. Performance Issues
**Symptoms**: Slow audit log queries
**Solutions**:
- Review database indexes
- Implement data archival
- Adjust cache settings
- Optimize query filters

#### 4. Missing Configuration
**Symptoms**: Table name errors or missing config
**Solutions**:
- Ensure `config/activitylog.php` exists
- Set `table_name` to `activity_log`
- Clear config cache: `php artisan config:clear`

### Debug Commands

```bash
# Check audit configuration
php artisan config:show activitylog

# View recent activities
php artisan tinker
>>> App\Models\Activity::latest()->take(5)->get()

# Test audit service
php artisan tinker
>>> app(App\Services\AuditService::class)->logSystem('test', 'Debug test')

# Check permissions
php artisan tinker
>>> auth()->user()->can('audit.view')
```

## 📋 Best Practices

### 1. Meaningful Descriptions
```php
// ❌ Poor description
$this->auditService->logUserManagement('user_updated', 'User updated', $user);

// ✅ Good description
$this->auditService->logUserManagement(
    'user_updated', 
    "User '{$user->name}' email changed from '{$oldEmail}' to '{$newEmail}'", 
    $user,
    ['old_email' => $oldEmail, 'new_email' => $newEmail]
);
```

### 2. Appropriate Module Classification
```php
// ❌ Wrong module
$this->auditService->logSystem('user_created', 'User created', $user);

// ✅ Correct module
$this->auditService->logUserManagement('user_created', 'User created', $user);
```

### 3. Comprehensive Metadata
```php
$this->auditService->logUserManagement(
    'tenant_assigned',
    "User '{$user->name}' assigned to tenant '{$tenant->name}'",
    $user,
    [
        'tenant_id' => $tenant->id,
        'tenant_name' => $tenant->name,
        'assigned_by' => auth()->user()->name,
        'assignment_type' => 'manual',
        'permissions_granted' => $permissions
    ]
);
```

### 4. Error Handling
```php
try {
    $user->update($data);
    
    $this->auditService->logUserManagement(
        'user_updated',
        "User '{$user->name}' updated successfully",
        $user,
        ['updated_fields' => array_keys($data)]
    );
} catch (\Exception $e) {
    $this->auditService->logSecurity(
        'update_failed',
        "Failed to update user '{$user->name}': {$e->getMessage()}",
        $user,
        ['error' => $e->getMessage(), 'attempted_data' => $data]
    );
    
    throw $e;
}
```

## 🎯 Summary

The audit logging system provides:

✅ **Complete Activity Tracking** - Every action is logged with context
✅ **Module Organization** - Structured by functional areas  
✅ **Rich Metadata** - IP addresses, user agents, request details
✅ **Real-time Interface** - Live dashboard with filtering and export
✅ **Performance Optimized** - Indexed database with caching
✅ **Security Focused** - Role-based access and data protection
✅ **Developer Friendly** - Simple API with comprehensive documentation

The system ensures complete audit compliance while maintaining excellent performance and usability for administrators and developers.