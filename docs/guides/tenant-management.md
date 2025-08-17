# Tenant Management

## Overview

This SSO system implements a **slug-based multi-tenant architecture** where tenants are identified by URL-friendly slugs. Each tenant represents a separate application or organization that authenticates through the central SSO server.

## Tenant Architecture

### Centralized Authentication Model
- **Central SSO Server**: Single authentication point for all tenants
- **Tenant Applications**: Separate Laravel applications that authenticate via SSO
- **Shared User Database**: All users stored in central database with tenant access control
- **Tenant Isolation**: Users can only access tenants they're explicitly granted access to

### Tenant Identification Strategy
- **Primary**: Slug-based URL routing
- **URL Structure**: `http://localhost:8000/auth/{tenant_slug}`
- **Examples**: 
  - `http://localhost:8000/auth/tenant1` → Tenant 1 (Acme Corporation)
  - `http://localhost:8000/auth/tenant2` → Tenant 2 (Beta Industries)
  - `http://localhost:8000/auth/marketing-team` → Marketing Department

## Tenant Slugs

### What is a Tenant Slug?
A **slug** is a URL-friendly identifier that:
- Contains only lowercase letters, numbers, and hyphens
- Is human-readable and SEO-friendly
- Provides better user experience than numeric IDs
- Enables branded URLs for each tenant

### Slug Examples:
| Tenant Name | Generated Slug | URL |
|-------------|----------------|-----|
| Acme Corporation | `acme-corporation` | `/auth/acme-corporation` |
| Tech Solutions Inc. | `tech-solutions-inc` | `/auth/tech-solutions-inc` |
| Marketing Department | `marketing-dept` | `/auth/marketing-dept` |
| Beta Industries | `beta-industries` | `/auth/beta-industries` |

### Benefits of Using Slugs:
1. **Clean URLs**: `/auth/acme-corp` instead of `/auth/123`
2. **User Experience**: Users can understand which tenant they're accessing
3. **Branding**: Tenants can have branded URLs matching their identity
4. **SEO Benefits**: Search engines prefer descriptive URLs
5. **Debugging**: Easier to identify tenants in logs and development
6. **API Clarity**: RESTful endpoints are more intuitive

## Database Schema

### Tenants Table Structure
```sql
CREATE TABLE tenants (
    id VARCHAR(255) PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,  -- URL-friendly identifier
    name VARCHAR(255) NOT NULL,         -- Display name
    domain VARCHAR(255),                -- Associated domain
    description TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Example data
INSERT INTO tenants VALUES 
('tenant1', 'tenant1', 'Acme Corporation', 'localhost:8001', 'Main tenant for Acme Corp', 1, NOW(), NOW()),
('tenant2', 'tenant2', 'Beta Industries', 'localhost:8002', 'Beta Industries tenant', 1, NOW(), NOW());
```

### User-Tenant Relationship Table
```sql
CREATE TABLE tenant_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tenant (user_id, tenant_id)
);
```

### Database Configuration
The system uses a **single shared database** for all SSO-related data:

```env
# Central SSO Database
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=sso_main
DB_USERNAME=sso_user
DB_PASSWORD=sso_password
```

## Tenant Model

### Tenant Model (`app/Models/Tenant.php`)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'slug',
        'name',
        'domain',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all users that have access to this tenant
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_users')
                    ->withTimestamps();
    }

    /**
     * Check if a user has access to this tenant
     */
    public function hasUser($userId)
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    /**
     * Get the route key for the model (use slug for routing)
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
```

### User Model with Tenant Relationships (`app/Models/User.php`)
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'tenants' => $this->tenants->pluck('slug')->toArray(),
        ];
    }

    /**
     * Get all tenants this user has access to
     */
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
                    ->withTimestamps();
    }

    /**
     * Check if user has access to a specific tenant
     */
    public function hasAccessToTenant($tenantSlug)
    {
        return $this->tenants->contains('slug', $tenantSlug);
    }

    /**
     * Get user's accessible tenant slugs
     */
    public function getTenantSlugs()
    {
        return $this->tenants->pluck('slug')->toArray();
    }
}
```

## Tenant Operations

### Creating Tenants

#### Via Database SQL
```sql
-- Insert new tenant
INSERT INTO tenants (id, slug, name, domain, description, is_active, created_at, updated_at) 
VALUES ('new-tenant', 'new-tenant', 'New Tenant Name', 'localhost:8003', 'Description', 1, NOW(), NOW());
```

#### Via Code
```php
use App\Models\Tenant;

$tenant = Tenant::create([
    'id' => 'new-tenant',
    'slug' => 'new-tenant',
    'name' => 'New Tenant Name',
    'domain' => 'localhost:8003',
    'description' => 'Description of the new tenant',
    'is_active' => true,
]);
```

#### Via Admin Dashboard
1. Login to `http://localhost:8000/login`
2. Navigate to "Tenants" section in admin dashboard
3. Click "Create New Tenant"
4. Fill in tenant details:
   - **ID**: Unique identifier (usually same as slug)
   - **Slug**: URL-friendly identifier (no spaces, lowercase)
   - **Name**: Display name for the tenant
   - **Domain**: Full domain with port (e.g., `localhost:8003`)
   - **Description**: Brief description of the tenant

### SSO URL Structure
After creating a tenant, the SSO authentication URL will be:
```
http://localhost:8000/auth/{tenant_slug}
```

Examples:
- `http://localhost:8000/auth/new-tenant`
- `http://localhost:8000/auth/acme-corp`
- `http://localhost:8000/auth/marketing-team`

### Database Operations

#### Central Database Migration
```bash
# Run migrations for central SSO database
docker exec central-sso php artisan migrate
```

#### Viewing Tenants
```sql
-- Connect to database
docker exec -it mariadb mysql -u sso_user -psso_password sso_main

-- View all tenants
SELECT id, slug, name, domain, is_active FROM tenants;

-- Check tenant-user relationships
SELECT t.name as tenant_name, u.name as user_name, u.email 
FROM tenants t
JOIN tenant_users tu ON t.id = tu.tenant_id
JOIN users u ON tu.user_id = u.id
ORDER BY t.name, u.name;
```

### User-Tenant Access Management

#### Assigning Users to Tenants
```php
$user = User::find(1);
$tenant = Tenant::where('slug', 'new-tenant')->first();

// Assign user to tenant
$user->tenants()->attach($tenant);

// Or via SQL
// INSERT INTO tenant_users (user_id, tenant_id, created_at, updated_at) 
// VALUES (1, 'new-tenant', NOW(), NOW());
```

#### Checking User Access
```php
$user = User::find(1);

// Check if user has access to a tenant
if ($user->hasAccessToTenant('tenant1')) {
    // User can access tenant1
    echo "Access granted";
} else {
    echo "Access denied";
}

// Get all tenants user can access
$accessibleTenants = $user->tenants;
```

#### Removing User Access
```php
$user = User::find(1);
$tenant = Tenant::where('slug', 'tenant1')->first();

// Remove user from tenant
$user->tenants()->detach($tenant);

// Or via SQL
// DELETE FROM tenant_users WHERE user_id = 1 AND tenant_id = 'tenant1';
```

#### Multi-Tenant User Management
```php
// Get user's accessible tenants for JWT
$userTenants = auth()->user()->tenants;

// Create JWT token with tenant claims
$customClaims = [
    'tenants' => $userTenants->pluck('slug')->toArray(),
    'current_tenant' => 'tenant1',
];

$token = JWTAuth::customClaims($customClaims)->fromUser($user);
```

## SSO Authentication Flow

### How Tenant Authentication Works

1. **User visits tenant app**: `http://localhost:8001`
2. **Clicks "Login with SSO"**: Redirects to central SSO
3. **SSO URL**: `http://localhost:8000/auth/tenant1?callback_url=http://localhost:8001/sso/callback`
4. **Authentication check**: JavaScript checks if user is already logged in
5. **Login or redirect**: Either shows login form or automatically redirects back
6. **Token generation**: Creates JWT with tenant-specific claims
7. **Callback**: Returns to tenant app with authentication token

### SSO Controller Logic
```php
// In SSOController::showLoginForm()
public function showLoginForm($tenant_slug, Request $request)
{
    $tenant = Tenant::where('slug', $tenant_slug)->first();
    
    if (!$tenant) {
        abort(404, 'Tenant not found');
    }
    
    return view('auth.sso-processing', [
        'tenant' => $tenant,
        'tenant_slug' => $tenant_slug,
        'callback_url' => $request->get('callback_url')
    ]);
}
```

## Common Issues & Troubleshooting

### Missing Slug Error (404)
**Problem**: SSO endpoints return 404 when accessing `/auth/tenant1`

**Cause**: Tenant records have `NULL` slug values

**Solution**:
```sql
-- Check for missing slugs
SELECT id, slug, name FROM tenants WHERE slug IS NULL;

-- Fix missing slugs
UPDATE tenants SET slug = id WHERE slug IS NULL;

-- Verify fix
SELECT id, slug, name FROM tenants;
```

### Tenant Not Found
**Problem**: "Tenant not found" error in logs

**Debug Steps**:
```php
// Check if tenant exists
$tenant = Tenant::where('slug', $tenantSlug)->first();
if (!$tenant) {
    Log::error('Tenant not found', ['slug' => $tenantSlug]);
    abort(404, 'Tenant not found');
}
```

### User Access Denied
**Problem**: User gets "Access Denied" message

**Debug Steps**:
```sql
-- Check user-tenant relationships
SELECT u.email, t.name as tenant_name, t.slug 
FROM users u
JOIN tenant_users tu ON u.id = tu.user_id
JOIN tenants t ON tu.tenant_id = t.id
WHERE u.email = 'user@example.com';
```

### Invalid Domain Configuration
**Problem**: Tenant app can't connect to central SSO

**Check**:
```sql
-- Verify tenant domain configuration
SELECT slug, name, domain FROM tenants;
```

**Fix**: Update domain if incorrect
```sql
UPDATE tenants SET domain = 'localhost:8001' WHERE slug = 'tenant1';
```

## Monitoring and Maintenance

### Health Checks
```bash
# Check central SSO status
curl http://localhost:8000/health

# Check tenant applications
curl http://localhost:8001
curl http://localhost:8002

# Test SSO endpoint
curl "http://localhost:8000/auth/tenant1?callback_url=http://localhost:8001/sso/callback"
```

### Database Maintenance
```bash
# Backup central database
docker exec mariadb mysqldump -u sso_user -psso_password sso_main > sso_backup.sql

# View tenant statistics
docker exec -it mariadb mysql -u sso_user -psso_password sso_main -e "
SELECT 
    t.name,
    t.slug,
    t.is_active,
    COUNT(tu.user_id) as user_count
FROM tenants t
LEFT JOIN tenant_users tu ON t.id = tu.tenant_id
GROUP BY t.id, t.name, t.slug, t.is_active
ORDER BY user_count DESC;"
```

### Performance Monitoring
```bash
# Monitor login activity
docker exec -it mariadb mysql -u sso_user -psso_password sso_main -e "
SELECT 
    DATE(login_at) as date,
    tenant_id,
    login_method,
    COUNT(*) as login_count
FROM login_audits 
WHERE login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(login_at), tenant_id, login_method
ORDER BY date DESC, login_count DESC;"
```