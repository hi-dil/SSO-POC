# CLI Commands Reference

Complete reference for all Artisan commands available in the multi-tenant SSO system, including custom commands for user management, tenant operations, and system maintenance.

## 🚀 Quick Command Reference

### Most Common Commands
```bash
# User Management
php artisan user:create user@example.com "User Name" password
php artisan user:assign 1 tenant1
php artisan user:list --tenant=tenant1

# Tenant Operations
php artisan tenant:create tenant3 "Tenant Three" tenant3.localhost
php artisan tenant:migrate tenant3
php artisan tenant:seed tenant3

# Authentication Testing
php artisan test:sso-flow --tenant=tenant1
php artisan auth:validate-token {token}

# System Maintenance
php artisan system:health
php artisan cache:clear-all
php artisan audit:cleanup --days=90
```

## 👥 User Management Commands

### User CRUD Operations

#### `user:create` - Create New User
```bash
php artisan user:create {email} {name} {password} [options]

# Arguments:
#   email      User email address (must be unique)
#   name       Full name of the user
#   password   User password (will be hashed)

# Options:
#   --admin             Set user as admin
#   --tenant=SLUG       Assign to specific tenant
#   --role=ROLE         Set initial role (default: user)
#   --verify            Mark email as verified
#   --send-welcome      Send welcome email

# Examples:
php artisan user:create admin@tenant1.com "Admin User" password123 --admin --tenant=tenant1
php artisan user:create user@example.com "Regular User" password123 --role=manager --verify
php artisan user:create test@tenant2.com "Test User" password123 --tenant=tenant2 --send-welcome
```

#### `user:update` - Update Existing User
```bash
php artisan user:update {id} [options]

# Arguments:
#   id         User ID to update

# Options:
#   --email=EMAIL       Update email address
#   --name=NAME         Update full name
#   --password=PASS     Update password
#   --admin[=BOOL]      Set/unset admin status
#   --verify            Mark email as verified
#   --active[=BOOL]     Set active status

# Examples:
php artisan user:update 1 --email=newemail@example.com --name="New Name"
php artisan user:update 2 --admin=true --verify
php artisan user:update 3 --password=newpassword123 --active=false
```

#### `user:delete` - Delete User
```bash
php artisan user:delete {id} [options]

# Arguments:
#   id         User ID to delete

# Options:
#   --force             Force delete without confirmation
#   --preserve-audit    Keep audit logs after deletion
#   --cascade           Delete related tenant assignments

# Examples:
php artisan user:delete 5 --force
php artisan user:delete 10 --preserve-audit --cascade
```

#### `user:list` - List Users
```bash
php artisan user:list [options]

# Options:
#   --tenant=SLUG       Filter by tenant
#   --role=ROLE         Filter by role
#   --admin             Show only admin users
#   --active            Show only active users
#   --inactive          Show only inactive users
#   --format=FORMAT     Output format: table, json, csv
#   --limit=LIMIT       Limit number of results
#   --export=FILE       Export to file

# Examples:
php artisan user:list --tenant=tenant1 --format=table
php artisan user:list --admin --active
php artisan user:list --role=manager --export=managers.csv
```

### User-Tenant Relationships

#### `user:assign` - Assign User to Tenant
```bash
php artisan user:assign {user_id} {tenant_slug} [options]

# Arguments:
#   user_id      User ID to assign
#   tenant_slug  Tenant slug to assign to

# Options:
#   --role=ROLE         Set role in tenant (default: user)
#   --expires=DATE      Set expiration date (YYYY-MM-DD)
#   --permissions=PERMS Comma-separated permissions

# Examples:
php artisan user:assign 1 tenant1 --role=admin
php artisan user:assign 2 tenant2 --role=manager --expires=2024-12-31
php artisan user:assign 3 tenant1 --permissions=users.view,reports.generate
```

#### `user:unassign` - Remove User from Tenant
```bash
php artisan user:unassign {user_id} {tenant_slug} [options]

# Arguments:
#   user_id      User ID to unassign
#   tenant_slug  Tenant slug to remove from

# Options:
#   --force             Force removal without confirmation
#   --preserve-data     Keep user data in tenant database

# Examples:
php artisan user:unassign 1 tenant1 --force
php artisan user:unassign 2 tenant2 --preserve-data
```

#### `user:role` - Set User Role in Tenant
```bash
php artisan user:role {user_id} {role} [options]

# Arguments:
#   user_id      User ID
#   role         Role to assign

# Options:
#   --tenant=SLUG       Specific tenant (required for multi-tenant users)
#   --global            Apply role globally across all tenants

# Examples:
php artisan user:role 1 admin --tenant=tenant1
php artisan user:role 2 manager --global
```

#### `user:tenants` - Show User's Tenant Assignments
```bash
php artisan user:tenants {user_id} [options]

# Arguments:
#   user_id      User ID to check

# Options:
#   --format=FORMAT     Output format: table, json
#   --active-only       Show only active assignments

# Examples:
php artisan user:tenants 1 --format=table
php artisan user:tenants 2 --active-only
```

### User Profile Management

#### `user:profile` - Manage User Profiles
```bash
php artisan user:profile {user_id} {action} [options]

# Arguments:
#   user_id      User ID
#   action       Action: show, update, complete

# Options (for update):
#   --phone=PHONE       Update phone number
#   --bio=BIO           Update biography
#   --job-title=TITLE   Update job title
#   --department=DEPT   Update department
#   --company=COMPANY   Update company

# Examples:
php artisan user:profile 1 show
php artisan user:profile 1 update --phone="+1234567890" --job-title="Manager"
php artisan user:profile 1 complete  # Show profile completion status
```

## 🏢 Tenant Management Commands

### Tenant Operations

#### `tenant:create` - Create New Tenant
```bash
php artisan tenant:create {slug} {name} {domain} [options]

# Arguments:
#   slug         Unique tenant identifier
#   name         Display name for tenant
#   domain       Primary domain for tenant

# Options:
#   --description=DESC  Tenant description
#   --settings=JSON     Initial settings as JSON
#   --active            Set as active (default: true)
#   --create-db         Create tenant database
#   --migrate           Run migrations after creation
#   --seed              Seed initial data

# Examples:
php artisan tenant:create tenant3 "Tenant Three" tenant3.localhost --create-db --migrate
php artisan tenant:create acme "ACME Corp" acme.company.com --description="ACME Corporation" --seed
```

#### `tenant:update` - Update Tenant Configuration
```bash
php artisan tenant:update {slug} [options]

# Arguments:
#   slug         Tenant slug to update

# Options:
#   --name=NAME         Update display name
#   --domain=DOMAIN     Update primary domain
#   --description=DESC  Update description
#   --settings=JSON     Update settings (merges with existing)
#   --active[=BOOL]     Set active status

# Examples:
php artisan tenant:update tenant1 --name="New Tenant Name" --active=true
php artisan tenant:update acme --domain=new.acme.com --description="Updated description"
```

#### `tenant:delete` - Delete Tenant
```bash
php artisan tenant:delete {slug} [options]

# Arguments:
#   slug         Tenant slug to delete

# Options:
#   --force             Force delete without confirmation
#   --cascade           Delete all related data
#   --backup            Create backup before deletion
#   --preserve-users    Keep user assignments in central DB

# Examples:
php artisan tenant:delete tenant3 --force --cascade
php artisan tenant:delete old-tenant --backup --preserve-users
```

#### `tenant:list` - List All Tenants
```bash
php artisan tenant:list [options]

# Options:
#   --active            Show only active tenants
#   --inactive          Show only inactive tenants
#   --format=FORMAT     Output format: table, json, csv
#   --with-stats        Include usage statistics
#   --export=FILE       Export to file

# Examples:
php artisan tenant:list --active --format=table
php artisan tenant:list --with-stats --export=tenants.csv
```

### Tenant Database Operations

#### `tenant:migrate` - Run Tenant Migrations
```bash
php artisan tenant:migrate {slug} [options]

# Arguments:
#   slug         Tenant slug

# Options:
#   --force             Force migrations in production
#   --seed              Run seeders after migration
#   --step              Run migrations step by step
#   --rollback=STEPS    Rollback specified steps

# Examples:
php artisan tenant:migrate tenant1 --seed
php artisan tenant:migrate tenant2 --force --step
php artisan tenant:migrate tenant3 --rollback=1
```

#### `tenant:seed` - Seed Tenant Database
```bash
php artisan tenant:seed {slug} [options]

# Arguments:
#   slug         Tenant slug

# Options:
#   --class=CLASS       Specific seeder class
#   --force             Force seeding in production
#   --demo-data         Include demo data

# Examples:
php artisan tenant:seed tenant1 --class=UserSeeder
php artisan tenant:seed tenant2 --demo-data --force
```

#### `tenant:backup` - Backup Tenant Database
```bash
php artisan tenant:backup {slug} [options]

# Arguments:
#   slug         Tenant slug

# Options:
#   --path=PATH         Backup file path
#   --compress          Compress backup file
#   --structure-only    Backup structure without data
#   --data-only         Backup data without structure

# Examples:
php artisan tenant:backup tenant1 --compress
php artisan tenant:backup tenant2 --path=/backups/tenant2.sql --structure-only
```

#### `tenant:restore` - Restore Tenant Database
```bash
php artisan tenant:restore {slug} {backup_file} [options]

# Arguments:
#   slug          Tenant slug
#   backup_file   Path to backup file

# Options:
#   --force             Force restore without confirmation
#   --drop-existing     Drop existing database before restore

# Examples:
php artisan tenant:restore tenant1 /backups/tenant1.sql --force
php artisan tenant:restore tenant2 backup.sql --drop-existing
```

## 🔐 Authentication and Security Commands

### Authentication Testing

#### `test:sso-flow` - Test SSO Authentication Flow
```bash
php artisan test:sso-flow [options]

# Options:
#   --tenant=SLUG       Test specific tenant
#   --user=EMAIL        Test with specific user
#   --method=METHOD     Authentication method: direct, redirect, api
#   --verbose           Show detailed output

# Examples:
php artisan test:sso-flow --tenant=tenant1 --user=admin@tenant1.com
php artisan test:sso-flow --method=api --verbose
```

#### `test:tenant-isolation` - Test Tenant Isolation
```bash
php artisan test:tenant-isolation [options]

# Options:
#   --tenant1=SLUG      First tenant to test
#   --tenant2=SLUG      Second tenant to test
#   --user=EMAIL        User to test with
#   --comprehensive     Run comprehensive isolation tests

# Examples:
php artisan test:tenant-isolation --tenant1=tenant1 --tenant2=tenant2
php artisan test:tenant-isolation --user=superadmin@sso.com --comprehensive
```

### Token Management

#### `auth:validate-token` - Validate JWT Token
```bash
php artisan auth:validate-token {token} [options]

# Arguments:
#   token        JWT token to validate

# Options:
#   --decode            Show decoded token payload
#   --verify-tenant     Verify tenant access
#   --check-blacklist   Check if token is blacklisted

# Examples:
php artisan auth:validate-token eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9... --decode
php artisan auth:validate-token {token} --verify-tenant --check-blacklist
```

#### `auth:clear-tokens` - Clear Expired Tokens
```bash
php artisan auth:clear-tokens [options]

# Options:
#   --expired-only      Clear only expired tokens
#   --user=ID           Clear tokens for specific user
#   --older-than=DAYS   Clear tokens older than specified days

# Examples:
php artisan auth:clear-tokens --expired-only
php artisan auth:clear-tokens --user=5 --older-than=30
```

#### `auth:blacklist` - Blacklist Token
```bash
php artisan auth:blacklist {token} [options]

# Arguments:
#   token        JWT token to blacklist

# Options:
#   --reason=REASON     Reason for blacklisting
#   --notify-user       Notify user of blacklisted token

# Examples:
php artisan auth:blacklist {token} --reason="Security incident"
php artisan auth:blacklist {token} --notify-user
```

### Security Operations

#### `security:scan` - Security Vulnerability Scan
```bash
php artisan security:scan [options]

# Options:
#   --type=TYPE         Scan type: dependencies, configuration, code
#   --report=FILE       Generate report file
#   --fix               Attempt to fix found issues

# Examples:
php artisan security:scan --type=dependencies --report=security-report.json
php artisan security:scan --fix
```

#### `security:audit-config` - Audit Security Configuration
```bash
php artisan security:audit-config [options]

# Options:
#   --environment=ENV   Environment to audit
#   --fix-warnings      Attempt to fix configuration warnings
#   --export=FILE       Export audit results

# Examples:
php artisan security:audit-config --environment=production
php artisan security:audit-config --fix-warnings --export=config-audit.json
```

## 📊 Audit and Analytics Commands

### Audit Log Management

#### `audit:view` - View Audit Logs
```bash
php artisan audit:view [options]

# Options:
#   --tenant=SLUG       Filter by tenant
#   --user=ID           Filter by user ID
#   --action=ACTION     Filter by action type
#   --since=DATE        Show logs since date (YYYY-MM-DD)
#   --until=DATE        Show logs until date (YYYY-MM-DD)
#   --limit=LIMIT       Limit number of results
#   --export=FILE       Export to file

# Examples:
php artisan audit:view --tenant=tenant1 --since=2024-01-01
php artisan audit:view --user=5 --action=login --limit=100
php artisan audit:view --export=audit-logs.csv
```

#### `audit:cleanup` - Clean Up Old Audit Logs
```bash
php artisan audit:cleanup [options]

# Options:
#   --days=DAYS         Keep logs for specified days (default: 365)
#   --tenant=SLUG       Clean specific tenant logs
#   --dry-run           Show what would be deleted without deleting
#   --force             Force cleanup without confirmation

# Examples:
php artisan audit:cleanup --days=90 --dry-run
php artisan audit:cleanup --tenant=tenant1 --days=180 --force
```

#### `audit:export` - Export Audit Data
```bash
php artisan audit:export [options]

# Options:
#   --format=FORMAT     Export format: csv, json, xml
#   --since=DATE        Export since date
#   --until=DATE        Export until date
#   --tenant=SLUG       Export specific tenant
#   --file=FILE         Output file path

# Examples:
php artisan audit:export --format=csv --since=2024-01-01 --file=audit-2024.csv
php artisan audit:export --tenant=tenant1 --format=json
```

### Analytics and Reporting

#### `analytics:generate` - Generate Analytics Report
```bash
php artisan analytics:generate {type} [options]

# Arguments:
#   type         Report type: users, logins, tenants, security

# Options:
#   --period=PERIOD     Report period: daily, weekly, monthly, yearly
#   --tenant=SLUG       Tenant-specific report
#   --format=FORMAT     Output format: table, json, pdf
#   --email=EMAIL       Email report to address
#   --save=FILE         Save report to file

# Examples:
php artisan analytics:generate users --period=monthly --format=pdf
php artisan analytics:generate logins --tenant=tenant1 --email=admin@company.com
php artisan analytics:generate security --period=weekly --save=security-report.json
```

#### `analytics:dashboard` - Launch Analytics Dashboard
```bash
php artisan analytics:dashboard [options]

# Options:
#   --port=PORT         Dashboard port (default: 8080)
#   --host=HOST         Dashboard host (default: localhost)
#   --tenant=SLUG       Tenant-specific dashboard

# Examples:
php artisan analytics:dashboard --port=9000
php artisan analytics:dashboard --tenant=tenant1 --host=0.0.0.0
```

## 🔧 System Maintenance Commands

### System Health and Monitoring

#### `system:health` - System Health Check
```bash
php artisan system:health [options]

# Options:
#   --service=SERVICE   Check specific service: database, redis, filesystem
#   --tenant=SLUG       Check specific tenant health
#   --detailed          Show detailed health information
#   --fix               Attempt to fix detected issues

# Examples:
php artisan system:health --detailed
php artisan system:health --service=database --fix
php artisan system:health --tenant=tenant1
```

#### `system:status` - Show System Status
```bash
php artisan system:status [options]

# Options:
#   --json              Output as JSON
#   --components        Show component status
#   --metrics           Include performance metrics

# Examples:
php artisan system:status --json
php artisan system:status --components --metrics
```

### Cache Management

#### `cache:clear-all` - Clear All Caches
```bash
php artisan cache:clear-all [options]

# Options:
#   --tenant=SLUG       Clear specific tenant cache
#   --type=TYPE         Cache type: application, route, config, view
#   --force             Force clear in production

# Examples:
php artisan cache:clear-all --tenant=tenant1
php artisan cache:clear-all --type=view --force
```

#### `cache:warm` - Warm Up Caches
```bash
php artisan cache:warm [options]

# Options:
#   --tenant=SLUG       Warm specific tenant cache
#   --routes            Warm route cache
#   --views             Warm view cache
#   --config            Warm config cache

# Examples:
php artisan cache:warm --routes --views
php artisan cache:warm --tenant=tenant1 --config
```

### Database Maintenance

#### `db:optimize` - Optimize Database
```bash
php artisan db:optimize [options]

# Options:
#   --tenant=SLUG       Optimize specific tenant database
#   --analyze           Analyze tables
#   --repair            Repair corrupted tables
#   --indexes           Rebuild indexes

# Examples:
php artisan db:optimize --analyze --indexes
php artisan db:optimize --tenant=tenant1 --repair
```

#### `db:backup-all` - Backup All Databases
```bash
php artisan db:backup-all [options]

# Options:
#   --path=PATH         Backup directory path
#   --compress          Compress backups
#   --exclude=TENANTS   Exclude specific tenants (comma-separated)

# Examples:
php artisan db:backup-all --compress --path=/backups
php artisan db:backup-all --exclude=test-tenant,demo-tenant
```

### Performance Optimization

#### `optimize:production` - Production Optimization
```bash
php artisan optimize:production [options]

# Options:
#   --config            Optimize configuration
#   --routes            Optimize routes
#   --views             Optimize views
#   --autoloader        Optimize autoloader

# Examples:
php artisan optimize:production --config --routes --views
php artisan optimize:production --autoloader
```

#### `performance:benchmark` - Performance Benchmarking
```bash
php artisan performance:benchmark {type} [options]

# Arguments:
#   type         Benchmark type: auth, database, api, full

# Options:
#   --iterations=NUM    Number of iterations (default: 100)
#   --tenant=SLUG       Benchmark specific tenant
#   --report=FILE       Save benchmark report

# Examples:
php artisan performance:benchmark auth --iterations=1000
php artisan performance:benchmark database --tenant=tenant1 --report=db-benchmark.json
```

## 🛠️ Development and Testing Commands

### Development Tools

#### `dev:generate-api-docs` - Generate API Documentation
```bash
php artisan dev:generate-api-docs [options]

# Options:
#   --format=FORMAT     Documentation format: swagger, postman
#   --output=PATH       Output directory
#   --include-examples  Include request/response examples

# Examples:
php artisan dev:generate-api-docs --format=swagger --include-examples
php artisan dev:generate-api-docs --output=/docs/api
```

#### `dev:seed-test-data` - Seed Test Data
```bash
php artisan dev:seed-test-data [options]

# Options:
#   --tenant=SLUG       Seed specific tenant
#   --users=COUNT       Number of test users (default: 50)
#   --realistic         Use realistic test data
#   --clean             Clean existing test data first

# Examples:
php artisan dev:seed-test-data --users=100 --realistic
php artisan dev:seed-test-data --tenant=tenant1 --clean
```

### Testing Commands

#### `test:integration` - Run Integration Tests
```bash
php artisan test:integration [options]

# Options:
#   --suite=SUITE       Test suite: auth, tenant, api
#   --tenant=SLUG       Test specific tenant
#   --coverage          Generate coverage report

# Examples:
php artisan test:integration --suite=auth --coverage
php artisan test:integration --tenant=tenant1
```

#### `test:load` - Load Testing
```bash
php artisan test:load [options]

# Options:
#   --endpoint=URL      Specific endpoint to test
#   --concurrent=NUM    Concurrent users (default: 10)
#   --duration=SEC      Test duration in seconds (default: 60)
#   --tenant=SLUG       Test specific tenant

# Examples:
php artisan test:load --endpoint=/api/auth/login --concurrent=50
php artisan test:load --tenant=tenant1 --duration=300
```

---

## 🔗 Related Documentation

- **[Configuration Reference](configuration.md)** - Environment variables and settings
- **[Troubleshooting Guide](troubleshooting.md)** - Command-related issues and solutions
- **[API Documentation](api.md)** - REST API reference
- **[Security Guide](../guides/security.md)** - Security-related commands and procedures