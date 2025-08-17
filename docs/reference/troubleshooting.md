# Troubleshooting Guide

Comprehensive guide for diagnosing and resolving common issues in the multi-tenant SSO system, with step-by-step solutions and preventive measures.

## 🚨 Emergency Response

### Critical System Failures

#### Complete System Down
```bash
# Immediate Assessment
docker ps -a                    # Check container status
docker logs central-sso --tail=50    # Check application logs
docker logs mariadb --tail=50       # Check database logs

# Quick Recovery Steps
docker-compose restart          # Restart all services
docker system prune -f         # Clean up resources if needed
docker-compose up -d           # Rebuild if restart fails

# Health Check
curl -f http://localhost:8000/health || echo "System not responding"
```

#### Database Connection Lost
```bash
# Emergency Database Recovery
docker exec mariadb mysqladmin ping  # Test database connectivity
docker restart mariadb              # Restart database
docker exec central-sso php artisan migrate:status  # Check migrations

# If database is corrupted
docker exec mariadb mysqlcheck --all-databases --repair
```

#### Security Breach Response
```bash
# Immediate Security Actions
php artisan auth:clear-tokens --expired-only    # Clear expired tokens
php artisan security:scan --type=all           # Security scan
php artisan audit:view --since=today --action=login  # Review recent activity

# Lock down system if needed
php artisan down --message="Security maintenance"
```

## 🔐 Authentication Issues

### Login Failures

#### "Invalid email or password" (HTTP 401)

**Symptoms:**
- Users cannot log in with correct credentials
- Authentication API returns 401 status
- Login form rejects known good passwords

**Diagnosis Steps:**
```bash
# 1. Verify user exists
php artisan user:list --email=user@example.com

# 2. Check password hash
php artisan tinker
User::where('email', 'user@example.com')->first()->password;

# 3. Verify tenant access
php artisan user:tenants 1  # Replace 1 with user ID

# 4. Check audit logs
php artisan audit:view --user=1 --action=login --limit=10
```

**Common Solutions:**
```bash
# Reset user password
php artisan user:update 1 --password=newpassword123

# Assign user to tenant if missing
php artisan user:assign 1 tenant1

# Clear failed login attempts
php artisan cache:forget "login_attempts:192.168.1.100"

# Verify email if required
php artisan user:update 1 --verify
```

**Prevention:**
- Implement proper error logging
- Set up monitoring for failed login spikes
- Regular password policy enforcement

---

#### "Access denied for this tenant" (HTTP 403)

**Symptoms:**
- User exists but cannot access specific tenant
- JWT token validation fails for tenant
- Cross-tenant access denied

**Diagnosis Steps:**
```bash
# 1. Check user-tenant relationships
php artisan user:tenants 1

# 2. Verify tenant exists and is active
php artisan tenant:list --active

# 3. Check JWT token claims
php artisan auth:validate-token {token} --decode --verify-tenant

# 4. Review tenant assignment audit
php artisan audit:view --user=1 --action=assign
```

**Solutions:**
```bash
# Assign user to tenant
php artisan user:assign 1 tenant1 --role=user

# Update existing assignment role
php artisan user:role 1 admin --tenant=tenant1

# Verify tenant slug in JWT claims
# Token should include tenant in 'tenants' array
```

---

#### "Account locked" (HTTP 423)

**Symptoms:**
- Account lockout after failed attempts
- User cannot login even with correct password
- Lockout duration in effect

**Diagnosis Steps:**
```bash
# 1. Check account status
php artisan user:list --user-id=1

# 2. Review failed login attempts
php artisan audit:view --user=1 --action=login --since=today

# 3. Check lockout cache
php artisan cache:get "account_lockout:user_1"
```

**Solutions:**
```bash
# Unlock account manually
php artisan cache:forget "account_lockout:user_1"
php artisan cache:forget "login_attempts:user_1"

# Reset failed attempts counter
php artisan user:unlock 1

# Update lockout settings if needed
# ACCOUNT_LOCKOUT_ATTEMPTS=5
# ACCOUNT_LOCKOUT_DURATION=300
```

### JWT Token Issues

#### "Token has expired" (HTTP 401)

**Symptoms:**
- Valid tokens rejected as expired
- Automatic token refresh not working
- Users logged out unexpectedly

**Diagnosis Steps:**
```bash
# 1. Decode token to check expiration
php artisan auth:validate-token {token} --decode

# 2. Check JWT configuration
php artisan config:show jwt.ttl
php artisan config:show jwt.refresh_ttl

# 3. Verify system time sync
date && docker exec central-sso date
```

**Solutions:**
```bash
# Refresh token if within refresh window
curl -X POST http://localhost:8000/api/auth/refresh \
  -H "Authorization: Bearer {token}"

# Adjust token lifetime if too short
# JWT_TTL=120  # 2 hours instead of 1

# Force re-login if refresh fails
php artisan auth:clear-tokens --user=1
```

---

#### "Invalid token signature" (HTTP 401)

**Symptoms:**
- Token signature verification fails
- Tokens work in some environments but not others
- JWT_SECRET mismatch

**Diagnosis Steps:**
```bash
# 1. Check JWT secret configuration
php artisan config:show jwt.secret

# 2. Verify secret across environments
echo $JWT_SECRET

# 3. Check token generation vs validation
php artisan jwt:decode {token} --verify-signature
```

**Solutions:**
```bash
# Ensure JWT_SECRET is consistent across all services
# Central SSO and all tenant apps must use same secret

# Regenerate JWT secret if compromised
php artisan jwt:secret --force

# Update all applications with new secret
# Redeploy tenant applications after secret change
```

### Session Management Issues

#### Cross-Tenant Session Problems

**Symptoms:**
- User logged in to one tenant but not another
- Session data not shared between tenant apps
- SSO redirect not working

**Diagnosis Steps:**
```bash
# 1. Check session configuration
php artisan config:show session.domain
php artisan config:show session.same_site

# 2. Verify cookie settings
# Check browser dev tools for cookie domain/path

# 3. Test session sharing
curl -I http://localhost:8001/auth/check \
  -H "Cookie: laravel_session=session_value"
```

**Solutions:**
```bash
# Configure session domain for sharing
# SESSION_DOMAIN=.localhost  # Note the leading dot

# Ensure consistent session driver
# SESSION_DRIVER=redis  # Same across all apps

# Verify network connectivity
docker exec tenant1-app ping central-sso
```

## 🗄️ Database Issues

### Connection Problems

#### "Connection refused" (SQLSTATE[HY000] [2002])

**Symptoms:**
- Application cannot connect to database
- Database container not responding
- Connection timeout errors

**Diagnosis Steps:**
```bash
# 1. Check database container status
docker ps | grep mariadb

# 2. Test database connectivity
docker exec mariadb mysql -u root -p -e "SELECT 1"

# 3. Check network connectivity
docker exec central-sso ping mariadb

# 4. Verify database configuration
php artisan config:show database.connections.mysql
```

**Solutions:**
```bash
# Restart database container
docker restart mariadb

# Check database logs for errors
docker logs mariadb --tail=100

# Verify database credentials
# DB_USERNAME and DB_PASSWORD in .env

# Test connection manually
docker exec central-sso php artisan db:monitor
```

---

#### "Too many connections" (SQLSTATE[HY000] [1040])

**Symptoms:**
- Database rejects new connections
- High connection count in database
- Application timeouts under load

**Diagnosis Steps:**
```bash
# 1. Check current connections
docker exec mariadb mysql -e "SHOW STATUS LIKE 'Threads_connected'"

# 2. Check maximum connections
docker exec mariadb mysql -e "SHOW VARIABLES LIKE 'max_connections'"

# 3. Review connection pool settings
php artisan config:show database.connections.mysql.pool
```

**Solutions:**
```bash
# Increase max_connections in MariaDB
# Add to docker-compose.yml:
# command: --max-connections=200

# Optimize connection pool
# DB_POOL_MIN=5
# DB_POOL_MAX=50

# Kill long-running connections
docker exec mariadb mysql -e "SHOW PROCESSLIST"
docker exec mariadb mysql -e "KILL {process_id}"
```

### Migration Issues

#### "Table already exists" Errors

**Symptoms:**
- Migration fails with table exists error
- Database schema out of sync
- Mixed migration states

**Diagnosis Steps:**
```bash
# 1. Check migration status
php artisan migrate:status

# 2. Check actual database schema
docker exec mariadb mysql -e "SHOW TABLES" sso_main

# 3. Compare with migration files
ls database/migrations/
```

**Solutions:**
```bash
# Reset migrations and start fresh (DANGER: Data loss)
php artisan migrate:reset
php artisan migrate

# Or use fresh migration with seed
php artisan migrate:fresh --seed

# For specific tenant databases
php artisan tenant:migrate tenant1 --force
```

---

#### Foreign Key Constraint Failures

**Symptoms:**
- Cannot insert/update records due to FK constraints
- Orphaned records in related tables
- Migration rollback failures

**Diagnosis Steps:**
```bash
# 1. Check foreign key constraints
docker exec mariadb mysql -e "
  SELECT * FROM information_schema.KEY_COLUMN_USAGE 
  WHERE CONSTRAINT_SCHEMA = 'sso_main' 
  AND REFERENCED_TABLE_NAME IS NOT NULL"

# 2. Find orphaned records
php artisan db:check-integrity
```

**Solutions:**
```bash
# Temporarily disable foreign key checks
docker exec mariadb mysql -e "SET FOREIGN_KEY_CHECKS=0"

# Clean up orphaned records
php artisan db:cleanup-orphaned

# Re-enable foreign key checks
docker exec mariadb mysql -e "SET FOREIGN_KEY_CHECKS=1"
```

### Performance Issues

#### Slow Query Performance

**Symptoms:**
- Database queries taking > 1 second
- High CPU usage on database
- Application timeouts

**Diagnosis Steps:**
```bash
# 1. Enable slow query log
docker exec mariadb mysql -e "SET GLOBAL slow_query_log = 'ON'"
docker exec mariadb mysql -e "SET GLOBAL long_query_time = 1"

# 2. Check query performance
php artisan db:monitor --slow-queries

# 3. Analyze specific queries
php artisan db:explain "SELECT * FROM users WHERE email = ?"
```

**Solutions:**
```bash
# Add database indexes
php artisan make:migration add_indexes_to_users_table

# Optimize queries
php artisan db:optimize --analyze

# Enable query caching
# Add to database configuration:
# 'options' => [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true]
```

## 🌐 Network and Infrastructure Issues

### Docker Container Issues

#### Container Startup Failures

**Symptoms:**
- Containers exit immediately after start
- "Failed to start" errors in Docker logs
- Services not accessible

**Diagnosis Steps:**
```bash
# 1. Check container status
docker ps -a

# 2. Review container logs
docker logs central-sso --tail=50
docker logs tenant1-app --tail=50

# 3. Check resource usage
docker stats

# 4. Verify Docker Compose configuration
docker-compose config
```

**Solutions:**
```bash
# Rebuild containers
docker-compose build --no-cache
docker-compose up -d

# Check for port conflicts
netstat -tulpn | grep :8000

# Clear Docker cache if needed
docker system prune -a -f
```

---

#### Network Connectivity Issues

**Symptoms:**
- Services cannot communicate with each other
- DNS resolution failures within Docker network
- API calls between services fail

**Diagnosis Steps:**
```bash
# 1. Check Docker networks
docker network ls
docker network inspect sso-network

# 2. Test connectivity between containers
docker exec central-sso ping mariadb
docker exec tenant1-app ping central-sso

# 3. Check service discovery
docker exec central-sso nslookup mariadb
```

**Solutions:**
```bash
# Recreate Docker network
docker-compose down
docker network prune -f
docker-compose up -d

# Verify network configuration in docker-compose.yml
# Ensure all services are on same network

# Check for firewall issues
sudo ufw status  # Ubuntu
sudo systemctl status firewalld  # CentOS/RHEL
```

### SSL/TLS Issues

#### Certificate Problems

**Symptoms:**
- SSL certificate errors in browser
- HTTPS connections failing
- Certificate expiration warnings

**Diagnosis Steps:**
```bash
# 1. Check certificate status
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# 2. Verify certificate expiration
echo | openssl s_client -connect your-domain.com:443 2>/dev/null | openssl x509 -noout -dates

# 3. Check Cloudflare tunnel status (if using)
cloudflared tunnel info your-tunnel-name
```

**Solutions:**
```bash
# Renew certificate if expired
# For Cloudflare: Automatic renewal
# For Let's Encrypt: certbot renew

# Update certificate in application
# Update SSL_CERT_PATH and SSL_KEY_PATH

# Restart services after certificate update
docker-compose restart
```

## 🚀 Performance Troubleshooting

### High Memory Usage

#### Application Memory Leaks

**Symptoms:**
- Memory usage continuously increasing
- Out of memory errors
- Container restarts due to memory limits

**Diagnosis Steps:**
```bash
# 1. Monitor memory usage
docker stats central-sso

# 2. Check PHP memory settings
php artisan config:show app.memory_limit

# 3. Profile memory usage
php artisan debug:memory --detailed

# 4. Check for memory leaks
php artisan debug:memory-leaks
```

**Solutions:**
```bash
# Increase memory limit temporarily
# PHP_MEMORY_LIMIT=512M

# Optimize queries to reduce memory usage
php artisan db:optimize

# Clear caches regularly
php artisan schedule:run  # If cache clearing is scheduled

# Add memory monitoring
# Set up alerts for high memory usage
```

### High CPU Usage

#### Performance Bottlenecks

**Symptoms:**
- High CPU usage on application containers
- Slow response times
- Request timeouts

**Diagnosis Steps:**
```bash
# 1. Monitor CPU usage
top -p $(docker inspect -f '{{.State.Pid}}' central-sso)

# 2. Profile application performance
php artisan debug:performance --routes

# 3. Check for inefficient queries
php artisan db:monitor --explain-queries

# 4. Review cache hit rates
php artisan cache:stats
```

**Solutions:**
```bash
# Enable opcache for PHP
# Add to PHP configuration:
# opcache.enable=1
# opcache.memory_consumption=128

# Optimize database queries
php artisan db:optimize --indexes

# Increase cache TTL for frequently accessed data
# CACHE_TTL=7200  # 2 hours

# Scale horizontally if needed
# Add more container instances behind load balancer
```

## 🔒 Security Issues

### Suspicious Activity

#### Brute Force Attacks

**Symptoms:**
- High volume of failed login attempts
- Rate limit exceeded errors
- Unusual IP address patterns

**Detection:**
```bash
# 1. Check failed login patterns
php artisan audit:view --action=login --success=false --since=today

# 2. Analyze IP addresses
php artisan security:analyze-ips --suspicious

# 3. Check rate limiting effectiveness
php artisan throttle:stats --login-attempts
```

**Response:**
```bash
# Block suspicious IPs immediately
php artisan firewall:block 192.168.1.100 --reason="Brute force attempt"

# Increase rate limiting temporarily
# THROTTLE_LOGIN_MAX_ATTEMPTS=3
# THROTTLE_LOGIN_DECAY_MINUTES=15

# Force logout all users if needed
php artisan auth:logout-all --reason="Security incident"

# Enable additional monitoring
php artisan security:monitor --real-time
```

---

#### Unauthorized Access Attempts

**Symptoms:**
- Access to unauthorized tenants
- Permission escalation attempts
- Cross-tenant data access

**Investigation:**
```bash
# 1. Audit user permissions
php artisan audit:permissions --suspicious-activity

# 2. Check cross-tenant access attempts
php artisan audit:view --action=cross_tenant_access

# 3. Review recent permission changes
php artisan audit:view --action=permission_change --since=today
```

**Mitigation:**
```bash
# Revoke suspicious user access immediately
php artisan user:suspend 123 --reason="Unauthorized access attempt"

# Review and update user permissions
php artisan user:audit-permissions 123

# Enable stricter tenant isolation
# TENANT_ISOLATION_MODE=strict

# Force re-authentication for affected users
php artisan auth:force-reauth --tenant=tenant1
```

## 🧪 Development and Testing Issues

### Local Development Problems

#### Environment Setup Issues

**Symptoms:**
- Docker containers not starting
- Environment variables not loading
- Service discovery failures

**Common Solutions:**
```bash
# 1. Reset Docker environment
docker-compose down -v  # Remove volumes
docker system prune -a -f
docker-compose up -d

# 2. Check environment file
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 3. Verify Docker daemon
systemctl status docker  # Linux
brew services list | grep docker  # macOS

# 4. Clean and rebuild
docker-compose build --no-cache --pull
```

### Testing Framework Issues

#### Test Database Issues

**Symptoms:**
- Tests failing due to database state
- Foreign key constraint errors in tests
- Test isolation problems

**Solutions:**
```bash
# 1. Reset test database
php artisan migrate:fresh --env=testing
php artisan db:seed --env=testing

# 2. Use database transactions for test isolation
# In test classes: use DatabaseTransactions;

# 3. Clear test cache
php artisan cache:clear --env=testing
php artisan config:clear --env=testing
```

## 📊 Monitoring and Alerting

### Setting Up Monitoring

#### Proactive Monitoring Setup

```bash
# 1. Enable comprehensive logging
LOG_LEVEL=info
LOG_AUDIT_CHANNEL=audit
LOG_SECURITY_CHANNEL=security

# 2. Set up health check endpoints
php artisan route:list | grep health

# 3. Configure performance monitoring
php artisan performance:monitor --enable

# 4. Set up alerting rules
php artisan alerts:configure --critical-only
```

#### Key Metrics to Monitor

**System Health:**
- Application uptime and availability
- Database connection status
- Cache hit rates
- Memory and CPU usage

**Security Metrics:**
- Failed login attempts per minute
- Unusual access patterns
- Token validation failures
- Permission escalation attempts

**Performance Metrics:**
- Response time percentiles
- Database query performance
- Cache performance
- Error rates

**Business Metrics:**
- Active user count
- Authentication success rate
- Cross-tenant usage patterns
- Feature adoption rates

---

## 🆘 Getting Help

### Log Collection for Support

```bash
# Collect comprehensive logs for support
./scripts/collect-logs.sh

# Or manually:
docker logs central-sso > central-sso.log 2>&1
docker logs tenant1-app > tenant1-app.log 2>&1
docker logs mariadb > mariadb.log 2>&1

# Export configuration (sanitized)
php artisan config:export --sanitize > config.json

# Export recent audit logs
php artisan audit:export --since=yesterday --format=json > audit.json
```

### Support Information Template

When contacting support, include:

1. **Environment Information:**
   - Operating system and version
   - Docker and Docker Compose versions
   - Application version/commit hash

2. **Issue Description:**
   - Detailed symptoms
   - Error messages
   - Steps to reproduce

3. **System State:**
   - Container status (`docker ps -a`)
   - Recent logs (last 100 lines)
   - Configuration differences from defaults

4. **Impact Assessment:**
   - Number of affected users
   - Business impact
   - Temporary workarounds in place

---

## 🔗 Related Documentation

- **[Configuration Reference](configuration.md)** - Environment variables and settings
- **[CLI Commands](cli-commands.md)** - Command-line tools for troubleshooting
- **[API Documentation](api.md)** - API error codes and responses
- **[Security Guide](../guides/security.md)** - Security best practices and incident response