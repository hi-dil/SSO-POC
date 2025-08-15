# Tenant Management

## Overview

This project uses the [Stancl/Tenancy](https://tenancyforlaravel.com/) package to implement multi-tenancy with separate databases per tenant. This document explains how tenancy works in our SSO system.

## Tenancy Strategy

### Database-per-Tenant Model
- Each tenant has its own isolated database
- Complete data separation between tenants
- Scalable architecture for growth
- Easy backup/restore per tenant

### Tenant Identification
- **Primary**: Subdomain-based routing
- **Examples**: 
  - `tenant1.localhost:8001` → Tenant 1
  - `tenant2.localhost:8002` → Tenant 2

## Stancl/Tenancy Configuration

### Package Installation
```bash
composer require stancl/tenancy
php artisan tenancy:install
```

### Configuration Files

#### `config/tenancy.php`
```php
<?php

return [
    'tenant_model' => \App\Models\Tenant::class,
    'id_generator' => \Stancl\Tenancy\UuidGenerator::class,
    
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,
        'prefix' => '',
        'suffix' => '_db',
    ],
    
    'cache' => [
        'tag_base' => 'tenant',
    ],
    
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => ['local', 'public'],
    ],
];
```

#### `config/database.php` (Tenant Configuration)
```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        // ... other config
    ],
    
    'tenant' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => null, // Will be set dynamically
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        // ... other config
    ],
],
```

## Tenant Model

### Central Database Model (`app/Models/Tenant.php`)
```php
<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'domain',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'tenant_users')
                    ->withTimestamps();
    }
}
```

### User-Tenant Relationship (`app/Models/User.php`)
```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
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

    // Relationships
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
                    ->withTimestamps();
    }

    public function hasAccessToTenant($tenantSlug)
    {
        return $this->tenants->contains('slug', $tenantSlug);
    }
}
```

## Tenant Operations

### Creating Tenants

#### Via Artisan Command
```bash
php artisan tenants:create --name="New Tenant" --slug="new-tenant" --domain="new-tenant.localhost"
```

#### Via Code
```php
use App\Models\Tenant;

$tenant = Tenant::create([
    'name' => 'New Tenant',
    'slug' => 'new-tenant',
    'domain' => 'new-tenant.localhost:8003',
    'data' => [
        'plan' => 'basic',
        'settings' => [
            'theme' => 'default',
        ],
    ],
]);

// This automatically creates the tenant database
```

#### Via Admin Dashboard
1. Login to `http://sso.localhost:8000/dashboard`
2. Navigate to "Tenants" section
3. Click "Create New Tenant"
4. Fill in tenant details:
   - Name: Display name for the tenant
   - Slug: URL-friendly identifier
   - Domain: Full domain with port

### Database Operations

#### Running Migrations
```bash
# Migrate central database
php artisan migrate

# Migrate all tenant databases
php artisan tenants:migrate

# Migrate specific tenant
php artisan tenants:migrate --tenants=tenant1
```

#### Seeding Data
```bash
# Seed all tenant databases
php artisan tenants:seed

# Seed specific tenant
php artisan tenants:seed --tenants=tenant1 --class=UserSeeder
```

### Switching Tenant Context

#### Manual Context Switching
```php
use App\Models\Tenant;

$tenant = Tenant::where('slug', 'tenant1')->first();

tenancy()->initialize($tenant);

// Now all database operations use tenant1_db
User::all(); // Returns users from tenant1_db
```

#### Middleware-based Switching
```php
// In routes/web.php or api.php
Route::middleware(['tenant'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

### User-Tenant Management

#### Assigning Users to Tenants
```php
$user = User::find(1);
$tenant = Tenant::where('slug', 'tenant1')->first();

// Assign user to tenant
$user->tenants()->attach($tenant);

// Check if user has access
if ($user->hasAccessToTenant('tenant1')) {
    // User can access tenant1
}
```

#### Multi-Tenant User Flow
```php
// Get user's accessible tenants
$userTenants = auth()->user()->tenants;

// Switch to specific tenant in JWT
$jwt = auth()->claims([
    'tenants' => $userTenants->pluck('slug')->toArray(),
    'current_tenant' => 'tenant1',
])->attempt($credentials);
```

## Tenant-Specific Configuration

### Environment Variables per Tenant
```php
// In tenant database or config
$tenant = tenancy()->tenant;

$settings = $tenant->data['settings'] ?? [];

config([
    'app.name' => $tenant->name,
    'app.url' => "http://{$tenant->domain}",
    'mail.from.name' => $tenant->name,
]);
```

### Custom Tenant Features
```php
// Enable/disable features per tenant
class TenantFeatureMiddleware
{
    public function handle($request, $next, $feature)
    {
        $tenant = tenancy()->tenant;
        
        if (!$tenant->data['features'][$feature] ?? false) {
            abort(404, 'Feature not available');
        }
        
        return $next($request);
    }
}
```

## Monitoring and Maintenance

### Tenant Database Sizes
```bash
# Check database sizes
php artisan tenants:run db:show --option=--database --option=--extended
```

### Backup Strategies
```bash
# Backup specific tenant
mysqldump -u user -p tenant1_db > tenant1_backup.sql

# Backup all tenant databases
php artisan tenants:artisan "db:backup"
```

### Performance Considerations

1. **Connection Pooling**: Use connection pooling for better performance
2. **Database Indexing**: Ensure proper indexing in tenant databases
3. **Cache Separation**: Use tenant-specific cache keys
4. **File Storage**: Separate file storage per tenant

### Troubleshooting

#### Common Issues

**Tenant Not Found**
```php
// Check if tenant exists
$tenant = Tenant::where('slug', $slug)->first();
if (!$tenant) {
    abort(404, 'Tenant not found');
}
```

**Database Connection Issues**
```bash
# Test tenant database connection
php artisan tenants:run "migrate:status" --tenants=tenant1
```

**Cache Issues**
```bash
# Clear tenant-specific cache
php artisan tenants:artisan "cache:clear"
```