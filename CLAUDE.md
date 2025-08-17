# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Complete SSO (Single Sign-On) proof of concept built with Laravel and Docker. The system consists of a central SSO server and multiple tenant applications that authenticate through the central server using JWT tokens.

## Project Structure

```
sso-poc-claude3/
├── central-sso/          # Main SSO authentication server (Laravel)
├── tenant1-app/          # Tenant 1 application (Laravel)
├── tenant2-app/          # Tenant 2 application (Laravel)
├── docs/                 # Documentation (including original CLAUDE.md)
├── docker-compose.yml    # Docker services configuration
└── CLAUDE.md            # This file
```

## Quick Start

```bash
# Configure environment (copy and customize as needed)
cp .env.docker .env

# Start all services
docker compose up -d

# Run database migrations
docker exec central-sso php artisan migrate

# Seed test users
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder

# Access applications
# Central SSO: http://localhost:8000
# Tenant 1: http://localhost:8001
# Tenant 2: http://localhost:8002
```

## Environment Configuration

The SSO system now uses a centralized environment configuration system for Docker Compose deployments:

### 📄 Configuration Files
- **`.env.docker`** - Template with all configurable variables and defaults
- **`.env`** - Your customized environment file (copy from .env.docker)
- **`.env.example`** - Comprehensive reference for all possible variables

### 🔧 Key Configuration Areas

**Application Settings:**
- Service-specific APP_KEY, APP_NAME, APP_URL for each application
- Shared environment settings (APP_ENV, APP_DEBUG, LOG_LEVEL)

**Database Configuration:**
- Centralized database credentials (DB_USERNAME, DB_PASSWORD)
- Per-service database names (CENTRAL_SSO_DB_DATABASE, TENANT1_DB_DATABASE, etc.)

**Security Settings:**
- JWT secrets and API keys for secure communication
- Session configuration with separate cookies per service
- HMAC secrets for request signing

**Docker Settings:**
- Configurable container names and port mappings
- Volume configurations and service dependencies

### 🚀 Deployment Scenarios

**Development:**
```bash
cp .env.docker .env
# Edit .env as needed
docker compose up -d
```

**Production:**
```bash
cp .env.docker .env
# Update secrets, URLs, and security settings
# Set APP_ENV=production, APP_DEBUG=false
docker compose up -d
```

## Architecture

### 🏗️ Dual-Session Architecture

The system implements a **dual-session architecture** combining centralized authentication with local session management:

- **Central SSO Server** (`localhost:8000`): Laravel application handling authentication, user management, and JWT token generation
- **Tenant Applications** (`localhost:8001`, `localhost:8002`): Laravel applications using dual-session architecture:
  - **Direct Login**: Users can login directly with their SSO credentials
  - **API Authentication**: All credentials validated through central SSO API
  - **Local Sessions**: Laravel sessions created for each tenant app independently
  - **Data Synchronization**: User data automatically synced from central SSO
  - **SSO Redirect**: Traditional SSO flow also available
- **MariaDB Database**: Stores users, tenants, relationships, and audit logs
- **Docker Network**: All services communicate via Docker network

### Authentication Methods

1. **Direct Login to Tenant Apps**: User fills login form in tenant app → Tenant app makes API call to central SSO for authentication → Local Laravel session created with JWT token stored
2. **SSO Redirect Flow**: User clicks "Login with Central SSO" in tenant app → Redirected to central SSO for authentication → Same result as direct login but different user experience

## Common Commands

### Database Operations
```bash
# Run migrations
docker exec central-sso php artisan migrate

# Seed test data
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder

# Connect to database
docker exec -it mariadb mysql -u sso_user -psso_password sso_main

# Clear cache
docker exec central-sso php artisan cache:clear
```

### Testing

```bash
# Run all audit system tests
./run_tests.sh

# Individual test suites
docker exec central-sso php artisan test:login-audit
docker exec tenant1-app php artisan test:tenant-audit
docker exec tenant2-app php artisan test:tenant-audit
```

## Test Credentials

All users use password: **password**

### Authentication Capabilities
✅ **All users can login using BOTH methods:**
- **Direct Login**: Fill login form directly in tenant apps (`localhost:8001/login`, `localhost:8002/login`)
- **SSO Redirect**: Click "Login with Central SSO" button for traditional SSO flow

### Users
- `user@tenant1.com` / `password` (Tenant 1 User)
- `admin@tenant1.com` / `password` (Tenant 1 Admin) 
- `user@tenant2.com` / `password` (Tenant 2 User)
- `admin@tenant2.com` / `password` (Tenant 2 Admin)
- `superadmin@sso.com` / `password` (Access to both tenants)

## Key Features

### Tenant Management & URL Slugs
The SSO system uses **tenant slugs** for clean, user-friendly URLs and tenant identification.

#### Benefits of Using Slugs:
1. **Clean URLs**: `/auth/acme-corp` instead of `/auth/123`
2. **User Experience**: Users can understand which tenant they're accessing
3. **Branding**: Tenants can have branded URLs matching their identity

#### URL Structure:
- **SSO Login**: `http://localhost:8000/auth/{tenant_slug}`

### 🎨 Modern UI Design
- **shadcn/ui Design System**: Complete design system with dark/light themes
- **Modern Landing Page**: Professional UI with gradient design and responsive layout
- **Accessible Components**: WCAG compliant components with proper color contrast
- **Interactive Elements**: Toast notifications, modals, dropdowns with Alpine.js

### 🔄 Authentication Flows

1. **Dual-Session Direct Login** (Primary): Users login directly in tenant apps, credentials validated through central SSO API, local Laravel session created
2. **Central SSO Login**: Users login at central SSO and access tenant dashboard
3. **Seamless SSO Redirect**: Users click "Login with SSO" in tenant apps for automatic authentication
4. **API Authentication**: Direct API calls for programmatic access with JWT tokens
5. **Multi-Tenant Support**: Users can have access to multiple tenants with proper access control

### Administration & Management
- **User Management**: Complete user lifecycle management with tenant access control
- **Role Management**: Granular role-based access control system (central SSO only)
- **Tenant Management**: Multi-tenant configuration and user assignment
- **Admin Interface**: Modern, responsive admin panel with shadcn/ui design
- **Real-time Updates**: Live data refresh without page reloads

### Role-Based Access Control (RBAC) - Central SSO Only
- **Scope**: Roles and permissions apply **only to the central SSO server**
- **Tenant Applications**: Each tenant application manages its own separate role system
- **Multi-Tenant Roles**: Users can have different roles in different tenants within the central SSO system
- **Granular Permissions**: 19 built-in permissions across 6 categories for SSO management
- **Default Roles**: Super Admin, Admin, Manager, User, Viewer with pre-configured permissions

#### Built-in Permissions:
- **Users**: view, create, edit, delete
- **Roles**: view, create, edit, delete, assign
- **Tenants**: view, create, edit, delete
- **System**: settings, logs
- **API**: manage
- **Developer**: telescope.access, swagger.access
- **Profile**: view.own, edit.own, view.all, edit.all, export, analytics

### User Profile Management
Extended user profiles with personal, contact, and professional details:
- **Family Member Management**: Track family relationships, emergency contacts
- **Contact Information**: Multiple contact methods including phone, email
- **Address Management**: Multiple addresses (home, work, billing, shipping)
- **Social Media Integration**: Track and manage user social media profiles

### Login Audit System
Comprehensive authentication tracking across the entire SSO ecosystem:
- **Universal Tracking**: Records authentication events from all applications
- **Multi-Method Support**: Tracks direct logins, SSO logins, and API authentication
- **Real-Time Analytics**: Live dashboard with auto-refresh capabilities
- **Failed Attempt Monitoring**: Detailed tracking of unsuccessful login attempts

## 🔒 Enterprise Security Implementation

The SSO system implements **enterprise-grade security** with multiple layers of protection:

### Security Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Security Layers                        │
├─────────────────────────────────────────────────────────────┤
│ 1. TrustProxies Middleware (HTTPS Detection)               │
│ 2. API Key Authentication (Server-to-Server)               │
│ 3. HMAC Request Signing (Tamper Protection)                │
│ 4. Multi-Layer Rate Limiting (DoS Protection)              │
│ 5. Request ID Tracking (Audit Trail)                       │
│ 6. SSL/TLS Encryption (Data in Transit)                    │
│ 7. JWT Token Security (Session Management)                 │
│ 8. CSRF Protection (Web UI)                                │
└─────────────────────────────────────────────────────────────┘
```

### 🛡️ TrustProxies Middleware
- **HTTPS Detection**: Properly detects HTTPS requests behind Cloudflare proxy
- **Cloudflare Integration**: Pre-configured with Cloudflare IP ranges for automatic proxy detection
- **Flexible Configuration**: Supports `TRUSTED_PROXIES=*` for development or specific IP ranges for production
- **Essential for CSRF**: Enables proper session cookie handling and CSRF token validation on HTTPS

### 🔑 API Key Authentication
- **Tenant-Specific Keys**: Each tenant has a unique API key for identification
- **Header-Based Authentication**: Keys passed via `X-API-Key` header
- **Key Format**: `tenant{id}_{32_character_hash}`

### 🔐 HMAC Request Signing
- **Algorithm**: HMAC-SHA256 with shared secret
- **Canonical Requests**: Standardized request string format for consistent signing
- **Signature Headers**: `X-Signature` header contains request signature
- **Timestamp Validation**: `X-Timestamp` header prevents replay attacks

### ⚡ Multi-Layer Rate Limiting
- **By IP Address**: Prevent single IP from overwhelming system
- **By API Key**: Prevent single tenant from exceeding limits
- **By Endpoint**: Different limits for different operations
- **Rate Limits**: Auth endpoints: 10 req/min, API endpoints: 60 req/min, Audit: 100 req/min

### 📝 Request ID Tracking
- **Unique IDs**: UUID v4 for each request
- **Cross-Service Tracking**: Request IDs passed between services
- **Audit Integration**: All audit logs include request IDs

## Tools & Documentation

### Laravel Telescope
- **URL**: `http://localhost:8000/telescope`
- Monitor requests, database queries, exceptions
- Available only in development environment

### API Documentation
- **Swagger UI**: `http://localhost:8000/api/documentation`
- **Quick Access**: `http://localhost:8000/docs`
- Interactive API documentation with request/response schemas
- Available only in development environment

## Common Issues

### 🚨 500 Error - Permission Issues (Most Common)
**Symptoms**: Getting 500 errors when accessing applications
**Cause**: Docker bind mounts preserve host file ownership, but Laravel needs www-data (UID 33) to write to storage
**Quick Fix**:
```bash
# Fix from host machine (not inside Docker)
sudo chown -R 33:33 {central-sso,tenant1-app,tenant2-app}/{storage,bootstrap/cache}
sudo chmod -R 775 {central-sso,tenant1-app,tenant2-app}/{storage,bootstrap/cache}
docker compose restart
```
**Script Fix**: `./scripts/fix-permissions.sh`

### 🔒 SSL/HTTPS Connection Issues
**Symptoms**: ERR_SSL_VERSION_OR_CIPHER_MISMATCH or connection refused errors when accessing HTTPS domains
**Cause**: Cloudflare tunnel configuration or SSL certificate issues
**Quick Fix**:
```bash
# Diagnose SSL issues
./scripts/troubleshoot-ssl.sh

# Check tunnel status
docker logs cloudflared-sso

# Verify DNS pointing to Cloudflare (should show Cloudflare IPs)
dig sso.poc.hi-dil.com
```
**Complete Fix**: `./scripts/fix-https-csrf.sh`

### 🛡️ 419 Page Expired (CSRF Token) Errors
**Symptoms**: "Page Expired" errors when submitting forms on HTTPS deployment
**Cause**: Laravel cannot detect HTTPS properly behind Cloudflare proxy, breaking CSRF token validation
**Root Cause**: Missing TrustProxies middleware configuration
**Quick Fix**:
```bash
# Automatic fix for CSRF and HTTPS issues
./scripts/fix-https-csrf.sh

# Manual fix - ensure these are in your .env:
echo 'TRUSTED_PROXIES=*' >> .env
echo 'SESSION_SECURE_COOKIE=true' >> .env
echo 'SESSION_DOMAIN=.poc.hi-dil.com' >> .env
docker-compose restart
```
**Important**: TrustProxies middleware is pre-configured in all applications to handle Cloudflare proxy detection.

### Other Common Issues
- **Invalid credentials**: Ensure using MariaDB, not SQLite
- **Database connection**: Check Docker containers are running
- **Token validation**: Verify tenant associations in database
- **Domain consistency**: All apps must use `localhost` domain for session sharing
- **SSO authentication not working**: Check that Laravel Telescope is installed in all apps
- **Access denied errors**: Check user-tenant relationships in database

## Important Notes

### 🏗️ Dual-Session Architecture
- **Primary Login Method**: Users can login directly to tenant apps using SSO credentials
- **API-Based Authentication**: All credentials validated through central SSO API for consistency
- **Local Session Management**: Each tenant app maintains independent Laravel sessions
- **Automatic User Sync**: User data synchronized from central SSO on every login
- **Audit Integration**: All authentications tracked in central audit system

### System Requirements
- Database is MariaDB running in Docker, not SQLite
- All services must be running via Docker Compose
- Test users are seeded via `AddTestUsersSeeder`
- Tenant relationships are stored in `tenant_users` pivot table
- JWT claims include `tenants` array and `current_tenant`
- **Domain Consistency**: All apps use `localhost` domain to ensure proper session sharing
- **Laravel Telescope**: Required dependency for all applications to function properly

### Authentication Features
- **Dual Login Support**: Both direct login and SSO redirect work identically
- **Processing Page**: SSO authentication uses JavaScript-based checking for seamless UX
- **Laravel Authentication**: Tenant apps use Laravel's built-in auth system with local user accounts
- **User Synchronization**: SSO users are automatically created/updated as local users in tenant apps
- **Cross-Tenant Access**: Same credentials work across all tenant applications

---

# New Tenant Application Integration Guide

## Quick Integration Steps

1. **Prerequisites**: Laravel 11, Docker, MariaDB database
2. **Dependencies**: `composer require tymon/jwt-auth laravel/telescope guzzlehttp/guzzle`
3. **Environment**: Add SSO configuration to `.env`
4. **Database**: Create users table with `sso_user_id` field
5. **SSO Service**: Create service class to handle authentication
6. **Controllers**: Add SSO authentication controller
7. **Routes**: Configure SSO routes and callbacks
8. **Views**: Create SSO processing and login pages
9. **Docker**: Add tenant app to docker-compose.yml
10. **Register**: Add tenant to central SSO database

## Tenant Integration Environment

For new tenant applications, add these variables to your `.env` file:

```env
# Central SSO Configuration
CENTRAL_SSO_URL=http://central-sso:8000
TENANT_SLUG=your-tenant-slug

# For secure integration (production)
TENANT_API_KEY=tenant3_SECURE_32_CHAR_API_KEY_HERE
HMAC_SECRET=64_CHAR_HMAC_SECRET_FROM_CENTRAL_SSO
```

**Note:** For Docker Compose deployments, these variables are centrally managed in the main `.env.docker` file.

## Register Tenant in Central SSO

```sql
-- Connect to central SSO database
docker exec -it mariadb mysql -u sso_user -psso_password sso_main

-- Insert new tenant
INSERT INTO tenants (id, slug, name, domain, description, is_active, created_at, updated_at) 
VALUES ('your-tenant-slug', 'your-tenant-slug', 'Your Tenant Name', 'localhost:8003', 'Description', 1, NOW(), NOW());

-- Grant access to test users
INSERT INTO tenant_users (user_id, tenant_id, created_at, updated_at)
SELECT id, 'your-tenant-slug', NOW(), NOW() 
FROM users WHERE email IN ('superadmin@sso.com', 'admin@tenant1.com');
```

---

# 🔒 Secure Tenant Integration

For production environments, use **SecureSSOService** with enterprise-grade security:

## Security Features Included
- ✅ **API Key Authentication**: Every request authenticated with tenant-specific keys
- ✅ **HMAC Request Signing**: All requests cryptographically signed to prevent tampering
- ✅ **Rate Limiting**: Protection against brute force and DoS attacks
- ✅ **Comprehensive Audit**: All authentication events logged to central system
- ✅ **SSL/TLS Support**: Encrypted communication in production
- ✅ **Token Validation**: JWT tokens verified through secure API calls
- ✅ **Error Handling**: Graceful degradation if security services are unavailable

## 🚀 Quick Setup with Template

```bash
# Copy the secure tenant template
cp -r tenant-template/ my-new-tenant-app/
cd my-new-tenant-app/

# Follow the setup guide
cat SETUP.md
```

## 📈 Complete Documentation

For detailed documentation, see `/docs/` directory:
- **Original CLAUDE.md**: Complete documentation at `/docs/CLAUDE-original.md`
- **Architecture**: Detailed system architecture documentation
- **Security**: Enterprise security implementation guide
- **Production Deployment**: Complete production setup guide
- **API Documentation**: REST API reference
- **Testing Guide**: Comprehensive testing strategies

---

**Security Note**: Current implementation provides enterprise-grade protection suitable for production deployments. Phase 3 Advanced Security features (AI-powered security, zero-trust architecture) are planned for future development.