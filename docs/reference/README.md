# Reference Documentation

Technical reference materials, API documentation, and configuration details for the multi-tenant SSO system.

## 📚 Documentation in This Section

### **[API Documentation](api.md)**
🔌 **Complete REST API reference** - All endpoints, request/response schemas, authentication
- Authentication endpoints (login, logout, token validation)
- User management API (CRUD operations, profile management)
- Tenant management API (tenant operations, user assignments)
- Audit API (login tracking, analytics, reporting)
- Error codes and response formats

### **[Configuration Reference](configuration.md)**
⚙️ **Complete configuration guide** - Environment variables, settings, and options
- Environment variable reference
- Database configuration options
- JWT and security settings
- Tenant-specific configurations
- Performance and caching settings

### **[CLI Commands](cli-commands.md)**
💻 **Artisan command reference** - All available commands and usage
- User management commands
- Tenant operations
- Database operations
- Security and maintenance commands
- Development and testing commands

### **[Troubleshooting Guide](troubleshooting.md)**
🔧 **Common issues and solutions** - Diagnostic procedures and fixes
- Authentication problems
- Database connectivity issues
- Performance troubleshooting
- Security incident response
- Development environment issues

## 🔌 API Reference

### Authentication Endpoints

The SSO system provides comprehensive authentication APIs:

```bash
# Central SSO Authentication
POST /api/auth/login           # Authenticate user
POST /api/auth/logout          # Logout user
POST /api/auth/refresh         # Refresh JWT token
GET  /api/auth/me              # Get current user
POST /api/auth/validate        # Validate JWT token

# Tenant Authentication
POST /api/tenant/auth/login    # Direct tenant login
GET  /api/tenant/auth/check    # Check authentication status
```

### User Management API

Complete user lifecycle management:

```bash
# User CRUD Operations
GET    /api/users              # List users with filtering
POST   /api/users              # Create new user
GET    /api/users/{id}         # Get user details
PUT    /api/users/{id}         # Update user
DELETE /api/users/{id}         # Delete user

# Profile Management
GET    /api/users/{id}/profile         # Get extended profile
PUT    /api/users/{id}/profile         # Update profile
POST   /api/users/{id}/family          # Add family member
POST   /api/users/{id}/contacts        # Add contact method
POST   /api/users/{id}/addresses       # Add address
POST   /api/users/{id}/social-media    # Add social media profile
```

### Tenant Management API

Multi-tenant operations and configuration:

```bash
# Tenant Operations
GET    /api/tenants            # List tenants
POST   /api/tenants            # Create tenant
GET    /api/tenants/{slug}     # Get tenant details
PUT    /api/tenants/{slug}     # Update tenant
DELETE /api/tenants/{slug}     # Delete tenant

# User-Tenant Relationships
GET    /api/tenants/{slug}/users       # List tenant users
POST   /api/tenants/{slug}/users       # Assign user to tenant
DELETE /api/tenants/{slug}/users/{id}  # Remove user from tenant
PUT    /api/tenants/{slug}/users/{id}  # Update user role in tenant
```

### Audit and Analytics API

Comprehensive logging and reporting:

```bash
# Login Audit
GET    /api/audit/logins       # Login audit logs
POST   /api/audit/logins       # Record login attempt
GET    /api/audit/analytics    # Authentication analytics

# User Activity
GET    /api/audit/users/{id}   # User activity history
GET    /api/audit/tenants/{slug}  # Tenant activity logs
GET    /api/audit/security     # Security events
```

## ⚙️ Configuration Reference

### Environment Variables

#### **Application Configuration**
```bash
# Basic Application Settings
APP_NAME="Multi-Tenant SSO"
APP_ENV=production
APP_KEY=base64:GENERATED_KEY
APP_DEBUG=false
APP_URL=https://sso.your-domain.com

# Tenant Configuration
TENANT_SLUG=central-sso
TENANT_DOMAIN=sso.your-domain.com
```

#### **Database Configuration**
```bash
# MariaDB Database
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=sso_main
DB_USERNAME=sso_user
DB_PASSWORD=secure_password

# Database Pool Settings
DB_POOL_MIN=5
DB_POOL_MAX=50
DB_TIMEOUT=60
```

#### **JWT Authentication**
```bash
# JWT Configuration
JWT_SECRET=your_32_character_jwt_secret_here
JWT_TTL=60                    # Token lifetime in minutes
JWT_REFRESH_TTL=20160         # Refresh token lifetime in minutes
JWT_ALGO=HS256               # JWT algorithm

# Token Blacklist
JWT_BLACKLIST_ENABLED=true
JWT_BLACKLIST_GRACE_PERIOD=10
```

#### **SSO Configuration**
```bash
# Central SSO Settings
CENTRAL_SSO_URL=https://sso.your-domain.com
CENTRAL_SSO_API_KEY=your_api_key
HMAC_SECRET=your_64_character_hmac_secret

# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### Security Settings

#### **Rate Limiting Configuration**
```bash
# Rate Limiting
THROTTLE_LOGIN_MAX_ATTEMPTS=5
THROTTLE_LOGIN_DECAY_MINUTES=5
THROTTLE_API_MAX_REQUESTS=60
THROTTLE_API_WINDOW_MINUTES=1
```

#### **Password Security**
```bash
# Password Requirements
PASSWORD_MIN_LENGTH=8
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_SYMBOLS=true
PASSWORD_HISTORY_COUNT=5
```

## 💻 CLI Commands

### User Management Commands

```bash
# User Operations
php artisan user:create {email} {name} {password}    # Create user
php artisan user:update {id} --email={email}        # Update user
php artisan user:delete {id}                        # Delete user
php artisan user:list --tenant={slug}               # List users

# User-Tenant Assignment
php artisan user:assign {user_id} {tenant_slug}     # Assign to tenant
php artisan user:unassign {user_id} {tenant_slug}   # Remove from tenant
php artisan user:role {user_id} {role} --tenant={slug}  # Set role
```

### Tenant Management Commands

```bash
# Tenant Operations
php artisan tenant:create {slug} {name} {domain}    # Create tenant
php artisan tenant:update {slug} --name={name}      # Update tenant
php artisan tenant:delete {slug}                    # Delete tenant
php artisan tenant:list                             # List tenants

# Tenant Database Operations
php artisan tenant:migrate {slug}                   # Run migrations
php artisan tenant:seed {slug}                      # Seed database
php artisan tenant:backup {slug}                    # Backup database
php artisan tenant:restore {slug} {backup_file}     # Restore backup
```

### Security and Maintenance Commands

```bash
# Security Operations
php artisan auth:clear-tokens                       # Clear expired tokens
php artisan auth:blacklist {token}                  # Blacklist token
php artisan security:scan                           # Security scan
php artisan audit:cleanup --days=90                 # Clean old audit logs

# System Maintenance
php artisan system:health                           # System health check
php artisan cache:clear-all                         # Clear all caches
php artisan optimize:production                     # Production optimization
php artisan maintenance:enable {message}            # Enable maintenance mode
```

### Development and Testing Commands

```bash
# Development Tools
php artisan test:sso-flow                          # Test SSO authentication
php artisan test:tenant-isolation                  # Test tenant isolation
php artisan dev:generate-api-docs                  # Generate API docs
php artisan dev:seed-test-data                     # Seed test data

# Performance Testing
php artisan performance:auth-benchmark              # Auth performance test
php artisan performance:db-benchmark               # Database performance
php artisan performance:api-load-test              # API load testing
```

## 🔧 Troubleshooting Guide

### Common Authentication Issues

#### **Invalid Credentials**
```bash
# Symptoms
HTTP 401 Unauthorized
"Invalid email or password"

# Diagnosis
1. Verify user exists: php artisan user:list --email={email}
2. Check password hash: php artisan user:verify-password {email}
3. Verify tenant access: php artisan user:tenants {user_id}

# Solutions
- Reset password: php artisan user:reset-password {email}
- Assign tenant access: php artisan user:assign {user_id} {tenant_slug}
- Check account status: php artisan user:status {user_id}
```

#### **JWT Token Issues**
```bash
# Symptoms
"Token has expired"
"Invalid token signature"
"Token not found"

# Diagnosis
1. Check JWT configuration: php artisan config:show jwt
2. Verify token format: php artisan jwt:decode {token}
3. Check blacklist: php artisan jwt:blacklist-status {token}

# Solutions
- Refresh token: POST /api/auth/refresh
- Clear blacklist: php artisan jwt:clear-blacklist
- Regenerate JWT secret: php artisan jwt:secret --force
```

### Database Connection Issues

#### **Connection Timeouts**
```bash
# Symptoms
"SQLSTATE[HY000] [2002] Connection timed out"
"Too many connections"

# Diagnosis
1. Check database status: docker exec mariadb mysql -e "SHOW STATUS LIKE 'Threads_connected'"
2. Test connection: php artisan db:monitor
3. Check pool settings: php artisan config:show database.connections.mysql

# Solutions
- Increase connection pool: DB_POOL_MAX=100
- Optimize queries: php artisan db:optimize
- Restart database: docker restart mariadb
```

#### **Migration Failures**
```bash
# Symptoms
"Migration failed"
"Table already exists"
"Foreign key constraint fails"

# Diagnosis
1. Check migration status: php artisan migrate:status
2. Verify database state: php artisan db:show
3. Check foreign key constraints: php artisan db:constraints

# Solutions
- Reset migrations: php artisan migrate:reset
- Fresh migration: php artisan migrate:fresh --seed
- Fix constraints: php artisan db:fix-constraints
```

### Performance Issues

#### **Slow Authentication**
```bash
# Symptoms
Login takes > 2 seconds
High CPU usage during auth
Memory exhaustion

# Diagnosis
1. Enable query logging: DB_LOG_QUERIES=true
2. Monitor performance: php artisan performance:monitor
3. Check cache usage: php artisan cache:stats

# Solutions
- Enable Redis caching: CACHE_DRIVER=redis
- Optimize password hashing: BCRYPT_ROUNDS=10
- Use connection pooling: DB_POOL_ENABLED=true
```

#### **High Memory Usage**
```bash
# Symptoms
Out of memory errors
Container restarts
Slow response times

# Diagnosis
1. Memory profiling: php artisan debug:memory
2. Check query efficiency: php artisan db:explain
3. Monitor sessions: php artisan session:monitor

# Solutions
- Increase memory limit: PHP_MEMORY_LIMIT=512M
- Optimize queries: Add database indexes
- Clean old sessions: php artisan session:gc
```

### Security Incidents

#### **Brute Force Attacks**
```bash
# Symptoms
High failed login attempts
Rate limit exceeded errors
Suspicious IP patterns

# Diagnosis
1. Check audit logs: php artisan audit:failed-logins --recent
2. Analyze IP patterns: php artisan security:analyze-ips
3. Review rate limits: php artisan throttle:status

# Response
- Block suspicious IPs: php artisan firewall:block {ip}
- Increase rate limits: THROTTLE_LOGIN_MAX_ATTEMPTS=3
- Enable account lockout: ACCOUNT_LOCKOUT_ENABLED=true
```

#### **Unauthorized Access**
```bash
# Symptoms
Access to unauthorized tenants
Permission escalation
Cross-tenant data access

# Diagnosis
1. Audit user permissions: php artisan audit:permissions {user_id}
2. Check tenant isolation: php artisan tenant:verify-isolation
3. Review access logs: php artisan audit:access-violations

# Response
- Revoke access: php artisan user:unassign {user_id} {tenant_slug}
- Reset permissions: php artisan user:reset-permissions {user_id}
- Force logout: php artisan auth:logout-user {user_id}
```

## 🔍 Error Codes Reference

### HTTP Status Codes

| Code | Description | Common Causes |
|------|-------------|---------------|
| 200 | OK | Successful operation |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request format or parameters |
| 401 | Unauthorized | Invalid or missing authentication |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource does not exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |
| 503 | Service Unavailable | Maintenance mode or overload |

### Application Error Codes

| Code | Category | Description |
|------|----------|-------------|
| AUTH_001 | Authentication | Invalid credentials |
| AUTH_002 | Authentication | Account locked |
| AUTH_003 | Authentication | Token expired |
| AUTH_004 | Authentication | Unauthorized tenant access |
| USER_001 | User Management | User not found |
| USER_002 | User Management | Email already exists |
| USER_003 | User Management | Invalid user data |
| TENANT_001 | Tenant Management | Tenant not found |
| TENANT_002 | Tenant Management | Slug already exists |
| TENANT_003 | Tenant Management | Invalid tenant configuration |
| DB_001 | Database | Connection failed |
| DB_002 | Database | Query timeout |
| DB_003 | Database | Constraint violation |

---

## 🔗 Related Documentation

- **[Getting Started](../getting-started/README.md)** - Quick setup and local development
- **[Architecture Overview](../architecture/README.md)** - System design and concepts
- **[Deployment Guide](../deployment/README.md)** - Production deployment strategies
- **[Guides](../guides/README.md)** - Step-by-step instructions for common tasks

---

**Quick Links**:
- **[Live API Documentation](http://localhost:8000/api/documentation)** - Interactive Swagger UI
- **[Laravel Telescope](http://localhost:8000/telescope)** - Application monitoring (development)
- **[System Health](http://localhost:8000/health)** - Health check endpoint