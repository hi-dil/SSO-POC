# Prometheus & Grafana Implementation Todo

## 📋 Complete Implementation Checklist

This document provides a step-by-step todo list for implementing comprehensive monitoring and observability for your multi-tenant SSO system using Prometheus, Grafana, and Spatie's Laravel Prometheus package.

## 🎯 Phase 1: Basic Setup (Priority: High)

### ✅ Package Installation & Configuration

#### Central SSO Application
- [ ] **Install Spatie Laravel Prometheus package**
  ```bash
  cd central-sso
  composer require spatie/laravel-prometheus
  ```

- [ ] **Publish configuration files**
  ```bash
  php artisan vendor:publish --tag="prometheus-config"
  php artisan vendor:publish --tag="prometheus-migrations"
  ```

- [ ] **Run migrations (if using database storage)**
  ```bash
  php artisan migrate
  ```

- [ ] **Configure `config/prometheus.php`**
  - Set storage adapter to 'redis' for production
  - Configure application labels (app_name, tenant, environment)
  - Enable middleware for HTTP request metrics
  - Set custom buckets for histograms

#### Tenant Applications (Repeat for tenant1-app and tenant2-app)
- [ ] **Install package in tenant1-app**
  ```bash
  cd tenant1-app
  composer require spatie/laravel-prometheus
  php artisan vendor:publish --tag="prometheus-config"
  ```

- [ ] **Install package in tenant2-app**
  ```bash
  cd tenant2-app
  composer require spatie/laravel-prometheus
  php artisan vendor:publish --tag="prometheus-config"
  ```

- [ ] **Configure tenant-specific labels in prometheus.php**
  - Set app_name to 'tenant1-app' or 'tenant2-app'
  - Set tenant slug appropriately
  - Match storage configuration with central SSO

### ✅ Environment Configuration

- [ ] **Add Prometheus environment variables to all .env files**
  ```env
  # Prometheus Configuration
  PROMETHEUS_ROUTE_PATH=metrics
  PROMETHEUS_ROUTE_MIDDLEWARE=
  PROMETHEUS_STORAGE_ADAPTER=redis
  PROMETHEUS_REDIS_DATABASE=2
  PROMETHEUS_MIDDLEWARE_ENABLED=true
  
  # Application Metrics
  APP_VERSION=1.0.0
  METRICS_ENABLED=true
  
  # Grafana Configuration
  GRAFANA_PASSWORD=secure_grafana_password
  ```

- [ ] **Update Redis configuration to support Prometheus**
  - Ensure Redis DB 2 is available for Prometheus metrics
  - Test Redis connectivity from all applications

### ✅ Basic Metrics Endpoint

- [ ] **Test basic metrics endpoint in each application**
  ```bash
  # Test Central SSO
  curl http://localhost:8000/metrics
  
  # Test Tenant 1
  curl http://localhost:8001/metrics
  
  # Test Tenant 2  
  curl http://localhost:8002/metrics
  ```

- [ ] **Verify basic HTTP metrics are being collected**
  - Check for `sso_http_requests_total` metrics
  - Check for `sso_http_request_duration_seconds` metrics
  - Verify labels are correctly applied

## 🔧 Phase 2: Custom Metrics Implementation (Priority: High)

### ✅ Create Custom Collectors

#### SSO Metrics Collector (Central SSO)
- [ ] **Create `app/Prometheus/Collectors/SSOMetricsCollector.php`**
  - Implement total users per tenant metric
  - Implement active sessions metric
  - Implement login attempts counter
  - Implement JWT tokens issued counter
  - Implement cross-tenant access counter

- [ ] **Register collector in `config/prometheus.php`**
  ```php
  'collectors' => [
      \App\Prometheus\Collectors\SSOMetricsCollector::class => [
          'enabled' => true,
      ],
  ],
  ```

- [ ] **Test SSO metrics collection**
  ```bash
  curl http://localhost:8000/metrics | grep sso_total_users
  curl http://localhost:8000/metrics | grep sso_login_attempts
  ```

#### Authentication Metrics Collector (Central SSO)
- [ ] **Create `app/Prometheus/Collectors/AuthenticationMetricsCollector.php`**
  - Implement authentication duration histogram
  - Implement failed logins by IP counter
  - Implement password reset requests counter
  - Implement 2FA attempts counter (if applicable)
  - Implement API authentication counter

- [ ] **Register and test authentication metrics**

#### Database Metrics Collector (All Applications)
- [ ] **Create `app/Prometheus/Collectors/DatabaseMetricsCollector.php`**
  - Implement database query duration histogram
  - Implement active connections gauge
  - Implement table row counts gauge
  - Implement slow query counter

- [ ] **Add database metrics to all applications**
- [ ] **Test database metrics collection**

#### Tenant Metrics Collector (Central SSO)
- [ ] **Create `app/Prometheus/Collectors/TenantMetricsCollector.php`**
  - Implement tenant active users gauge
  - Implement tenant API usage counter
  - Implement tenant resource usage gauge
  - Implement cross-tenant data sharing counter

- [ ] **Test tenant-specific metrics**

### ✅ Event-Based Metrics

#### Authentication Event Listeners
- [ ] **Create `app/Listeners/RecordAuthenticationMetrics.php`**
  - Handle login events
  - Handle failed login events
  - Handle logout events
  - Record authentication timing

- [ ] **Register event listeners in `EventServiceProvider.php`**
  ```php
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
  ```

- [ ] **Test event-based metrics**
  - Perform test logins and verify metrics increment
  - Perform failed logins and verify IP tracking
  - Test logout events

#### Custom Business Events
- [ ] **Create metrics for business-critical events**
  - User registration events
  - Tenant creation/modification events
  - API key usage events
  - Cross-tenant access events

### ✅ Middleware Integration

- [ ] **Add Prometheus middleware to HTTP Kernel**
  ```php
  // In app/Http/Kernel.php
  protected $middleware = [
      // ... existing middleware
      \Spatie\Prometheus\Middleware\CollectRequestDurationMiddleware::class,
  ];
  ```

- [ ] **Test middleware integration**
  - Make requests to various endpoints
  - Verify request duration metrics are collected
  - Check that all routes are properly labeled

## 🐳 Phase 3: Infrastructure Setup (Priority: High)

### ✅ Docker Monitoring Stack

- [ ] **Create `docker-compose.monitoring.yml`**
  - Add Prometheus service configuration
  - Add Grafana service configuration
  - Add MySQL exporter for database metrics
  - Add Redis exporter for cache metrics
  - Add Node exporter for system metrics

- [ ] **Create monitoring configuration directories**
  ```bash
  mkdir -p monitoring/{prometheus,grafana/{provisioning/{datasources,dashboards},dashboards}}
  ```

- [ ] **Deploy monitoring stack**
  ```bash
  docker-compose -f docker-compose.yml -f docker-compose.monitoring.yml up -d
  ```

- [ ] **Verify all monitoring services are running**
  ```bash
  docker-compose ps | grep -E "(prometheus|grafana|exporter)"
  ```

### ✅ Prometheus Configuration

- [ ] **Create `monitoring/prometheus/prometheus.yml`**
  - Configure scrape targets for all applications
  - Set appropriate scrape intervals (30s recommended)
  - Add proper labels for service identification
  - Configure retention period (30 days recommended)

- [ ] **Create Prometheus alert rules**
  - High error rate alerts
  - High response time alerts
  - Failed login spike alerts
  - Database connection issues
  - System resource alerts

- [ ] **Test Prometheus configuration**
  ```bash
  # Check Prometheus targets
  curl http://localhost:9090/api/v1/targets
  
  # Test metric queries
  curl "http://localhost:9090/api/v1/query?query=sso_http_requests_total"
  ```

### ✅ Grafana Setup

- [ ] **Configure Grafana data sources**
  - Create `monitoring/grafana/provisioning/datasources/prometheus.yml`
  - Set Prometheus as default data source
  - Test data source connectivity

- [ ] **Create dashboard provisioning**
  - Create `monitoring/grafana/provisioning/dashboards/dashboard.yml`
  - Set up automatic dashboard loading

- [ ] **Access Grafana interface**
  ```bash
  # Open http://localhost:3000
  # Login with admin / GRAFANA_PASSWORD
  ```

## 📊 Phase 4: Dashboard Creation (Priority: Medium)

### ✅ SSO Overview Dashboard

- [ ] **Create main SSO overview dashboard**
  - Users by tenant pie chart
  - Active sessions gauge
  - HTTP request rate time series
  - Response time percentiles
  - Login attempts per minute
  - Database table sizes table

- [ ] **Import dashboard from JSON file**
  - Use provided `sso-overview-dashboard.json`
  - Customize for your specific needs
  - Test all panels display correctly

### ✅ Authentication Dashboard

- [ ] **Create authentication-focused dashboard**
  - Login success/failure rates
  - Authentication methods breakdown
  - Failed login attempts by IP
  - Cross-tenant access patterns
  - Password reset requests
  - Session duration metrics

### ✅ Infrastructure Dashboard

- [ ] **Create infrastructure monitoring dashboard**
  - System resource usage (CPU, memory, disk)
  - Database performance metrics
  - Redis cache performance
  - Cloudflare tunnel status
  - Container health status

### ✅ Business Intelligence Dashboard

- [ ] **Create business metrics dashboard**
  - User growth trends
  - Tenant activity levels
  - Feature usage statistics
  - API endpoint popularity
  - Geographic access patterns (if available)

## 🚨 Phase 5: Alerting Setup (Priority: Medium)

### ✅ Alert Rules Configuration

- [ ] **Review and customize alert rules in `sso-alerts.yml`**
  - Adjust thresholds for your environment
  - Add environment-specific rules
  - Test alert rule syntax

- [ ] **Set up alert notification channels**
  - Configure Slack webhook integration
  - Set up email notifications (optional)
  - Configure PagerDuty integration (optional)

### ✅ Alert Testing

- [ ] **Test critical alerts**
  - Simulate high error rates
  - Test failed login spike detection
  - Verify database connection alerts
  - Test system resource alerts

- [ ] **Verify alert delivery**
  - Check Slack notifications work
  - Verify alert escalation procedures
  - Test alert resolution notifications

## 🔒 Phase 6: Security & Performance (Priority: Medium)

### ✅ Metrics Endpoint Security

- [ ] **Secure metrics endpoints**
  ```php
  // Add authentication to metrics routes
  Route::get('/metrics', [PrometheusMetricsController::class, 'index'])
      ->middleware(['auth:api']);
  ```

- [ ] **Configure IP allowlisting for Prometheus scraping**
  - Restrict /metrics endpoint access
  - Use internal Docker networks where possible

### ✅ Performance Optimization

- [ ] **Implement metrics caching**
  - Cache expensive database queries
  - Use Redis for metrics storage
  - Set appropriate cache TTLs

- [ ] **Optimize collection frequency**
  - Use appropriate scrape intervals
  - Implement async collection for heavy metrics
  - Use queued jobs for expensive operations

### ✅ Data Retention & Storage

- [ ] **Configure Prometheus data retention**
  - Set retention period (30 days recommended)
  - Monitor storage usage
  - Plan for long-term storage needs

- [ ] **Set up backup procedures**
  - Backup Grafana dashboards
  - Export important Prometheus data
  - Document recovery procedures

## 🔍 Phase 7: Testing & Validation (Priority: High)

### ✅ Metrics Validation

- [ ] **Verify all custom metrics are collecting data**
  ```bash
  # Check each metric type
  curl http://localhost:8000/metrics | grep sso_total_users
  curl http://localhost:8000/metrics | grep sso_login_attempts
  curl http://localhost:8000/metrics | grep sso_http_requests
  ```

- [ ] **Test metrics across all applications**
  - Central SSO metrics endpoint
  - Tenant 1 metrics endpoint
  - Tenant 2 metrics endpoint

### ✅ Dashboard Testing

- [ ] **Test all dashboard panels**
  - Verify data is displaying correctly
  - Check that time ranges work properly
  - Test panel interactions and filters

- [ ] **Performance testing**
  - Generate load on applications
  - Verify metrics reflect actual usage
  - Check dashboard responsiveness

### ✅ Integration Testing

- [ ] **Test end-to-end monitoring flow**
  - Generate various types of events
  - Verify they appear in metrics
  - Check they display in dashboards
  - Verify alerts trigger appropriately

## 📚 Phase 8: Documentation & Training (Priority: Low)

### ✅ Documentation

- [ ] **Document metrics definitions**
  - Create metrics catalog
  - Document alert thresholds
  - Create troubleshooting guides

- [ ] **Create operational runbooks**
  - Alert response procedures
  - Metrics interpretation guides
  - Dashboard usage instructions

### ✅ Team Training

- [ ] **Train team on monitoring tools**
  - Grafana dashboard usage
  - Prometheus query language basics
  - Alert response procedures

- [ ] **Establish monitoring practices**
  - Regular metrics review schedule
  - Incident response procedures
  - Capacity planning processes

## 🎯 Quick Start Checklist

For immediate implementation, focus on these critical items:

### Day 1 Priority
- [ ] Install Spatie Laravel Prometheus in all applications
- [ ] Configure basic HTTP metrics collection
- [ ] Deploy Prometheus and Grafana containers
- [ ] Test basic metrics endpoints

### Day 2 Priority  
- [ ] Implement SSO and authentication metrics collectors
- [ ] Create basic overview dashboard
- [ ] Set up critical alerts (high error rate, failed logins)
- [ ] Test end-to-end metrics flow

### Week 1 Priority
- [ ] Complete all custom metrics implementation
- [ ] Create comprehensive dashboards
- [ ] Set up all alerting rules
- [ ] Document metrics and procedures

## ✅ Success Criteria

Your monitoring implementation is successful when:

- ✅ All applications expose `/metrics` endpoints with data
- ✅ Prometheus successfully scrapes all targets
- ✅ Grafana displays meaningful dashboards with real data
- ✅ Critical alerts trigger and deliver notifications
- ✅ Team can use dashboards for troubleshooting
- ✅ Business metrics provide actionable insights
- ✅ System performance is visible and measurable

## 🔧 Troubleshooting Common Issues

### Metrics Not Appearing
```bash
# Check if metrics endpoint is accessible
curl http://localhost:8000/metrics

# Check Prometheus targets
curl http://localhost:9090/api/v1/targets

# Check Laravel logs
docker-compose logs central-sso | grep prometheus
```

### Dashboard Not Loading Data
```bash
# Test Prometheus query manually
curl "http://localhost:9090/api/v1/query?query=sso_http_requests_total"

# Check Grafana data source
# Go to Configuration → Data Sources → Test
```

### Performance Issues
```bash
# Check metrics collection performance
# Monitor /metrics endpoint response time
# Review expensive database queries in collectors
```

This implementation provides comprehensive observability for your multi-tenant SSO system, enabling proactive monitoring, troubleshooting, and business intelligence.