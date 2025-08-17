# Testing Documentation

This directory contains documentation and resources related to testing the multi-tenant SSO system.

## Test Documentation

### 🔄 **SSO Flow Testing**
- **[test-sso-flow.md](test-sso-flow.md)** - Comprehensive guide for testing SSO authentication flows across all tenant applications

## Testing Categories

### Unit & Integration Tests
- **Laravel Tests** - Located in each application's `tests/` directory
- **PHPUnit Configuration** - Configured in `phpunit.xml` files
- **Test Commands** - Use `php artisan test` or `./run_tests.sh`

### Security Testing
- **Security Test Suite** - Located in `../security-tests/` directory
- **Automated Security Scans** - Integrated in CI/CD pipeline
- **Manual Testing Tools** - Available in `../security-tests/tools/`

### End-to-End Testing
- **SSO Flow Testing** - Cross-application authentication testing
- **API Testing** - REST API endpoint validation
- **UI Testing** - Web interface functionality testing

## Test Execution

### Quick Test Commands

```bash
# Run all application tests
./run_tests.sh

# Test individual applications
cd central-sso && php artisan test
cd tenant1-app && php artisan test
cd tenant2-app && php artisan test

# Test specific suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

### SSO Integration Testing

```bash
# Manual SSO flow testing
# Follow the guide in test-sso-flow.md

# Automated SSO testing (if implemented)
./scripts/test-sso-flow.sh production
```

### Security Testing

```bash
# Run security test suite
cd security-tests
./run_security_tests.sh

# Daily security check
./security-tests/daily_security_check.sh
```

## Test Data

### Test Users
The system includes seeded test users for different scenarios:
- `superadmin@sso.com` - Cross-tenant access
- `admin@tenant1.com` - Tenant 1 administrator
- `user@tenant1.com` - Tenant 1 regular user
- `admin@tenant2.com` - Tenant 2 administrator
- `user@tenant2.com` - Tenant 2 regular user

Password for all test users: `password`

### Test Databases
- **Main Database**: `sso_main` - Primary SSO database
- **Test Database**: `sso_test` - Isolated test environment
- **Tenant Databases**: `tenant1_db`, `tenant2_db` - Tenant-specific data

## CI/CD Testing

The CI/CD pipeline includes:
- **Multi-version Testing** - PHP 8.1 & 8.2
- **Multi-application Testing** - All applications tested
- **Security Scanning** - Vulnerability detection
- **Integration Testing** - Cross-service validation

## Related Documentation

- **[../cicd-deployment-guide.md](../cicd-deployment-guide.md)** - CI/CD testing integration
- **[../security-architecture.md](../security-architecture.md)** - Security testing approach
- **[../../security-tests/README.md](../../security-tests/README.md)** - Security testing tools

## Navigation

```
docs/
├── testing/            # ← You are here
│   ├── test-sso-flow.md
│   └── README.md
├── summaries/          # Implementation summaries
├── *.md               # Detailed guides
└── README.md          # Main documentation index
```