# Testing Guide

This comprehensive testing guide covers all aspects of testing the SSO audit system, from individual component tests to full system integration testing.

## Overview

The SSO system includes a robust testing framework that validates:
- **Central SSO audit functionality** - Core authentication tracking
- **Tenant application integration** - Cross-application audit communication
- **Real authentication flows** - End-to-end testing with actual login attempts
- **Database consistency** - Data integrity across all operations
- **Performance metrics** - Response times and system load testing

## Quick Start

### Run All Tests (Recommended)
```bash
# Execute comprehensive test suite
./run_tests.sh
```

This script runs all test suites and provides a complete system validation including:
- Central SSO audit system tests
- Both tenant application tests
- Real authentication flow testing
- Database statistics verification

## Test Suites

### 1. Central SSO Audit System Tests

Test the core audit functionality in the central SSO server.

```bash
# Basic audit tests
docker exec central-sso php artisan test:login-audit

# Comprehensive tests including API calls
docker exec central-sso php artisan test:login-audit --comprehensive
```

**What gets tested:**
- ✅ Login/logout recording functionality
- ✅ Failed login attempt tracking
- ✅ Database audit record operations
- ✅ Analytics data structure validation
- ✅ API endpoint functionality
- ✅ User and tenant activity summaries

### 2. Tenant Application Tests

Test audit communication between tenant applications and central SSO.

```bash
# Test tenant1 audit system
docker exec tenant1-app php artisan test:tenant-audit

# Test tenant2 audit system
docker exec tenant2-app php artisan test:tenant-audit

# Comprehensive tenant tests
docker exec tenant1-app php artisan test:tenant-audit --comprehensive
```

**What gets tested:**
- ✅ Environment configuration validation
- ✅ Central SSO connectivity
- ✅ Audit API communication
- ✅ Authentication flow simulation
- ✅ Direct login and SSO callback testing

### 3. Full System Integration Tests

Test the complete system as an integrated whole.

```bash
# Run comprehensive integration tests
docker exec central-sso php artisan test:full-system

# Run with automatic cleanup
docker exec central-sso php artisan test:full-system --cleanup
```

**What gets tested:**
- ✅ Cross-tenant audit tracking
- ✅ Concurrent audit recording
- ✅ Data consistency verification
- ✅ Performance benchmarking
- ✅ Analytics and reporting functions
- ✅ System cleanup procedures

### 4. Laravel Feature Tests

Run the traditional Laravel test suite with PHPUnit.

```bash
# Run Laravel feature tests
docker exec central-sso php artisan test

# Run specific test file
docker exec central-sso php artisan test tests/Feature/LoginAuditTest.php

# Run with detailed output
docker exec central-sso php artisan test --verbose
```

**What gets tested:**
- ✅ Audit service unit tests
- ✅ API endpoint validation
- ✅ Database model functionality
- ✅ Authentication workflows
- ✅ Permission and validation logic

## Test Commands Reference

### Central SSO Commands

| Command | Purpose | Duration |
|---------|---------|----------|
| `test:login-audit` | Basic audit system tests | ~10 seconds |
| `test:login-audit --comprehensive` | Full audit tests + API calls | ~30 seconds |
| `test:full-system` | Complete integration tests | ~60 seconds |
| `test:full-system --cleanup` | Integration tests + cleanup | ~70 seconds |

### Tenant Application Commands

| Command | Purpose | Duration |
|---------|---------|----------|
| `test:tenant-audit` | Basic tenant audit tests | ~10 seconds |
| `test:tenant-audit --comprehensive` | Full tenant tests | ~20 seconds |

## Manual Testing

### Authentication Flow Testing

Test real user authentication scenarios:

```bash
# Test API authentication
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "superadmin@sso.com", 
    "password": "password", 
    "tenant_slug": "tenant1"
  }'

# Test audit API directly
curl -X POST "http://localhost:8000/api/audit/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "superadmin@sso.com",
    "tenant_id": "tenant1", 
    "login_method": "api",
    "is_successful": true
  }'
```

### Database Verification

Check audit records directly in the database:

```bash
# View recent audit records
docker exec sso-mariadb mysql -u sso_user -psso_password sso_main -e "
SELECT 
  id, 
  user_id, 
  tenant_id, 
  login_method, 
  is_successful, 
  DATE_FORMAT(login_at, '%H:%i:%s') as time
FROM login_audits 
ORDER BY id DESC 
LIMIT 10;"

# Get audit statistics
docker exec sso-mariadb mysql -u sso_user -psso_password sso_main -e "
SELECT 
  COUNT(*) as total_audits,
  COUNT(DISTINCT user_id) as unique_users,
  SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful_logins,
  SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed_logins
FROM login_audits;"

# View audit breakdown by tenant and method
docker exec sso-mariadb mysql -u sso_user -psso_password sso_main -e "
SELECT 
  tenant_id, 
  login_method, 
  COUNT(*) as count 
FROM login_audits 
WHERE is_successful = 1 
GROUP BY tenant_id, login_method 
ORDER BY tenant_id, login_method;"
```

## Analytics Dashboard Testing

### Accessing the Dashboard
1. Login to central SSO: `http://localhost:8000/login`
2. Use credentials: `superadmin@sso.com` / `password`
3. Navigate to: `http://localhost:8000/admin/analytics`

### Dashboard Features to Test
- **Live Statistics**: Auto-refresh every 30 seconds
- **Tenant Breakdown**: Login activity per tenant
- **Method Analysis**: Direct vs SSO vs API usage
- **Recent Activity**: Real-time login feed
- **Export Functionality**: CSV download capability

## Test Data and Factories

The system includes comprehensive test data generation using Laravel model factories for consistent and reliable testing scenarios.

## Troubleshooting Tests

### Common Test Issues

#### Database Connection Errors
```bash
# Check database status and restart if needed
docker exec sso-mariadb mysqladmin -u sso_user -psso_password status
docker restart sso-mariadb
```

#### Missing Test Users
```bash
# Seed test data
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder
```

#### Cache Issues
```bash
# Clear all caches
docker exec central-sso php artisan cache:clear
docker exec central-sso php artisan config:clear
```

## Best Practices

### Writing New Tests
1. **Follow existing patterns**: Use the established test structure
2. **Clean up test data**: Always clean up after tests complete
3. **Use factories**: Leverage model factories for consistent test data
4. **Test edge cases**: Include both success and failure scenarios
5. **Document expected behavior**: Clear assertions with descriptive messages

### Test Maintenance
1. **Regular execution**: Run tests with each code change
2. **Update with features**: Add tests for new audit functionality
3. **Performance monitoring**: Track test execution times
4. **Data cleanup**: Ensure tests don't accumulate stale data

---

*This testing guide ensures comprehensive validation of the SSO audit system across all components and use cases.*