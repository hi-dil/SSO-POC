# Testing Guide

## Testing Overview

This guide covers testing strategies for the multi-tenant SSO system, including unit tests, integration tests, and end-to-end testing scenarios.

## Test Environment Setup

### Prerequisites
```bash
# Ensure test environment is running
docker-compose up -d

# Run tests inside containers
docker-compose exec central-sso php artisan test
docker-compose exec tenant1-app php artisan test
```

### Test Databases
Each service uses separate test databases:
- Central SSO: `sso_main_test`
- Tenant 1: `tenant1_db_test`
- Tenant 2: `tenant2_db_test`

### Environment Configuration
```env
# .env.testing for central SSO
APP_ENV=testing
DB_CONNECTION=mysql_testing
DB_DATABASE=sso_main_test

# .env.testing for tenant apps
APP_ENV=testing
DB_CONNECTION=mysql_testing
DB_DATABASE=tenant1_db_test
```

## Testing Scenarios

### 1. Authentication Flow Testing

#### SSO Redirect Flow Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;

class SSORedirectFlowTest extends TestCase
{
    public function test_user_can_login_via_sso_redirect()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['slug' => 'test-tenant']);
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        
        // Act - Start SSO flow
        $response = $this->get("/auth/{$tenant->slug}?callback_url=http://test.localhost/callback");
        
        // Assert - Redirected to login page
        $response->assertStatus(200);
        $response->assertSee('Login');
        
        // Act - Submit login
        $response = $this->post("/auth/{$tenant->slug}", [
            'email' => $user->email,
            'password' => 'password',
            'callback_url' => 'http://test.localhost/callback'
        ]);
        
        // Assert - Redirected with token
        $response->assertRedirect();
        $this->assertStringContainsString('token=', $response->headers->get('Location'));
    }
}
```

#### Local Form Authentication Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;

class LocalAuthTest extends TestCase
{
    public function test_user_can_login_via_api()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['slug' => 'test-tenant']);
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        
        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'tenant_slug' => $tenant->slug
        ]);
        
        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'token',
            'user' => ['id', 'email', 'name', 'tenants']
        ]);
        
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('token'));
    }
    
    public function test_user_cannot_login_to_unauthorized_tenant()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['slug' => 'unauthorized-tenant']);
        $user = User::factory()->create();
        // Note: User not attached to tenant
        
        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'tenant_slug' => $tenant->slug
        ]);
        
        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Access denied to tenant'
        ]);
    }
}
```

### 2. User Registration Testing

#### Central Registration Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;

class CentralRegistrationTest extends TestCase
{
    public function test_user_can_register_with_tenant_selection()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['slug' => 'selected-tenant']);
        
        // Act
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tenant_slug' => $tenant->slug
        ]);
        
        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'token',
            'user' => ['id', 'email', 'name', 'tenants']
        ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com'
        ]);
        
        $this->assertDatabaseHas('tenant_users', [
            'tenant_id' => $tenant->id
        ]);
    }
}
```

#### Client App Registration Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class ClientRegistrationTest extends TestCase
{
    public function test_user_can_register_on_client_app()
    {
        // Mock Central SSO API response
        Http::fake([
            'central-sso:8000/api/auth/register' => Http::response([
                'success' => true,
                'token' => 'mock-jwt-token',
                'user' => [
                    'id' => 1,
                    'email' => 'john@example.com',
                    'name' => 'John Doe',
                    'tenants' => ['tenant1']
                ]
            ], 201)
        ]);
        
        // Act
        $response = $this->post('/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        
        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user = auth()->user());
        
        Http::assertSent(function ($request) {
            return $request->url() === 'http://central-sso:8000/api/auth/register' &&
                   $request['tenant_slug'] === 'tenant1';
        });
    }
}
```

### 3. JWT Token Testing

#### Token Validation Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTTokenTest extends TestCase
{
    public function test_jwt_token_contains_tenant_information()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['slug' => 'test-tenant']);
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        
        // Act
        $token = JWTAuth::claims([
            'tenants' => ['test-tenant'],
            'current_tenant' => 'test-tenant'
        ])->fromUser($user);
        
        // Assert
        $payload = JWTAuth::setToken($token)->getPayload();
        $this->assertEquals(['test-tenant'], $payload->get('tenants'));
        $this->assertEquals('test-tenant', $payload->get('current_tenant'));
    }
    
    public function test_expired_token_is_rejected()
    {
        // Arrange
        $user = User::factory()->create();
        $token = JWTAuth::customClaims(['exp' => time() - 3600])->fromUser($user);
        
        // Act & Assert
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenExpiredException::class);
        JWTAuth::setToken($token)->authenticate();
    }
}
```

### 4. Multi-Tenancy Testing

#### Tenant Isolation Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use Stancl\Tenancy\Facades\Tenancy;

class TenantIsolationTest extends TestCase
{
    public function test_tenant_data_is_isolated()
    {
        // Arrange
        $tenant1 = Tenant::factory()->create(['slug' => 'tenant1']);
        $tenant2 = Tenant::factory()->create(['slug' => 'tenant2']);
        
        // Act - Create user in tenant1
        Tenancy::initialize($tenant1);
        $user1 = \App\Models\TenantUser::create([
            'central_user_id' => 1,
            'name' => 'User One',
            'email' => 'user1@tenant1.com'
        ]);
        
        // Switch to tenant2
        Tenancy::initialize($tenant2);
        $users = \App\Models\TenantUser::all();
        
        // Assert - tenant2 should not see tenant1's users
        $this->assertCount(0, $users);
        $this->assertDatabaseMissing('users', [
            'email' => 'user1@tenant1.com'
        ]);
    }
}
```

#### Tenant Switching Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;

class TenantSwitchingTest extends TestCase
{
    public function test_user_can_switch_between_tenants()
    {
        // Arrange
        $tenant1 = Tenant::factory()->create(['slug' => 'tenant1']);
        $tenant2 = Tenant::factory()->create(['slug' => 'tenant2']);
        $user = User::factory()->create();
        $user->tenants()->attach([$tenant1->id, $tenant2->id]);
        
        // Act - Login to tenant1
        $response1 = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'tenant_slug' => 'tenant1'
        ]);
        
        $token1 = $response1->json('token');
        
        // Act - Switch to tenant2
        $response2 = $this->withHeaders(['Authorization' => "Bearer $token1"])
            ->postJson('/api/auth/switch-tenant', [
                'tenant_slug' => 'tenant2'
            ]);
        
        // Assert
        $response2->assertStatus(200);
        $newToken = $response2->json('token');
        $this->assertNotEquals($token1, $newToken);
        
        // Verify new token has correct tenant
        $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($newToken)->getPayload();
        $this->assertEquals('tenant2', $payload->get('current_tenant'));
    }
}
```

### 5. Admin Dashboard Testing

#### Tenant Management Test
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;

class AdminDashboardTest extends TestCase
{
    public function test_admin_can_create_tenant()
    {
        // Arrange
        $admin = User::factory()->create(['is_admin' => true]);
        
        // Act
        $response = $this->actingAs($admin)
            ->post('/dashboard/tenants', [
                'name' => 'New Tenant',
                'slug' => 'new-tenant',
                'domain' => 'new-tenant.localhost:8003'
            ]);
        
        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('tenants', [
            'slug' => 'new-tenant',
            'name' => 'New Tenant'
        ]);
        
        // Verify tenant database was created
        $tenant = Tenant::where('slug', 'new-tenant')->first();
        $this->assertNotNull($tenant);
    }
    
    public function test_non_admin_cannot_access_dashboard()
    {
        // Arrange
        $user = User::factory()->create(['is_admin' => false]);
        
        // Act
        $response = $this->actingAs($user)->get('/dashboard');
        
        // Assert
        $response->assertStatus(403);
    }
}
```

## Manual Testing Scenarios

### End-to-End SSO Flow

1. **Setup**: Start all services with `docker-compose up -d`

2. **Create Test User**:
   ```bash
   docker-compose exec central-sso php artisan tinker
   ```
   ```php
   $user = User::create([
       'name' => 'Test User',
       'email' => 'test@example.com',
       'password' => bcrypt('password')
   ]);
   
   $tenant = Tenant::where('slug', 'tenant1')->first();
   $user->tenants()->attach($tenant);
   ```

3. **Test SSO Redirect Flow**:
   - Visit `http://tenant1.localhost:8001`
   - Click "Login with SSO"
   - Should redirect to `http://sso.localhost:8000/auth/tenant1`
   - Login with test credentials
   - Should redirect back to tenant1 with JWT token
   - Should be logged into tenant1

4. **Test Local Form Flow**:
   - Visit `http://tenant2.localhost:8002`
   - Fill login form directly
   - Should authenticate via API call to central SSO
   - Should be logged into tenant2

5. **Test Registration**:
   - Visit `http://sso.localhost:8000/register`
   - Register with tenant selection
   - Visit `http://tenant1.localhost:8001/register`
   - Register with auto-tenant detection

### Performance Testing

#### Load Testing with Artillery
```yaml
# artillery-config.yml
config:
  target: 'http://sso.localhost:8000'
  phases:
    - duration: 60
      arrivalRate: 10
scenarios:
  - name: "Login Flow"
    flow:
      - post:
          url: "/api/auth/login"
          json:
            email: "test@example.com"
            password: "password"
            tenant_slug: "tenant1"
```

```bash
# Run load test
npx artillery run artillery-config.yml
```

#### Database Performance Testing
```bash
# Test tenant database creation time
time docker-compose exec central-sso php artisan tenants:create --name="Performance Test" --slug="perf-test"

# Test multi-tenant queries
docker-compose exec central-sso php artisan tinker
```
```php
// Test tenant switching performance
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $tenant = Tenant::inRandomOrder()->first();
    tenancy()->initialize($tenant);
    \App\Models\User::count();
}
$end = microtime(true);
echo "Time: " . ($end - $start) . " seconds\n";
```

## Test Data Factories

### User Factory
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'is_admin' => false,
        ];
    }

    public function admin()
    {
        return $this->state(['is_admin' => true]);
    }
}
```

### Tenant Factory
```php
<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition()
    {
        $slug = $this->faker->unique()->slug();
        
        return [
            'name' => $this->faker->company(),
            'slug' => $slug,
            'domain' => "{$slug}.localhost:800" . $this->faker->randomDigit(),
            'data' => [
                'plan' => $this->faker->randomElement(['basic', 'premium']),
                'features' => [
                    'analytics' => $this->faker->boolean(),
                    'api' => $this->faker->boolean(),
                ]
            ],
        ];
    }
}
```

## Continuous Integration

### GitHub Actions Workflow
```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mariadb:10.9
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: sso_main_test
        ports:
          - 3306:3306
        options: --health-cmd="healthcheck.sh --connect --innodb_initialized" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, pdo, pdo_mysql
          
      - name: Install Dependencies
        run: |
          cd central-sso
          composer install --no-progress --prefer-dist --optimize-autoloader
          
      - name: Run Tests
        run: |
          cd central-sso
          php artisan test --parallel
          
      - name: Run Client App Tests
        run: |
          cd tenant1-app
          composer install --no-progress --prefer-dist --optimize-autoloader
          php artisan test
```