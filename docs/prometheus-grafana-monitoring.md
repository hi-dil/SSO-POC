# Prometheus & Grafana Monitoring Implementation Guide

## Overview

This guide provides step-by-step instructions for implementing comprehensive monitoring and observability for the multi-tenant SSO system using Prometheus, Grafana, and Spatie's Laravel Prometheus package.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                   Monitoring Architecture                   │
├─────────────────────────────────────────────────────────────┤
│ Central SSO App ──→ Laravel Prometheus ──→ Prometheus      │
│ Tenant 1 App   ──→ Laravel Prometheus ──→ Prometheus      │
│ Tenant 2 App   ──→ Laravel Prometheus ──→ Prometheus      │
│ Cloudflared     ──→ Built-in Metrics  ──→ Prometheus      │
│ MariaDB         ──→ MySQL Exporter    ──→ Prometheus      │
│ Redis           ──→ Redis Exporter    ──→ Prometheus      │
│                                            │                │
│ Prometheus ──────────────────────────────→ Grafana        │
│                                            │                │
│ Grafana ──→ Dashboards ──→ Alerts ──→ Notifications       │
└─────────────────────────────────────────────────────────────┘
```

## Prerequisites

- Docker and Docker Compose
- Laravel applications (Central SSO + Tenants)
- Basic understanding of Prometheus/Grafana concepts

## Installation & Setup

### Step 1: Install Spatie Laravel Prometheus

#### For Central SSO Application

```bash
cd central-sso

# Install the package
composer require spatie/laravel-prometheus

# Publish the configuration
php artisan vendor:publish --tag="prometheus-config"

# Publish the migration (optional, for database storage)
php artisan vendor:publish --tag="prometheus-migrations"
php artisan migrate
```

#### For Tenant Applications

```bash
# Install in tenant1-app
cd tenant1-app
composer require spatie/laravel-prometheus
php artisan vendor:publish --tag="prometheus-config"

# Install in tenant2-app  
cd tenant2-app
composer require spatie/laravel-prometheus
php artisan vendor:publish --tag="prometheus-config"
```

### Step 2: Configure Laravel Prometheus

#### Central SSO Configuration (`central-sso/config/prometheus.php`)

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prometheus Metrics Route
    |--------------------------------------------------------------------------
    */
    'route_path' => env('PROMETHEUS_ROUTE_PATH', 'metrics'),
    'route_middleware' => explode(',', env('PROMETHEUS_ROUTE_MIDDLEWARE', '')),

    /*
    |--------------------------------------------------------------------------
    | Metrics Storage
    |--------------------------------------------------------------------------
    */
    'storage_adapter' => env('PROMETHEUS_STORAGE_ADAPTER', 'memory'),
    
    'storage_adapters' => [
        'memory' => [
            'class' => \Prometheus\Storage\InMemory::class,
        ],
        'redis' => [
            'class' => \Prometheus\Storage\Redis::class,
            'options' => [
                'host' => env('REDIS_HOST', '127.0.0.1'),
                'port' => env('REDIS_PORT', 6379),
                'password' => env('REDIS_PASSWORD'),
                'database' => env('PROMETHEUS_REDIS_DATABASE', 2),
                'timeout' => 0.1,
                'read_timeout' => 10,
                'persistent_connections' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Collectors Configuration
    |--------------------------------------------------------------------------
    */
    'collectors' => [
        // HTTP Request metrics
        \Spatie\Prometheus\Collectors\Horizon\CurrentMasterSupervisorCollector::class => [
            'enabled' => false, // We're not using Horizon
        ],
        
        // Custom collectors (we'll create these)
        \App\Prometheus\Collectors\SSOMetricsCollector::class => [
            'enabled' => true,
        ],
        \App\Prometheus\Collectors\AuthenticationMetricsCollector::class => [
            'enabled' => true,
        ],
        \App\Prometheus\Collectors\DatabaseMetricsCollector::class => [
            'enabled' => true,
        ],
        \App\Prometheus\Collectors\TenantMetricsCollector::class => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'enabled' => env('PROMETHEUS_MIDDLEWARE_ENABLED', true),
        'requests' => [
            'enabled' => true,
            'counter_name' => 'sso_http_requests_total',
            'histogram_name' => 'sso_http_request_duration_seconds',
            'buckets' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Labels
    |--------------------------------------------------------------------------
    */
    'labels' => [
        'app_name' => env('APP_NAME', 'central-sso'),
        'app_version' => env('APP_VERSION', '1.0.0'),
        'environment' => env('APP_ENV', 'production'),
        'tenant' => env('TENANT_SLUG', 'central'),
    ],
];
```

#### Tenant Applications Configuration

Similar configuration but with tenant-specific labels:

```php
// tenant1-app/config/prometheus.php
'labels' => [
    'app_name' => env('APP_NAME', 'tenant1-app'),
    'app_version' => env('APP_VERSION', '1.0.0'),
    'environment' => env('APP_ENV', 'production'),
    'tenant' => env('TENANT_SLUG', 'tenant1'),
],

// tenant2-app/config/prometheus.php  
'labels' => [
    'app_name' => env('APP_NAME', 'tenant2-app'),
    'app_version' => env('APP_VERSION', '1.0.0'),
    'environment' => env('APP_ENV', 'production'),
    'tenant' => env('TENANT_SLUG', 'tenant2'),
],
```

### Step 3: Create Custom Metrics Collectors

#### SSO Metrics Collector (`central-sso/app/Prometheus/Collectors/SSOMetricsCollector.php`)

```php
<?php

namespace App\Prometheus\Collectors;

use Spatie\Prometheus\Collectors\Collector;
use Prometheus\CollectorRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SSOMetricsCollector implements Collector
{
    protected CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function register(): void
    {
        // Total users across all tenants
        $this->registry->getOrRegisterGauge(
            'sso_total_users',
            'Total number of users in the SSO system',
            ['tenant']
        );

        // Active sessions
        $this->registry->getOrRegisterGauge(
            'sso_active_sessions',
            'Number of active user sessions',
            ['tenant']
        );

        // Login attempts (counter)
        $this->registry->getOrRegisterCounter(
            'sso_login_attempts_total',
            'Total number of login attempts',
            ['tenant', 'status', 'method']
        );

        // JWT tokens issued
        $this->registry->getOrRegisterCounter(
            'sso_jwt_tokens_issued_total',
            'Total number of JWT tokens issued',
            ['tenant', 'type']
        );

        // Cross-tenant access attempts
        $this->registry->getOrRegisterCounter(
            'sso_cross_tenant_access_total',
            'Cross-tenant access attempts',
            ['source_tenant', 'target_tenant', 'status']
        );
    }

    public function collect(): void
    {
        // Collect total users per tenant
        $tenantUsers = DB::table('tenant_users')
            ->join('tenants', 'tenant_users.tenant_id', '=', 'tenants.id')
            ->select('tenants.slug', DB::raw('count(*) as user_count'))
            ->groupBy('tenants.slug')
            ->get();

        foreach ($tenantUsers as $tenant) {
            $this->registry->getGauge('sso_total_users', ['tenant'])
                ->set($tenant->user_count, [$tenant->slug]);
        }

        // Collect active sessions
        $activeSessions = Cache::remember('prometheus_active_sessions', 60, function () {
            return DB::table('sessions')
                ->where('last_activity', '>', now()->subMinutes(30)->timestamp)
                ->count();
        });

        $this->registry->getGauge('sso_active_sessions', ['tenant'])
            ->set($activeSessions, ['all']);

        // Collect recent login metrics (last hour)
        $recentLogins = DB::table('login_audits')
            ->where('created_at', '>', now()->subHour())
            ->selectRaw('
                tenant_slug,
                login_method,
                CASE WHEN success = 1 THEN "success" ELSE "failure" END as status,
                count(*) as count
            ')
            ->groupBy('tenant_slug', 'login_method', 'success')
            ->get();

        foreach ($recentLogins as $login) {
            $this->registry->getCounter('sso_login_attempts_total', ['tenant', 'status', 'method'])
                ->incBy($login->count, [$login->tenant_slug, $login->status, $login->login_method]);
        }
    }
}
```

#### Authentication Metrics Collector (`central-sso/app/Prometheus/Collectors/AuthenticationMetricsCollector.php`)

```php
<?php

namespace App\Prometheus\Collectors;

use Spatie\Prometheus\Collectors\Collector;
use Prometheus\CollectorRegistry;
use Illuminate\Support\Facades\DB;

class AuthenticationMetricsCollector implements Collector
{
    protected CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function register(): void
    {
        // Authentication response times
        $this->registry->getOrRegisterHistogram(
            'sso_auth_duration_seconds',
            'Authentication request duration in seconds',
            ['tenant', 'method', 'status'],
            [0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]
        );

        // Failed login attempts by IP
        $this->registry->getOrRegisterCounter(
            'sso_failed_logins_by_ip_total',
            'Failed login attempts grouped by IP address',
            ['ip_address', 'tenant']
        );

        // Password reset requests
        $this->registry->getOrRegisterCounter(
            'sso_password_reset_requests_total',
            'Password reset requests',
            ['tenant', 'status']
        );

        // Two-factor authentication metrics
        $this->registry->getOrRegisterCounter(
            'sso_2fa_attempts_total',
            'Two-factor authentication attempts',
            ['tenant', 'method', 'status']
        );

        // API authentication metrics
        $this->registry->getOrRegisterCounter(
            'sso_api_auth_total',
            'API authentication attempts',
            ['tenant', 'endpoint', 'status']
        );
    }

    public function collect(): void
    {
        // Collect failed login attempts by IP (last 24 hours)
        $failedLogins = DB::table('login_audits')
            ->where('created_at', '>', now()->subDay())
            ->where('success', false)
            ->selectRaw('ip_address, tenant_slug, count(*) as count')
            ->groupBy('ip_address', 'tenant_slug')
            ->get();

        foreach ($failedLogins as $failure) {
            $this->registry->getCounter('sso_failed_logins_by_ip_total', ['ip_address', 'tenant'])
                ->incBy($failure->count, [$failure->ip_address, $failure->tenant_slug]);
        }

        // Collect password reset metrics (last hour)
        $passwordResets = DB::table('password_reset_tokens')
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($passwordResets > 0) {
            $this->registry->getCounter('sso_password_reset_requests_total', ['tenant', 'status'])
                ->incBy($passwordResets, ['all', 'requested']);
        }
    }
}
```

#### Database Metrics Collector (`central-sso/app/Prometheus/Collectors/DatabaseMetricsCollector.php`)

```php
<?php

namespace App\Prometheus\Collectors;

use Spatie\Prometheus\Collectors\Collector;
use Prometheus\CollectorRegistry;
use Illuminate\Support\Facades\DB;

class DatabaseMetricsCollector implements Collector
{
    protected CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function register(): void
    {
        // Database query duration
        $this->registry->getOrRegisterHistogram(
            'sso_db_query_duration_seconds',
            'Database query duration in seconds',
            ['query_type', 'table'],
            [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0]
        );

        // Database connections
        $this->registry->getOrRegisterGauge(
            'sso_db_connections_active',
            'Active database connections',
            []
        );

        // Database table sizes
        $this->registry->getOrRegisterGauge(
            'sso_db_table_rows',
            'Number of rows in database tables',
            ['table']
        );

        // Slow query count
        $this->registry->getOrRegisterCounter(
            'sso_db_slow_queries_total',
            'Total number of slow database queries',
            ['table']
        );
    }

    public function collect(): void
    {
        // Collect database connection count
        try {
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            if (!empty($connections)) {
                $this->registry->getGauge('sso_db_connections_active', [])
                    ->set($connections[0]->Value, []);
            }
        } catch (\Exception $e) {
            // Handle connection errors gracefully
        }

        // Collect table row counts for key tables
        $tables = ['users', 'tenants', 'tenant_users', 'login_audits', 'sessions'];
        
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $this->registry->getGauge('sso_db_table_rows', ['table'])
                    ->set($count, [$table]);
            } catch (\Exception $e) {
                // Handle table access errors gracefully
            }
        }
    }
}
```

#### Tenant Metrics Collector (`central-sso/app/Prometheus/Collectors/TenantMetricsCollector.php`)

```php
<?php

namespace App\Prometheus\Collectors;

use Spatie\Prometheus\Collectors\Collector;
use Prometheus\CollectorRegistry;
use Illuminate\Support\Facades\DB;

class TenantMetricsCollector implements Collector
{
    protected CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function register(): void
    {
        // Tenant activity metrics
        $this->registry->getOrRegisterGauge(
            'sso_tenant_active_users',
            'Number of active users per tenant (last 24h)',
            ['tenant']
        );

        // Tenant API usage
        $this->registry->getOrRegisterCounter(
            'sso_tenant_api_requests_total',
            'Total API requests per tenant',
            ['tenant', 'endpoint', 'method', 'status']
        );

        // Tenant resource usage
        $this->registry->getOrRegisterGauge(
            'sso_tenant_resource_usage',
            'Resource usage per tenant',
            ['tenant', 'resource_type']
        );

        // Cross-tenant data sharing
        $this->registry->getOrRegisterCounter(
            'sso_tenant_data_sharing_total',
            'Cross-tenant data sharing events',
            ['source_tenant', 'target_tenant', 'data_type']
        );
    }

    public function collect(): void
    {
        // Collect active users per tenant (last 24 hours)
        $activeUsers = DB::table('login_audits')
            ->where('created_at', '>', now()->subDay())
            ->where('success', true)
            ->selectRaw('tenant_slug, count(DISTINCT user_id) as active_users')
            ->groupBy('tenant_slug')
            ->get();

        foreach ($activeUsers as $tenant) {
            $this->registry->getGauge('sso_tenant_active_users', ['tenant'])
                ->set($tenant->active_users, [$tenant->tenant_slug]);
        }

        // Collect tenant resource usage (example: storage, sessions)
        $tenants = DB::table('tenants')->where('is_active', true)->get();
        
        foreach ($tenants as $tenant) {
            // Example: Count sessions per tenant
            $sessionCount = DB::table('sessions')
                ->join('users', 'sessions.user_id', '=', 'users.id')
                ->join('tenant_users', 'users.id', '=', 'tenant_users.user_id')
                ->where('tenant_users.tenant_id', $tenant->id)
                ->count();

            $this->registry->getGauge('sso_tenant_resource_usage', ['tenant', 'resource_type'])
                ->set($sessionCount, [$tenant->slug, 'sessions']);
        }
    }
}
```

### Step 4: Enable Metrics Middleware

#### Add Middleware to HTTP Kernel (`central-sso/app/Http/Kernel.php`)

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        // ... existing middleware
        \Spatie\Prometheus\Middleware\CollectRequestDurationMiddleware::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            // ... existing middleware
        ],

        'api' => [
            // ... existing middleware
            \Spatie\Prometheus\Middleware\CollectRequestDurationMiddleware::class,
        ],
    ];
}
```

### Step 5: Create Custom Metrics Events

#### Authentication Event Listener (`central-sso/app/Listeners/RecordAuthenticationMetrics.php`)

```php
<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Spatie\Prometheus\Facades\Prometheus;

class RecordAuthenticationMetrics
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;
        $tenant = $this->getUserTenant($user);

        // Record successful login
        Prometheus::counterInc('sso_login_attempts_total', [
            'tenant' => $tenant,
            'status' => 'success',
            'method' => request()->get('login_method', 'standard')
        ]);

        // Record login duration (if available)
        if ($duration = request()->get('auth_duration')) {
            Prometheus::histogramObserve('sso_auth_duration_seconds', $duration, [
                'tenant' => $tenant,
                'method' => request()->get('login_method', 'standard'),
                'status' => 'success'
            ]);
        }
    }

    public function handleFailed(Failed $event): void
    {
        $credentials = $event->credentials;
        $tenant = request()->get('tenant_slug', 'unknown');

        // Record failed login
        Prometheus::counterInc('sso_login_attempts_total', [
            'tenant' => $tenant,
            'status' => 'failure',
            'method' => request()->get('login_method', 'standard')
        ]);

        // Record failed login by IP
        Prometheus::counterInc('sso_failed_logins_by_ip_total', [
            'ip_address' => request()->ip(),
            'tenant' => $tenant
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user;
        $tenant = $this->getUserTenant($user);

        // Record logout event
        Prometheus::counterInc('sso_logout_total', [
            'tenant' => $tenant,
            'method' => request()->get('logout_method', 'standard')
        ]);
    }

    private function getUserTenant($user): string
    {
        // Get user's primary tenant or current tenant from session
        return $user->tenants()->first()?->slug ?? 'unknown';
    }
}
```

#### Register Event Listeners (`central-sso/app/Providers/EventServiceProvider.php`)

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use App\Listeners\RecordAuthenticationMetrics;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            RecordAuthenticationMetrics::class . '@handleLogin',
        ],
        Failed::class => [
            RecordAuthenticationMetrics::class . '@handleFailed',
        ],
        Logout::class => [
            RecordAuthenticationMetrics::class . '@handleLogout',
        ],
    ];
}
```

### Step 6: Docker Compose Monitoring Stack

#### Create Monitoring Configuration (`docker-compose.monitoring.yml`)

```yaml
version: '3.8'

services:
  # Prometheus Server
  prometheus:
    image: prom/prometheus:v2.45.0
    container_name: sso-prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.libraries=/etc/prometheus/console_libraries'
      - '--web.console.templates=/etc/prometheus/consoles'
      - '--storage.tsdb.retention.time=30d'
      - '--web.enable-lifecycle'
      - '--web.enable-admin-api'
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus/prometheus.yml:/etc/prometheus/prometheus.yml:ro
      - ./monitoring/prometheus/rules:/etc/prometheus/rules:ro
      - prometheus_data:/prometheus
    networks:
      - sso-network
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "wget", "--quiet", "--tries=1", "--spider", "http://localhost:9090/-/healthy"]
      interval: 30s
      timeout: 10s
      retries: 3

  # Grafana Dashboard
  grafana:
    image: grafana/grafana:10.0.0
    container_name: sso-grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=${GRAFANA_PASSWORD:-admin123}
      - GF_USERS_ALLOW_SIGN_UP=false
      - GF_SECURITY_ALLOW_EMBEDDING=true
      - GF_AUTH_ANONYMOUS_ENABLED=false
      - GF_INSTALL_PLUGINS=grafana-piechart-panel
    volumes:
      - grafana_data:/var/lib/grafana
      - ./monitoring/grafana/provisioning:/etc/grafana/provisioning:ro
      - ./monitoring/grafana/dashboards:/var/lib/grafana/dashboards:ro
    networks:
      - sso-network
    restart: unless-stopped
    depends_on:
      - prometheus
    healthcheck:
      test: ["CMD-SHELL", "wget --no-verbose --tries=1 --spider http://localhost:3000/api/health || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 3

  # MySQL Exporter for Database Metrics
  mysql-exporter:
    image: prom/mysqld-exporter:v0.15.0
    container_name: sso-mysql-exporter
    ports:
      - "9104:9104"
    environment:
      - DATA_SOURCE_NAME=${DB_USERNAME}:${DB_PASSWORD}@(mariadb:3306)/
    networks:
      - sso-network
    restart: unless-stopped
    depends_on:
      - mariadb
    command:
      - '--collect.info_schema.processlist'
      - '--collect.info_schema.innodb_metrics'
      - '--collect.info_schema.tablestats'
      - '--collect.info_schema.tables'
      - '--collect.info_schema.userstats'
      - '--collect.engine_innodb_status'

  # Redis Exporter for Cache Metrics
  redis-exporter:
    image: oliver006/redis_exporter:v1.52.0
    container_name: sso-redis-exporter
    ports:
      - "9121:9121"
    environment:
      - REDIS_ADDR=redis:6379
      - REDIS_PASSWORD=${REDIS_PASSWORD}
    networks:
      - sso-network
    restart: unless-stopped
    depends_on:
      - redis

  # Node Exporter for System Metrics
  node-exporter:
    image: prom/node-exporter:v1.6.0
    container_name: sso-node-exporter
    ports:
      - "9100:9100"
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.rootfs=/rootfs'
      - '--path.sysfs=/host/sys'
      - '--collector.filesystem.mount-points-exclude=^/(sys|proc|dev|host|etc)($$|/)'
    networks:
      - sso-network
    restart: unless-stopped

volumes:
  prometheus_data:
    driver: local
  grafana_data:
    driver: local

networks:
  sso-network:
    external: true
```

### Step 7: Prometheus Configuration

#### Main Configuration (`monitoring/prometheus/prometheus.yml`)

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    environment: '{{ .Environment }}'
    cluster: 'sso-cluster'

# Load alerting rules
rule_files:
  - "rules/*.yml"

# Scrape configurations
scrape_configs:
  # Prometheus itself
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']

  # Central SSO Application
  - job_name: 'central-sso'
    static_configs:
      - targets: ['central-sso:8000']
    metrics_path: '/metrics'
    scrape_interval: 30s
    scrape_timeout: 10s
    labels:
      application: 'central-sso'
      service_type: 'web'

  # Tenant Applications
  - job_name: 'tenant1-app'
    static_configs:
      - targets: ['tenant1-app:8000']
    metrics_path: '/metrics'
    scrape_interval: 30s
    labels:
      application: 'tenant1-app'
      service_type: 'web'
      tenant: 'tenant1'

  - job_name: 'tenant2-app'
    static_configs:
      - targets: ['tenant2-app:8000']
    metrics_path: '/metrics'
    scrape_interval: 30s
    labels:
      application: 'tenant2-app'
      service_type: 'web'
      tenant: 'tenant2'

  # Cloudflare Tunnel
  - job_name: 'cloudflared'
    static_configs:
      - targets: ['cloudflared:9090']
    metrics_path: '/metrics'
    scrape_interval: 30s
    labels:
      service_type: 'tunnel'

  # Database Metrics
  - job_name: 'mysql'
    static_configs:
      - targets: ['mysql-exporter:9104']
    scrape_interval: 30s
    labels:
      service_type: 'database'

  # Redis Metrics
  - job_name: 'redis'
    static_configs:
      - targets: ['redis-exporter:9121']
    scrape_interval: 30s
    labels:
      service_type: 'cache'

  # System Metrics
  - job_name: 'node'
    static_configs:
      - targets: ['node-exporter:9100']
    scrape_interval: 30s
    labels:
      service_type: 'system'

# Alertmanager configuration (optional)
alerting:
  alertmanagers:
    - static_configs:
        - targets: []
```

#### Alert Rules (`monitoring/prometheus/rules/sso-alerts.yml`)

```yaml
groups:
  - name: sso-alerts
    rules:
      # High error rate alert
      - alert: HighErrorRate
        expr: |
          (
            rate(sso_http_requests_total{status=~"5.."}[5m]) /
            rate(sso_http_requests_total[5m])
          ) > 0.05
        for: 5m
        labels:
          severity: warning
          service: '{{ $labels.app_name }}'
        annotations:
          summary: "High error rate detected"
          description: "Error rate is above 5% for {{ $labels.app_name }} (current: {{ $value | humanizePercentage }})"

      # High response time alert
      - alert: HighResponseTime
        expr: |
          histogram_quantile(0.95, rate(sso_http_request_duration_seconds_bucket[5m])) > 2
        for: 5m
        labels:
          severity: warning
          service: '{{ $labels.app_name }}'
        annotations:
          summary: "High response time detected"
          description: "95th percentile response time is above 2s for {{ $labels.app_name }}"

      # Failed login attempts spike
      - alert: HighFailedLogins
        expr: |
          rate(sso_login_attempts_total{status="failure"}[5m]) > 10
        for: 2m
        labels:
          severity: critical
          service: 'authentication'
        annotations:
          summary: "High failed login rate"
          description: "Failed login rate is above 10/min for tenant {{ $labels.tenant }}"

      # Database connection issues
      - alert: DatabaseConnectionIssues
        expr: |
          sso_db_connections_active > 400
        for: 1m
        labels:
          severity: warning
          service: 'database'
        annotations:
          summary: "High database connection count"
          description: "Database connections are above 400 (current: {{ $value }})"

      # Low active user count (business metric)
      - alert: LowActiveUsers
        expr: |
          sso_tenant_active_users < 5
        for: 30m
        labels:
          severity: info
          service: 'business'
        annotations:
          summary: "Low active user count"
          description: "Tenant {{ $labels.tenant }} has less than 5 active users in the last 24h"

      # Cloudflare tunnel down
      - alert: CloudflareTunnelDown
        expr: |
          up{job="cloudflared"} == 0
        for: 1m
        labels:
          severity: critical
          service: 'tunnel'
        annotations:
          summary: "Cloudflare tunnel is down"
          description: "Cloudflare tunnel is not responding"

      # High memory usage
      - alert: HighMemoryUsage
        expr: |
          (1 - (node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)) > 0.85
        for: 5m
        labels:
          severity: warning
          service: 'system'
        annotations:
          summary: "High memory usage"
          description: "Memory usage is above 85% (current: {{ $value | humanizePercentage }})"
```

### Step 8: Grafana Dashboard Configuration

#### Data Source Configuration (`monitoring/grafana/provisioning/datasources/prometheus.yml`)

```yaml
apiVersion: 1

datasources:
  - name: Prometheus
    type: prometheus
    access: proxy
    url: http://prometheus:9090
    isDefault: true
    editable: true
    basicAuth: false
```

#### Dashboard Provisioning (`monitoring/grafana/provisioning/dashboards/dashboard.yml`)

```yaml
apiVersion: 1

providers:
  - name: 'sso-dashboards'
    orgId: 1
    folder: 'SSO System'
    type: file
    disableDeletion: false
    updateIntervalSeconds: 30
    allowUiUpdates: true
    options:
      path: /var/lib/grafana/dashboards
```

## Todo Implementation Checklist

### Phase 1: Basic Setup ✅
- [ ] Install Spatie Laravel Prometheus in all applications
- [ ] Configure basic metrics collection
- [ ] Set up Prometheus and Grafana containers
- [ ] Create basic dashboard

### Phase 2: Custom Metrics ⏳
- [ ] Implement SSO-specific metrics collectors
- [ ] Create authentication event listeners
- [ ] Set up database and system metrics
- [ ] Configure tenant-specific metrics

### Phase 3: Dashboards & Alerts 📊
- [ ] Create comprehensive Grafana dashboards
- [ ] Set up alert rules and notifications
- [ ] Configure Slack/email alerting
- [ ] Create business intelligence dashboards

### Phase 4: Advanced Features 🚀
- [ ] Implement distributed tracing (optional)
- [ ] Add custom business metrics
- [ ] Create automated reports
- [ ] Set up metric-based auto-scaling

### Phase 5: Production Optimization 🎯
- [ ] Optimize metrics collection performance
- [ ] Set up long-term metric storage
- [ ] Create disaster recovery procedures
- [ ] Document troubleshooting procedures

## Environment Variables

Add these to your `.env` files:

```env
# Prometheus Configuration
PROMETHEUS_ROUTE_PATH=metrics
PROMETHEUS_ROUTE_MIDDLEWARE=
PROMETHEUS_STORAGE_ADAPTER=redis
PROMETHEUS_REDIS_DATABASE=2
PROMETHEUS_MIDDLEWARE_ENABLED=true

# Grafana Configuration
GRAFANA_PASSWORD=secure_grafana_password

# Application Metrics
APP_VERSION=1.0.0
METRICS_ENABLED=true
```

## Security Considerations

### Metrics Endpoint Security
```php
// In routes/web.php or routes/api.php
Route::get('/metrics', [\Spatie\Prometheus\Controllers\PrometheusMetricsController::class, 'index'])
    ->middleware(['auth:api']) // Require authentication
    ->name('metrics');
```

### Sensitive Data Filtering
```php
// In prometheus configuration
'sensitive_labels' => [
    'password', 'token', 'secret', 'key', 'email'
],
```

## Performance Optimization

### Caching Metrics
```php
// In collectors, use caching for expensive queries
$userCount = Cache::remember('metrics:user_count', 300, function () {
    return DB::table('users')->count();
});
```

### Async Collection
```php
// Use queued jobs for heavy metric collection
dispatch(new CollectHeavyMetricsJob())->onQueue('metrics');
```

This implementation provides comprehensive monitoring and observability for your multi-tenant SSO system, enabling you to track performance, security, and business metrics effectively.