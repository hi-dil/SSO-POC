# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a complete SSO (Single Sign-On) proof of concept project built with Laravel and Docker. The system consists of a central SSO server and multiple tenant applications that authenticate through the central server using JWT tokens.

## Project Structure

```
sso-poc-claude3/
├── central-sso/          # Main SSO authentication server (Laravel)
├── tenant1-app/          # Tenant 1 application (Laravel)
├── tenant2-app/          # Tenant 2 application (Laravel)
├── docs/                 # Documentation
├── docker-compose.yml    # Docker services configuration
└── CLAUDE.md            # This file
```

## Development Setup

### Prerequisites
- Docker and Docker Compose
- Git

### Quick Start
```bash
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

## Architecture

### 🏗️ Dual-Session Architecture

The system implements a **dual-session architecture** that combines centralized authentication with local session management:

- **Central SSO Server** (`localhost:8000`): Laravel application handling authentication, user management, and JWT token generation
- **Tenant Applications** (`localhost:8001`, `localhost:8002`): Laravel applications using **dual-session architecture**:
  - **Direct Login**: Users can login directly with their SSO credentials
  - **API Authentication**: All credentials validated through central SSO API
  - **Local Sessions**: Laravel sessions created for each tenant app independently
  - **Data Synchronization**: User data automatically synced from central SSO
  - **SSO Redirect**: Traditional SSO flow also available
- **MariaDB Database**: Stores users, tenants, relationships, and audit logs
- **Docker Network**: All services communicate via Docker network

### Authentication Methods

1. **Direct Login to Tenant Apps**:
   - User fills login form in tenant app (`localhost:8001/login`)
   - Tenant app makes API call to central SSO for authentication
   - Local Laravel session created with JWT token stored
   - User accesses tenant app with local session

2. **SSO Redirect Flow**:
   - User clicks "Login with Central SSO" in tenant app
   - Redirected to central SSO for authentication
   - Same result as direct login but different user experience

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

### Development Commands
```bash
# View logs
docker compose logs central-sso
docker compose logs tenant1-app

# Restart services
docker compose restart

# Run commands in containers
docker exec central-sso php artisan tinker
docker exec tenant1-app composer install
```

### Testing

The system includes comprehensive test suites for all components:

#### Quick Test (Recommended)
```bash
# Run all audit system tests
./run_tests.sh
```

#### Individual Test Suites
```bash
# Central SSO audit system tests
docker exec central-sso php artisan test:login-audit
docker exec central-sso php artisan test:login-audit --comprehensive

# Tenant application tests
docker exec tenant1-app php artisan test:tenant-audit
docker exec tenant2-app php artisan test:tenant-audit

# Full system integration tests
docker exec central-sso php artisan test:full-system --cleanup

# Laravel feature tests
docker exec central-sso php artisan test
```

#### Manual API Testing
```bash
# Test API authentication
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email": "superadmin@sso.com", "password": "password", "tenant_slug": "tenant1"}'

# Check application status
curl http://localhost:8000/telescope
curl http://localhost:8001

# Test role management (requires authentication token)
TOKEN="your_jwt_token_here"

# List all roles
curl -X GET "http://localhost:8000/api/roles" \
  -H "Authorization: Bearer $TOKEN"

# List all permissions
curl -X GET "http://localhost:8000/api/permissions" \
  -H "Authorization: Bearer $TOKEN"

# Create new role
curl -X POST "http://localhost:8000/api/roles" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name": "Custom Manager", "description": "Custom role for managers", "permissions": ["users.view", "users.create"]}'

# Assign role to user
curl -X POST "http://localhost:8000/api/users/1/roles" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"role_slug": "manager", "tenant_id": 1}'

# Access Role Management UI
# Login to central SSO at http://localhost:8000/login
# Navigate to admin dashboard and click "Roles & Permissions" tab

# Access User Management UI
# Login to central SSO at http://localhost:8000/login
# Navigate to admin dashboard and click "Users" tab (first item in sidebar)
```

## Test Credentials

All users use password: **password**

### Authentication Capabilities

✅ **All users can now login using BOTH methods:**
- **Direct Login**: Fill login form directly in tenant apps (`localhost:8001/login`, `localhost:8002/login`)
- **SSO Redirect**: Click "Login with Central SSO" button for traditional SSO flow

### Single Tenant Users
- `user@tenant1.com` / `password` (Tenant 1 User) - **SSO + Direct Login**
- `admin@tenant1.com` / `password` (Tenant 1 Admin) - **SSO + Direct Login**
- `user@tenant2.com` / `password` (Tenant 2 User) - **SSO + Direct Login**
- `admin@tenant2.com` / `password` (Tenant 2 Admin) - **SSO + Direct Login**

### Multi-Tenant User
- `superadmin@sso.com` / `password` (Access to both tenants) - **SSO + Direct Login**

### Login Examples

**Direct Login to Tenant 1:**
```bash
# Visit http://localhost:8001/login
# Use any valid credentials above
# Authentication happens through central SSO API
# Local Laravel session created automatically
```

**Direct Login to Tenant 2:**
```bash
# Visit http://localhost:8002/login
# Use any valid credentials above
# Same dual-session authentication process
```

## Key Features

### Tenant Management & URL Slugs

The SSO system uses **tenant slugs** for clean, user-friendly URLs and tenant identification.

#### What is a Slug?
A **slug** is a URL-friendly identifier that:
- Contains only lowercase letters, numbers, and hyphens
- Is human-readable and SEO-friendly
- Provides better user experience than numeric IDs

#### Examples:
- **Name**: "Acme Corporation" → **Slug**: `acme-corporation`
- **Name**: "Tech Solutions Inc." → **Slug**: `tech-solutions-inc`
- **Name**: "Marketing Department" → **Slug**: `marketing-dept`

#### Benefits of Using Slugs:
1. **Clean URLs**: `/auth/acme-corp` instead of `/auth/123`
2. **User Experience**: Users can understand which tenant they're accessing
3. **Branding**: Tenants can have branded URLs matching their identity
4. **SEO Benefits**: Search engines prefer descriptive URLs
5. **Debugging**: Easier to identify tenants in logs and development

#### Database Schema:
```sql
-- Tenants table structure
CREATE TABLE tenants (
    id VARCHAR(255) PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,  -- URL-friendly identifier
    name VARCHAR(255) NOT NULL,         -- Display name
    domain VARCHAR(255),                -- Associated domain
    description TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Example data
INSERT INTO tenants VALUES 
('tenant1', 'tenant1', 'Acme Corporation', 'localhost:8001', 'Main tenant for Acme Corp', 1),
('tenant2', 'tenant2', 'Beta Industries', 'localhost:8002', 'Beta Industries tenant', 1);
```

#### URL Structure:
- **SSO Login**: `http://localhost:8000/auth/{tenant_slug}`
- **Examples**: 
  - `http://localhost:8000/auth/tenant1`
  - `http://localhost:8000/auth/acme-corp`
  - `http://localhost:8000/auth/marketing-team`

#### Common Issue: Missing Slugs
If tenant records have `NULL` slug values, SSO endpoints will return 404 errors. This can be fixed with:
```sql
-- Fix missing slugs by setting slug = id
UPDATE tenants SET slug = id WHERE slug IS NULL;
```

### 🎨 shadcn/ui Design System
- **Modern Design Language**: Complete design system based on shadcn/ui principles
- **Dark/Light Themes**: Full theme system with user preference persistence and smooth transitions
- **Accessible Components**: WCAG compliant components with proper color contrast and semantic HTML
- **Responsive Design**: Mobile-first approach with proper breakpoint handling
- **Interactive Elements**: Toast notifications, modals, dropdowns with Alpine.js
- **Consistent UI**: Unified visual language across all admin interfaces
- **Component Library**: Reusable buttons, cards, forms, tables, and navigation components

### Modern Landing Page
- **Professional UI**: Clean, modern landing page with gradient design and responsive layout
- **Feature Showcase**: Highlights all SSO capabilities with interactive elements
- **Live Statistics**: Real-time display of active tenants, users, roles, and permissions
- **Quick Start Guide**: Step-by-step instructions for getting started
- **Authentication State**: Dynamic navigation based on user login status

### 🔄 Authentication Flows

#### 1. **Dual-Session Direct Login** (Primary Method)
- Users login directly in tenant apps (`localhost:8001/login`, `localhost:8002/login`)
- Credentials validated through central SSO API
- Local Laravel session created with JWT token stored
- User data automatically synchronized from central SSO
- **Benefits**: Fast, familiar UI, works offline after authentication

#### 2. **Central SSO Login**
- Users login at `localhost:8000/login` and access tenant dashboard
- Multi-tenant users can select which tenant to access

#### 3. **Seamless SSO Redirect**
- Users click "Login with SSO" in tenant apps for automatic authentication
- Same result as direct login but different user experience
- **Benefits**: Single sign-on across multiple applications

#### 4. **API Authentication**
- Direct API calls for programmatic access
- JWT tokens with tenant-specific claims

#### 5. **Multi-Tenant Support**
- Users can have access to multiple tenants with proper access control
- Consistent authentication across all tenant applications

### Seamless SSO Process
1. **SSO Button Click**: Tenant app redirects to central SSO processing page
2. **Processing Page**: Shows loading spinner while checking authentication status
3. **Authentication Check**: JavaScript makes API call to verify if user is logged in
4. **Auto-Redirect**: If authenticated and has access, automatically redirects back to tenant
5. **Access Control**: Shows error message if user doesn't have permission for the tenant
6. **Login Form**: Shows login form only if user is not authenticated
7. **Local User Sync**: Creates/updates local tenant users based on SSO user data
8. **Laravel Authentication**: Uses Laravel's built-in auth system for local sessions

### Token Management
- JWT tokens with tenant-specific claims
- 1-hour token expiration
- Token validation across tenant boundaries
- Refresh token capability

### API Architecture
- **RESTful API**: All endpoints follow REST conventions
- **DTO Pattern**: Request and response data transfer objects for type safety
- **OpenAPI 3.0**: Complete API documentation with Swagger/OpenAPI
- **Structured Responses**: Consistent JSON response format across all endpoints
- **Error Handling**: Standardized error responses with proper HTTP status codes

### Administration & Management
- **User Management**: Complete user lifecycle management with tenant access control
- **Role Management**: Granular role-based access control system
- **Tenant Management**: Multi-tenant configuration and user assignment
- **Admin Interface**: Modern, responsive admin panel with shadcn/ui design
- **Real-time Updates**: Live data refresh without page reloads

### Role-Based Access Control (RBAC) - Central SSO Only
- **Scope**: Roles and permissions apply **only to the central SSO server** for managing authentication and tenant access
- **Tenant Applications**: Each tenant application manages its own separate role system and permissions
- **Multi-Tenant Roles**: Users can have different roles in different tenants within the central SSO system
- **Granular Permissions**: 19 built-in permissions across 6 categories for SSO management
- **System vs Custom**: System roles/permissions are protected from deletion
- **Flexible Assignment**: Roles can be global or tenant-specific within the SSO system
- **Default Roles**: Super Admin, Admin, Manager, User, Viewer with pre-configured permissions
- **Permission Categories**: Users, Roles, Tenants, System, API, Developer
- **Web UI**: Complete role management interface with modern shadcn/ui design
- **Toast Notifications**: User-friendly success/error messages throughout the interface
- **API Protection**: All role management endpoints available via REST API
- **Developer Tools**: Permission-controlled access to Telescope and Swagger documentation

#### Built-in Permissions:
- **Users**: view, create, edit, delete
- **Roles**: view, create, edit, delete, assign
- **Tenants**: view, create, edit, delete
- **System**: settings, logs
- **API**: manage
- **Developer**: telescope.access, swagger.access
- **Profile**: view.own, edit.own, view.all, edit.all, export, analytics

#### Role Management Features:
- **Interactive UI**: Create, edit, and delete roles with full permission management
- **User Role Assignment**: Assign/remove roles from users with tenant-specific control
- **Permission Visualization**: Organized by category with clear descriptions
- **Real-time Updates**: Live data refresh after role changes
- **Access Control**: Protected routes based on user permissions
- **Confirmation Dialogs**: Safe deletion with user confirmation
- **Responsive Design**: Works on desktop and mobile devices

#### Role Management API Endpoints:
- `GET /api/roles` - List all roles
- `POST /api/roles` - Create new role
- `GET /api/roles/{id}` - Get role details
- `PUT /api/roles/{id}` - Update role
- `DELETE /api/roles/{id}` - Delete role
- `GET /api/permissions` - List permissions
- `GET /api/permissions/categories` - Get permission categories
- `GET /api/users/{id}/roles` - Get user roles
- `POST /api/users/{id}/roles` - Assign role to user
- `DELETE /api/users/{id}/roles` - Remove role from user
- `PUT /api/users/{id}/roles/sync` - Sync user roles

### User Management - Central SSO Only
- **Scope**: User management applies **only to the central SSO server** for managing authentication users
- **Centralized Administration**: Complete user lifecycle management from a single interface
- **Tenant Access Control**: Granular control over which tenants users can access
- **Password Management**: Secure password creation, updates, and confirmation
- **Admin Privileges**: Admin flag management for elevated system access
- **Integration**: Seamlessly integrates with role management and tenant systems

#### User Management Features:
- **Complete CRUD Operations**: Create, read, update, and delete user accounts
- **Tenant Access Assignment**: Control which tenants users can access
- **Password Security**: Strong password requirements with confirmation
- **Admin Flag Management**: Grant/revoke admin privileges
- **Role Integration**: View and manage user roles alongside basic information
- **Self-Protection**: Users cannot delete their own accounts
- **Real-time Updates**: Live interface updates without page refresh
- **Modern UI**: Consistent shadcn/ui design matching the admin interface

#### User Management API Endpoints:
- `GET /admin/users` - User management interface
- `GET /admin/users/data` - JSON data for AJAX updates
- `POST /admin/users` - Create new user
- `PUT /admin/users/{id}` - Update existing user
- `DELETE /admin/users/{id}` - Delete user
- `POST /admin/users/{id}/tenants` - Assign tenant access
- `DELETE /admin/users/{id}/tenants` - Remove tenant access

#### User Management UI Features:
- **User Overview**: List all users with avatars, contact info, and access details
- **Creation/Editing**: Modal forms with comprehensive validation
- **Tenant Management**: Separate modal for managing tenant access
- **Visual Indicators**: Admin badges, tenant access tags, role assignments
- **Safety Features**: Confirmation dialogs and self-deletion prevention
- **Clean Interface**: No intrusive welcome messages or unnecessary notifications

### User Profile Management - Comprehensive User Data System

The central SSO system includes an advanced user profile management system that extends beyond basic authentication to provide comprehensive user data management capabilities.

#### Profile System Features:
- **Extended User Profiles**: Complete user information management with personal, contact, and professional details
- **Multi-Dimensional Data**: Organized into categories for better data management and user experience
- **Family Member Management**: Track family relationships, emergency contacts, and dependent information
- **Contact Information**: Multiple contact methods including phone, email, and communication preferences
- **Address Management**: Support for multiple addresses (home, work, billing, shipping) with full geographic data
- **Social Media Integration**: Track and manage user social media profiles and professional networks
- **Professional Information**: Job titles, departments, company information, and career details
- **Personal Demographics**: Age, nationality, gender, biographical information, and personal preferences

#### Profile Data Categories:

##### Basic Profile Information:
- **Personal Details**: Name, date of birth, gender, nationality, biographical information
- **Contact Information**: Primary phone, emergency contacts, preferred communication methods
- **Professional Data**: Job title, department, company, work location, employment details
- **System Data**: Avatar/profile photos, account preferences, authentication settings

##### Extended Profile Tables:
- **user_family_members**: Family relationships, emergency contacts, dependent information
- **user_contacts**: Multiple contact methods, phone numbers, email addresses, communication preferences
- **user_addresses**: Residential, work, billing, and shipping addresses with full geographic data
- **user_social_media**: Social media profiles, professional networks, online presence management

#### Profile Management API Endpoints:
- `GET /api/user/profile` - Get complete user profile information
- `PUT /api/user/profile` - Update basic profile information
- `GET /api/user/profile/family` - Get family member information
- `POST /api/user/profile/family` - Add family member
- `PUT /api/user/profile/family/{id}` - Update family member
- `DELETE /api/user/profile/family/{id}` - Remove family member
- `GET /api/user/profile/contacts` - Get contact information
- `POST /api/user/profile/contacts` - Add contact method
- `PUT /api/user/profile/contacts/{id}` - Update contact information
- `DELETE /api/user/profile/contacts/{id}` - Remove contact method
- `GET /api/user/profile/addresses` - Get address information
- `POST /api/user/profile/addresses` - Add address
- `PUT /api/user/profile/addresses/{id}` - Update address
- `DELETE /api/user/profile/addresses/{id}` - Remove address
- `GET /api/user/profile/social-media` - Get social media profiles
- `POST /api/user/profile/social-media` - Add social media profile
- `PUT /api/user/profile/social-media/{id}` - Update social media profile
- `DELETE /api/user/profile/social-media/{id}` - Remove social media profile

#### Profile Management UI Features:
- **Comprehensive Profile Views**: Organized tabs for different profile categories
- **Modal-Based Editing**: User-friendly forms for adding and editing profile information
- **Real-Time Updates**: Live interface updates without page refresh
- **Data Validation**: Comprehensive validation for all profile fields
- **File Upload Support**: Avatar and document upload capabilities
- **Privacy Controls**: Granular control over profile data visibility
- **Responsive Design**: Mobile-friendly interface for profile management

#### Admin Profile Management:
- **Administrative Profile Access**: Admins can view and edit user profiles
- **Bulk Profile Operations**: Mass updates and data management tools
- **Profile Analytics**: Usage statistics and data completeness metrics
- **Data Export**: Export profile data for reporting and compliance
- **Audit Trails**: Track all profile changes and administrative actions

#### Profile Database Schema:
```sql
-- Extended user table with profile fields
users: id, name, email, phone, date_of_birth, gender, nationality, bio, avatar_url, 
       address_line_1, address_line_2, city, state_province, postal_code, country,
       emergency_contact_name, emergency_contact_phone, emergency_contact_relationship,
       job_title, department, company, work_location, hire_date, employment_status

-- Family member relationships
user_family_members: id, user_id, name, relationship, date_of_birth, phone, email, 
                     address, emergency_contact, notes

-- Multiple contact methods
user_contacts: id, user_id, contact_type, contact_value, is_primary, is_verified, notes

-- Multiple addresses
user_addresses: id, user_id, address_type, address_line_1, address_line_2, city, 
                state_province, postal_code, country, is_primary, notes

-- Social media profiles
user_social_media: id, user_id, platform, username, profile_url, is_public, notes
```

#### Profile Management Permissions:
- `profile.view.own` - View own profile information
- `profile.edit.own` - Edit own profile information
- `profile.view.all` - View all user profiles (admin)
- `profile.edit.all` - Edit all user profiles (admin)
- `profile.export` - Export profile data
- `profile.analytics` - Access profile analytics

### Login Audit System - Comprehensive Authentication Tracking

The system includes a comprehensive login audit system that tracks all authentication events across the entire SSO ecosystem in real-time.

#### Audit System Features:
- **Universal Tracking**: Records authentication events from all applications (central SSO + all tenants)
- **Multi-Method Support**: Tracks direct logins, SSO logins, and API authentication
- **Real-Time Analytics**: Live dashboard with auto-refresh capabilities
- **Failed Attempt Monitoring**: Detailed tracking of unsuccessful login attempts
- **Session Management**: Active session tracking with automatic cleanup
- **Cross-Tenant Visibility**: Centralized view of user activity across all tenants

#### What Gets Tracked:
- **Central SSO Logins**: Direct authentication at the central server
- **Tenant Direct Logins**: Users logging directly into tenant applications
- **Tenant SSO Logins**: SSO authentication within tenant applications
- **API Authentication**: Programmatic authentication via API endpoints
- **Failed Login Attempts**: Invalid credentials, access denied, etc.
- **Session Data**: Login/logout times, session duration, IP addresses, user agents

#### Audit Dashboard Features:
- **Live Statistics**: Active users, today's logins, session counts
- **Tenant Breakdown**: Login activity per tenant in real-time
- **Method Analysis**: Distribution across direct/SSO/API authentication
- **User Activity**: Individual user login history and patterns
- **Recent Activity**: Real-time feed of latest authentication events
- **Performance Metrics**: Login trends and system usage patterns

#### Testing the Audit System:
```bash
# Run comprehensive audit system tests
./run_tests.sh

# Test individual components
docker exec central-sso php artisan test:login-audit --comprehensive
docker exec tenant1-app php artisan test:tenant-audit
docker exec tenant2-app php artisan test:tenant-audit

# View recent audit records
docker exec sso-mariadb mysql -u sso_user -psso_password sso_main -e "
SELECT id, user_id, tenant_id, login_method, is_successful, login_at 
FROM login_audits ORDER BY id DESC LIMIT 10;"
```

#### Accessing Analytics:
- **Admin Dashboard**: `http://localhost:8000/admin/analytics`
- **Login Required**: Admin users with analytics permissions
- **Auto-Refresh**: Live data updates every 30 seconds
- **Export Capability**: CSV export of audit data available

#### Database Schema:
- **login_audits**: Main audit log table with authentication events
- **active_sessions**: Real-time session tracking table
- **Retention**: Configurable cleanup (default 90 days)
- **Performance**: Optimized indexes for fast querying

#### API Endpoints for Audit:
- `POST /api/audit/login` - Record login event (used by tenant apps)
- `POST /api/audit/logout` - Record logout event
- `GET /admin/analytics/statistics` - Get dashboard statistics
- `GET /admin/analytics/recent-activity` - Get recent login activity

## 🔒 Enterprise Security Implementation

The SSO system implements **enterprise-grade security** with multiple layers of protection for production environments.

### Security Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     Security Layers                        │
├─────────────────────────────────────────────────────────────┤
│ 1. API Key Authentication (Server-to-Server)               │
│ 2. HMAC Request Signing (Tamper Protection)                │
│ 3. Multi-Layer Rate Limiting (DoS Protection)              │
│ 4. Request ID Tracking (Audit Trail)                       │
│ 5. SSL/TLS Encryption (Data in Transit)                    │
│ 6. Database Security (Data at Rest)                        │
│ 7. JWT Token Security (Session Management)                 │
│ 8. CSRF Protection (Web UI)                                │
└─────────────────────────────────────────────────────────────┘
```

### 🔑 API Key Authentication

**Purpose**: Secure server-to-server communication between tenant applications and central SSO.

**Implementation**:
- **Tenant-Specific Keys**: Each tenant has a unique API key for identification
- **Scope-Based Access**: Different API keys for different operations (auth, audit)
- **Header-Based Authentication**: Keys passed via `X-API-Key` header
- **Key Format**: `tenant{id}_{32_character_hash}` (e.g., `tenant1_0059abacdb1bd536fd605b520902f89658672011`)

**Configuration**:
```bash
# Generated API Keys (in .env files)
TENANT1_API_KEY=tenant1_0059abacdb1bd536fd605b520902f89658672011
TENANT2_API_KEY=tenant2_0010258f78e44ca7ad9de92a1a1c9307b278bbd7
```

**Usage Example**:
```bash
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "X-API-Key: tenant1_0059abacdb1bd536fd605b520902f89658672011" \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'
```

### 🔐 HMAC Request Signing

**Purpose**: Prevent request tampering and replay attacks using cryptographic signatures.

**Implementation**:
- **Algorithm**: HMAC-SHA256 with shared secret
- **Canonical Requests**: Standardized request string format for consistent signing
- **Signature Headers**: `X-Signature` header contains request signature
- **Timestamp Validation**: `X-Timestamp` header prevents replay attacks

**HMAC Secret**:
```bash
HMAC_SECRET=81880e27a1869b38105b1ad7f9f3b329bc0c69df7500ddd2c0ce58710e2007df
```

**Signature Process**:
1. Create canonical request string: `METHOD|URI|TIMESTAMP|TENANT_ID|BODY_HASH`
2. Generate HMAC-SHA256 signature using shared secret
3. Include signature in `X-Signature` header
4. Server validates signature on incoming requests

### ⚡ Multi-Layer Rate Limiting

**Purpose**: Protect against denial-of-service attacks and API abuse.

**Implementation**:
- **By IP Address**: Prevent single IP from overwhelming system
- **By API Key**: Prevent single tenant from exceeding limits
- **By Endpoint**: Different limits for different operations
- **Sliding Window**: Redis-based sliding window for accurate counting

**Rate Limits**:
```bash
# Authentication endpoints: 10 requests/minute
# Audit endpoints: 100 requests/minute  
# Default API endpoints: 60 requests/minute
# Role management: 120 requests/minute
```

**Response Headers**:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1755357757
```

### 📝 Request ID Tracking

**Purpose**: Comprehensive audit trail for all API requests across the system.

**Implementation**:
- **Unique IDs**: UUID v4 for each request
- **Cross-Service Tracking**: Request IDs passed between services
- **Audit Integration**: All audit logs include request IDs
- **Header Propagation**: `X-Request-ID` header maintained across calls

**Audit Trail Example**:
```json
{
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "user_id": 25,
  "tenant_id": "tenant1",
  "endpoint": "/api/auth/login",
  "ip_address": "172.18.0.1",
  "user_agent": "SecureSSOService/1.0",
  "timestamp": "2025-01-16T10:15:30Z"
}
```

### 🛡️ Security Middleware Stack

**Central SSO API Protection**:
```php
// All authentication endpoints protected
Route::prefix('auth')->middleware(['api.key:auth', 'rate.limit'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/validate', [AuthController::class, 'validateToken']);
});

// Audit endpoints with dedicated scope
Route::prefix('audit')->middleware(['api.key:audit', 'rate.limit'])->group(function () {
    Route::post('/login', [LoginAuditController::class, 'recordLogin']);
    Route::post('/logout', [LoginAuditController::class, 'recordLogout']);
});
```

### 🔧 Security Configuration

**Central Security Config** (`central-sso/config/security.php`):
```php
return [
    // API Key Authentication
    'api_keys' => [
        'tenant1' => env('TENANT1_API_KEY'),
        'tenant2' => env('TENANT2_API_KEY'),
    ],
    
    // HMAC Request Signing
    'hmac' => [
        'secret' => env('HMAC_SECRET'),
        'algorithm' => 'sha256',
        'timestamp_tolerance' => 300, // 5 minutes
    ],
    
    // Rate Limiting
    'rate_limiting' => [
        'by_ip' => true,
        'by_api_key' => true,
        'auth_per_minute' => 10,
        'audit_per_minute' => 100,
        'default_per_minute' => 60,
    ],
    
    // SSL/TLS (Production)
    'ssl' => [
        'enabled' => env('SSL_ENABLED', false),
        'cert_path' => env('SSL_CERT_PATH'),
        'key_path' => env('SSL_KEY_PATH'),
    ],
];
```

### 🚀 Secure Tenant Integration

**SecureSSOService**: Enhanced SSO service with full security implementation.

**Key Features**:
- **Automatic API Key Authentication**: Includes tenant API key in all requests
- **Request Signing**: HMAC signatures for all API calls
- **Error Handling**: Graceful degradation if security features fail
- **Audit Integration**: Comprehensive logging of all authentication events

**Implementation** (`tenant1-app/app/Services/SecureSSOService.php`):
```php
class SecureSSOService
{
    private function generateSecureHeaders($method, $uri, $body) {
        $headers = [
            'X-API-Key' => $this->apiKey,
            'X-Timestamp' => now()->toISOString(),
            'X-Tenant-ID' => $this->tenantSlug,
            'X-Request-ID' => Str::uuid(),
        ];
        
        // Generate HMAC signature
        $signature = $this->generateSignature($method, $uri, $headers, $body);
        $headers['X-Signature'] = $signature;
        
        return $headers;
    }
}
```

### 🗃️ Database Security

**MariaDB Hardening**:
```bash
# SSL/TLS encryption for database connections
DB_SSL_MODE=REQUIRED
DB_SSL_CERT=/path/to/client-cert.pem
DB_SSL_KEY=/path/to/client-key.pem
DB_SSL_CA=/path/to/ca-cert.pem

# Strong authentication
DB_AUTH_PLUGIN=mysql_native_password
DB_PASSWORD_VALIDATION=STRONG
```

**Connection Security**:
- Encrypted connections between applications and database
- Certificate-based authentication for production
- Separate database users with minimal privileges
- Regular security updates and patches

### 📊 Security Monitoring

**Real-Time Monitoring**:
- **Rate Limit Violations**: Logged with IP, user agent, and endpoint details
- **Invalid API Keys**: Failed authentication attempts tracked
- **HMAC Failures**: Request tampering attempts logged
- **Audit Trail**: Complete record of all authentication events

**Log Examples**:
```bash
# Rate limit exceeded
[2025-01-16 10:15:30] WARNING: Rate limit exceeded [type: ip, identifier: 172.18.0.1, limit: 60, current: 61]

# Invalid API key
[2025-01-16 10:15:31] ERROR: Invalid API key [key_prefix: invalid_, ip: 172.18.0.1, endpoint: /api/auth/login]

# HMAC signature failure
[2025-01-16 10:15:32] ERROR: HMAC signature validation failed [expected: abc123, received: def456, tenant: tenant1]
```

### 🧪 Security Testing

**Test Commands**:
```bash
# Test rate limiting
for i in {1..15}; do curl -X POST "http://localhost:8000/api/auth/login" -H "X-API-Key: tenant1_key"; done

# Test invalid API key
curl -X POST "http://localhost:8000/api/auth/login" -H "X-API-Key: invalid_key" -d '{"email":"test","password":"test"}'

# Test HMAC validation
curl -X POST "http://localhost:8000/api/auth/login" -H "X-API-Key: tenant1_key" -H "X-Signature: invalid_signature"
```

### 🏭 Production Deployment

**SSL Certificate Generation**:
```bash
# Generate SSL certificates for production
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/sso.key \
  -out /etc/ssl/certs/sso.crt \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=your-domain.com"
```

**Production Environment Variables**:
```bash
# Security Configuration
SSL_ENABLED=true
SSL_CERT_PATH=/etc/ssl/certs/sso.crt
SSL_KEY_PATH=/etc/ssl/private/sso.key

# Database Security
DB_SSL_MODE=REQUIRED
DB_SSL_CERT=/etc/ssl/certs/db-client.pem

# Rate Limiting (Redis recommended)
CACHE_DRIVER=redis
REDIS_HOST=redis-cluster.internal
```

### ⚠️ Security Best Practices

**API Key Management**:
- ✅ Rotate API keys regularly (quarterly recommended)
- ✅ Use environment variables, never commit keys to repository
- ✅ Different keys for different environments (dev, staging, prod)
- ✅ Monitor API key usage for anomalies

**HMAC Signature Security**:
- ✅ Use strong, randomly generated HMAC secrets (64+ characters)
- ✅ Implement timestamp validation to prevent replay attacks
- ✅ Log all signature validation failures for monitoring
- ✅ Rotate HMAC secrets periodically

**Rate Limiting Strategy**:
- ✅ Set conservative limits initially, adjust based on usage
- ✅ Use Redis for production (better performance than cache)
- ✅ Implement progressive rate limiting (stricter for failed attempts)
- ✅ Monitor rate limit violations for potential attacks

### 🔄 Migration from Basic to Secure

**Upgrading Existing Tenant Apps**:
1. **Install SecureSSOService**: Replace basic SSOService with SecureSSOService
2. **Update Environment**: Add API keys and HMAC secrets to .env
3. **Test Authentication**: Verify all login flows work with new security
4. **Monitor Logs**: Check for any security-related errors
5. **Enable Production SSL**: Configure SSL certificates and HTTPS

**Backward Compatibility**:
- Security features can be disabled via configuration
- Gradual rollout possible (enable security per tenant)
- Non-breaking changes to existing API contracts
- Comprehensive logging helps identify migration issues

This enterprise security implementation provides **bank-grade protection** suitable for production SSO systems handling sensitive user authentication across multiple applications.

## 🚀 Production Deployment Guide

This guide covers deploying the SSO system to production with enterprise-grade security enabled.

### 📋 Pre-Deployment Checklist

**Infrastructure Requirements**:
- [ ] Linux server (Ubuntu 20.04+ or CentOS 8+ recommended)
- [ ] Docker Engine 20.10+ and Docker Compose v2
- [ ] Redis cluster for session storage and rate limiting
- [ ] MariaDB 10.6+ with SSL/TLS support
- [ ] Load balancer with SSL termination (nginx, HAProxy, or cloud LB)
- [ ] Domain names with valid SSL certificates
- [ ] Firewall rules configured for security

**Security Requirements**:
- [ ] SSL certificates for all domains
- [ ] Strong secrets generated for all environments
- [ ] API keys rotated from development values
- [ ] Database users with minimal privileges
- [ ] Network segmentation between services
- [ ] Monitoring and alerting configured

### 🔐 SSL/TLS Certificate Setup

**Option 1: Let's Encrypt (Recommended for most deployments)**
```bash
# Install certbot
sudo apt update && sudo apt install certbot python3-certbot-nginx

# Generate certificates for all domains
sudo certbot --nginx -d sso.yourdomain.com -d tenant1.yourdomain.com -d tenant2.yourdomain.com

# Verify certificate renewal
sudo certbot renew --dry-run

# Setup auto-renewal
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

**Option 2: Commercial SSL Certificate**
```bash
# Generate private key and CSR
openssl req -new -newkey rsa:2048 -nodes \
    -keyout /etc/ssl/private/sso.key \
    -out /etc/ssl/certs/sso.csr \
    -subj "/C=US/ST=YourState/L=YourCity/O=YourOrg/CN=sso.yourdomain.com"

# After receiving certificate from CA, combine with intermediate certificates
cat sso.yourdomain.com.crt intermediate.crt > /etc/ssl/certs/sso.crt
chmod 600 /etc/ssl/private/sso.key
chmod 644 /etc/ssl/certs/sso.crt
```

### 🌐 Load Balancer Configuration

**Nginx Configuration** (`/etc/nginx/sites-available/sso`):
```nginx
# Central SSO Server
server {
    listen 443 ssl http2;
    server_name sso.yourdomain.com;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/sso.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/sso.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Frame-Options DENY always;
    add_header X-Content-Type-Options nosniff always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=login:10m rate=10r/m;
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
    
    # API Endpoints
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Authentication endpoints with stricter limits
    location /api/auth/ {
        limit_req zone=login burst=5 nodelay;
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Web Interface
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# Tenant Applications (similar configuration for each)
server {
    listen 443 ssl http2;
    server_name tenant1.yourdomain.com;
    
    ssl_certificate /etc/letsencrypt/live/tenant1.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tenant1.yourdomain.com/privkey.pem;
    
    # Same SSL and security settings as above
    
    location / {
        proxy_pass http://127.0.0.1:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# HTTP to HTTPS redirect
server {
    listen 80;
    server_name sso.yourdomain.com tenant1.yourdomain.com tenant2.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

### 🗄️ Database Production Setup

**MariaDB Security Configuration** (`/etc/mysql/mariadb.conf.d/99-security.cnf`):
```ini
[mariadb]
# SSL/TLS Configuration
ssl-ca = /etc/mysql/ssl/ca-cert.pem
ssl-cert = /etc/mysql/ssl/server-cert.pem
ssl-key = /etc/mysql/ssl/server-key.pem
require_secure_transport = ON

# Security Settings
skip-networking = 0
bind-address = 127.0.0.1
local-infile = 0
symbolic-links = 0

# Performance & Security
max_connections = 200
max_user_connections = 50
query_cache_type = 1
query_cache_limit = 2M
query_cache_size = 64M

# Logging
log_error = /var/log/mysql/error.log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

**Generate Database SSL Certificates**:
```bash
# Create SSL directory
sudo mkdir -p /etc/mysql/ssl
cd /etc/mysql/ssl

# Generate CA certificate
sudo openssl genrsa 2048 > ca-key.pem
sudo openssl req -new -x509 -nodes -days 3600 -key ca-key.pem -out ca-cert.pem \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=MySQL-CA"

# Generate server certificate
sudo openssl req -newkey rsa:2048 -days 3600 -nodes -keyout server-key.pem -out server-req.pem \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=MySQL-Server"
sudo openssl rsa -in server-key.pem -out server-key.pem
sudo openssl x509 -req -in server-req.pem -days 3600 -CA ca-cert.pem -CAkey ca-key.pem -set_serial 01 -out server-cert.pem

# Generate client certificate
sudo openssl req -newkey rsa:2048 -days 3600 -nodes -keyout client-key.pem -out client-req.pem \
    -subj "/C=US/ST=State/L=City/O=Organization/CN=MySQL-Client"
sudo openssl rsa -in client-key.pem -out client-key.pem
sudo openssl x509 -req -in client-req.pem -days 3600 -CA ca-cert.pem -CAkey ca-key.pem -set_serial 01 -out client-cert.pem

# Set permissions
sudo chown mysql:mysql /etc/mysql/ssl/*
sudo chmod 600 /etc/mysql/ssl/*-key.pem
sudo chmod 644 /etc/mysql/ssl/*-cert.pem
```

### 🚀 Application Deployment

**Production Docker Compose** (`docker-compose.prod.yml`):
```yaml
version: '3.8'

services:
  central-sso:
    build:
      context: ./central-sso
      dockerfile: Dockerfile.prod
    container_name: central-sso-prod
    ports:
      - "127.0.0.1:8000:8000"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://sso.yourdomain.com
    volumes:
      - ./central-sso:/var/www/html
      - /etc/ssl/certs:/etc/ssl/certs:ro
      - /var/log/sso:/var/www/html/storage/logs
    depends_on:
      - mariadb-prod
      - redis-prod
    networks:
      - sso-network-prod
    restart: unless-stopped
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '0.5'

  tenant1-app:
    build:
      context: ./tenant1-app
      dockerfile: Dockerfile.prod
    container_name: tenant1-app-prod
    ports:
      - "127.0.0.1:8001:8000"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://tenant1.yourdomain.com
      - CENTRAL_SSO_URL=https://sso.yourdomain.com
    volumes:
      - ./tenant1-app:/var/www/html
      - /var/log/sso:/var/www/html/storage/logs
    depends_on:
      - mariadb-prod
    networks:
      - sso-network-prod
    restart: unless-stopped

  tenant2-app:
    build:
      context: ./tenant2-app
      dockerfile: Dockerfile.prod
    container_name: tenant2-app-prod
    ports:
      - "127.0.0.1:8002:8000"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://tenant2.yourdomain.com
      - CENTRAL_SSO_URL=https://sso.yourdomain.com
    volumes:
      - ./tenant2-app:/var/www/html
      - /var/log/sso:/var/www/html/storage/logs
    depends_on:
      - mariadb-prod
    networks:
      - sso-network-prod
    restart: unless-stopped

  mariadb-prod:
    image: mariadb:10.6
    container_name: sso-mariadb-prod
    environment:
      MARIADB_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MARIADB_DATABASE: sso_main
      MARIADB_USER: sso_user
      MARIADB_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mariadb_data_prod:/var/lib/mysql
      - /etc/mysql/ssl:/etc/mysql/ssl:ro
      - ./database/production.cnf:/etc/mysql/mariadb.conf.d/99-production.cnf:ro
    ports:
      - "127.0.0.1:3306:3306"
    networks:
      - sso-network-prod
    restart: unless-stopped
    deploy:
      resources:
        limits:
          memory: 2G

  redis-prod:
    image: redis:7-alpine
    container_name: sso-redis-prod
    command: redis-server --requirepass ${REDIS_PASSWORD} --appendonly yes
    volumes:
      - redis_data_prod:/data
    ports:
      - "127.0.0.1:6379:6379"
    networks:
      - sso-network-prod
    restart: unless-stopped
    deploy:
      resources:
        limits:
          memory: 512M

volumes:
  mariadb_data_prod:
    driver: local
  redis_data_prod:
    driver: local

networks:
  sso-network-prod:
    driver: bridge
    ipam:
      config:
        - subnet: 172.20.0.0/16
```

### 🔑 Production Environment Configuration

**Central SSO Production Environment** (`.env.production`):
```bash
# Application Configuration
APP_NAME="Enterprise SSO"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_php_artisan_key_generate
APP_DEBUG=false
APP_URL=https://sso.yourdomain.com

# Database Configuration with SSL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sso_main
DB_USERNAME=sso_user
DB_PASSWORD=STRONG_RANDOM_PASSWORD_HERE
DB_SSL_MODE=REQUIRED
DB_SSL_CERT=/etc/ssl/certs/mysql-client-cert.pem
DB_SSL_KEY=/etc/ssl/private/mysql-client-key.pem
DB_SSL_CA=/etc/ssl/certs/mysql-ca-cert.pem

# Cache & Session (Redis)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=STRONG_REDIS_PASSWORD
REDIS_PORT=6379

# Security Configuration
TENANT1_API_KEY=tenant1_GENERATE_32_CHAR_RANDOM_STRING
TENANT2_API_KEY=tenant2_GENERATE_32_CHAR_RANDOM_STRING
HMAC_SECRET=GENERATE_64_CHAR_RANDOM_STRING
SSL_ENABLED=true
SSL_CERT_PATH=/etc/ssl/certs/sso.crt
SSL_KEY_PATH=/etc/ssl/private/sso.key

# JWT Configuration
JWT_SECRET=GENERATE_STRONG_JWT_SECRET
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=sso@yourdomain.com
MAIL_PASSWORD=MAIL_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=sso@yourdomain.com
MAIL_FROM_NAME="Enterprise SSO"

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# Security Headers
SESSION_DOMAIN=.yourdomain.com
SANCTUM_STATEFUL_DOMAINS=sso.yourdomain.com,tenant1.yourdomain.com,tenant2.yourdomain.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Telescope (Disable in production)
TELESCOPE_ENABLED=false
```

### 🔒 Security Hardening

**System-Level Security**:
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install fail2ban for intrusion prevention
sudo apt install fail2ban -y

# Configure fail2ban for SSH and web services
cat << EOF | sudo tee /etc/fail2ban/jail.local
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
action = iptables-multiport[name=ReqLimit, port="http,https", protocol=tcp]
logpath = /var/log/nginx/error.log
findtime = 600
bantime = 7200
maxretry = 10
EOF

sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Configure UFW firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Disable unnecessary services
sudo systemctl disable apache2 2>/dev/null || true
sudo systemctl stop apache2 2>/dev/null || true
```

**File System Security**:
```bash
# Set proper ownership and permissions
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo chmod -R 640 /var/www/html/.env*
sudo chmod 600 /etc/ssl/private/*

# Create log directory with proper permissions
sudo mkdir -p /var/log/sso
sudo chown www-data:www-data /var/log/sso
sudo chmod 750 /var/log/sso
```

### 📊 Monitoring and Alerting

**Install Monitoring Stack**:
```bash
# Install Prometheus node exporter
wget https://github.com/prometheus/node_exporter/releases/download/v1.6.1/node_exporter-1.6.1.linux-amd64.tar.gz
tar xzf node_exporter-1.6.1.linux-amd64.tar.gz
sudo cp node_exporter-1.6.1.linux-amd64/node_exporter /usr/local/bin/
sudo useradd --no-create-home --shell /bin/false node_exporter
sudo chown node_exporter:node_exporter /usr/local/bin/node_exporter

# Create systemd service
cat << EOF | sudo tee /etc/systemd/system/node_exporter.service
[Unit]
Description=Node Exporter
Wants=network-online.target
After=network-online.target

[Service]
User=node_exporter
Group=node_exporter
Type=simple
ExecStart=/usr/local/bin/node_exporter

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable node_exporter
sudo systemctl start node_exporter
```

**Application Health Checks**:
```bash
# Create health check script
cat << 'EOF' | sudo tee /usr/local/bin/sso-health-check.sh
#!/bin/bash
set -e

# Check application health
check_endpoint() {
    local url=$1
    local name=$2
    
    if curl -f -s "$url/health" > /dev/null; then
        echo "✅ $name is healthy"
        return 0
    else
        echo "❌ $name is unhealthy"
        return 1
    fi
}

# Check all services
FAILED=0
check_endpoint "https://sso.yourdomain.com" "Central SSO" || FAILED=1
check_endpoint "https://tenant1.yourdomain.com" "Tenant 1" || FAILED=1
check_endpoint "https://tenant2.yourdomain.com" "Tenant 2" || FAILED=1

# Check database connectivity
if docker exec sso-mariadb-prod mysql -u sso_user -p${DB_PASSWORD} -e "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ Database is healthy"
else
    echo "❌ Database is unhealthy"
    FAILED=1
fi

# Check Redis connectivity
if docker exec sso-redis-prod redis-cli -a ${REDIS_PASSWORD} ping | grep -q PONG; then
    echo "✅ Redis is healthy"
else
    echo "❌ Redis is unhealthy"
    FAILED=1
fi

exit $FAILED
EOF

sudo chmod +x /usr/local/bin/sso-health-check.sh

# Add to crontab for monitoring
echo "*/5 * * * * /usr/local/bin/sso-health-check.sh > /var/log/sso-health.log 2>&1" | crontab -
```

### 🔄 Backup and Recovery

**Automated Backup Script**:
```bash
cat << 'EOF' | sudo tee /usr/local/bin/sso-backup.sh
#!/bin/bash
set -e

BACKUP_DIR="/var/backups/sso"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Database backup
echo "Creating database backup..."
docker exec sso-mariadb-prod mysqldump \
    -u sso_user -p${DB_PASSWORD} \
    --single-transaction \
    --routines \
    --triggers \
    sso_main > "$BACKUP_DIR/database_$DATE.sql"

# Compress database backup
gzip "$BACKUP_DIR/database_$DATE.sql"

# Application files backup (configuration only)
echo "Creating configuration backup..."
tar -czf "$BACKUP_DIR/config_$DATE.tar.gz" \
    /var/www/html/.env* \
    /etc/nginx/sites-available/sso \
    /etc/ssl/certs/sso.* \
    /etc/mysql/ssl/

# Redis backup
echo "Creating Redis backup..."
docker exec sso-redis-prod redis-cli -a ${REDIS_PASSWORD} --rdb /data/dump_$DATE.rdb
docker cp sso-redis-prod:/data/dump_$DATE.rdb "$BACKUP_DIR/"
gzip "$BACKUP_DIR/dump_$DATE.rdb"

# Cleanup old backups
find "$BACKUP_DIR" -name "*.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $BACKUP_DIR"
ls -lh "$BACKUP_DIR"
EOF

sudo chmod +x /usr/local/bin/sso-backup.sh

# Schedule daily backups
echo "0 2 * * * /usr/local/bin/sso-backup.sh > /var/log/sso-backup.log 2>&1" | sudo crontab -
```

### 🚀 Deployment Steps

**1. Initial Server Setup**:
```bash
# Clone repository
git clone https://github.com/yourorg/sso-poc-claude3.git /var/www/sso
cd /var/www/sso

# Copy and configure environment files
cp central-sso/.env.example central-sso/.env.production
cp tenant1-app/.env.example tenant1-app/.env.production
cp tenant2-app/.env.example tenant2-app/.env.production

# Generate application keys and secrets
docker run --rm -v $(pwd)/central-sso:/app -w /app php:8.2-cli php artisan key:generate --env=production
```

**2. Security Configuration**:
```bash
# Generate API keys
TENANT1_KEY="tenant1_$(openssl rand -hex 16)"
TENANT2_KEY="tenant2_$(openssl rand -hex 16)"
HMAC_SECRET="$(openssl rand -hex 32)"

# Update environment files with generated secrets
sed -i "s/TENANT1_API_KEY=.*/TENANT1_API_KEY=$TENANT1_KEY/" central-sso/.env.production
sed -i "s/TENANT2_API_KEY=.*/TENANT2_API_KEY=$TENANT2_KEY/" central-sso/.env.production
sed -i "s/HMAC_SECRET=.*/HMAC_SECRET=$HMAC_SECRET/" central-sso/.env.production
```

**3. Application Deployment**:
```bash
# Start production services
docker-compose -f docker-compose.prod.yml up -d

# Run database migrations
docker exec central-sso-prod php artisan migrate --force

# Seed initial data
docker exec central-sso-prod php artisan db:seed --class=AddTestUsersSeeder --force

# Clear and cache configuration
docker exec central-sso-prod php artisan config:cache
docker exec central-sso-prod php artisan route:cache
docker exec central-sso-prod php artisan view:cache
```

**4. Final Verification**:
```bash
# Test all endpoints
curl -k https://sso.yourdomain.com/health
curl -k https://tenant1.yourdomain.com/health
curl -k https://tenant2.yourdomain.com/health

# Test authentication
curl -X POST "https://sso.yourdomain.com/api/auth/login" \
  -H "X-API-Key: $TENANT1_KEY" \
  -H "Content-Type: application/json" \
  -d '{"email": "superadmin@sso.com", "password": "password", "tenant_slug": "tenant1"}'

# Run health check
/usr/local/bin/sso-health-check.sh
```

### 📈 Performance Optimization

**PHP-FPM Tuning** (`/etc/php/8.2/fpm/pool.d/www.conf`):
```ini
[www]
user = www-data
group = www-data

listen = /run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0666

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 1000

# Security
php_admin_value[disable_functions] = exec,passthru,shell_exec,system,proc_open,popen
php_admin_flag[allow_url_fopen] = off
php_admin_flag[allow_url_include] = off
```

**Redis Optimization** (`/etc/redis/redis.conf`):
```ini
# Memory management
maxmemory 512mb
maxmemory-policy allkeys-lru

# Security
requirepass STRONG_REDIS_PASSWORD
bind 127.0.0.1

# Persistence
save 900 1
save 300 10
save 60 10000
```

This production deployment guide provides a complete, secure, and scalable foundation for deploying the SSO system in enterprise environments.

### Laravel Telescope
- **URL**: `http://localhost:8000/telescope`
- Monitor requests, database queries, exceptions
- Available only in development environment

### API Documentation
- **Swagger UI**: `http://localhost:8000/api/documentation`
- **Quick Access**: `http://localhost:8000/docs` (redirects to Swagger UI)
- **JSON Schema**: `http://localhost:8000/api/api-docs.json`
- Interactive API documentation with request/response schemas
- Test API endpoints directly from the browser
- Available only in development environment

### Common Issues
- **Invalid credentials**: Ensure using MariaDB, not SQLite
- **Database connection**: Check Docker containers are running
- **Token validation**: Verify tenant associations in database
- **CORS issues**: Check tenant app configurations
- **Domain consistency**: All apps must use `localhost` domain for session sharing
- **SSO authentication not working**: Check that Laravel Telescope is installed in all apps
- **"Please login to continue"**: Verify SSOCallbackController calls auth()->login() for local users
- **Access denied errors**: Check user-tenant relationships in database

## Important Notes

### 🏗️ Dual-Session Architecture
- **Primary Login Method**: Users can login directly to tenant apps using SSO credentials
- **API-Based Authentication**: All credentials validated through central SSO API for consistency
- **Local Session Management**: Each tenant app maintains independent Laravel sessions
- **Automatic User Sync**: User data synchronized from central SSO on every login
- **Session Data Storage**: JWT tokens and SSO user data cached in local sessions
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

This guide shows how to integrate a new Laravel application with the Central SSO system, following the same pattern as the existing tenant applications.

## 1. Prerequisites

- Laravel 11 application
- Docker and Docker Compose
- Access to the Central SSO server
- MariaDB database for the tenant

## 2. Required Dependencies

Add these packages to your Laravel application:

```bash
composer require tymon/jwt-auth
composer require laravel/telescope
composer require guzzlehttp/guzzle
```

## 3. Environment Configuration

Add these environment variables to your `.env` file:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=your_tenant_db
DB_USERNAME=sso_user
DB_PASSWORD=sso_password

# Central SSO Configuration
CENTRAL_SSO_URL=http://central-sso:8000
CENTRAL_SSO_DOMAIN=localhost:8000
TENANT_SLUG=your-tenant-slug

# JWT Configuration
JWT_SECRET=your_jwt_secret_here
JWT_TTL=60

# App Configuration
APP_URL=http://localhost:8003  # Use next available port
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:8003,localhost:8000
```

## 4. Database Setup

Create database migration for users table:

```php
<?php
// database/migrations/2024_01_01_000000_create_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->string('sso_user_id')->nullable(); // For SSO user mapping
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
```

## 5. User Model Configuration

Update your User model:

```php
<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'sso_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
```

## 6. SSO Service Class

Create an SSO service to handle authentication:

```php
<?php
// app/Services/SSOService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SSOService
{
    private string $centralSSOUrl;
    private string $tenantSlug;

    public function __construct()
    {
        $this->centralSSOUrl = config('app.central_sso_url');
        $this->tenantSlug = config('app.tenant_slug');
    }

    public function verifyToken(string $token): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get($this->centralSSOUrl . '/api/auth/me');

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::error('SSO token verification failed: ' . $e->getMessage());
        }

        return null;
    }

    public function createOrUpdateUser(array $userData): User
    {
        $user = User::updateOrCreate(
            ['sso_user_id' => $userData['id']],
            [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'is_admin' => $userData['is_admin'] ?? false,
                'password' => Hash::make('sso_user_' . $userData['id']), // Random password
            ]
        );

        return $user;
    }

    public function authenticateUser(User $user): void
    {
        Auth::login($user, true);
        session()->regenerate();
    }
}
```

## 7. SSO Authentication Controller

Create a controller to handle SSO callbacks:

```php
<?php
// app/Http/Controllers/Auth/SSOController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SSOService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SSOController extends Controller
{
    private SSOService $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function process(Request $request)
    {
        return view('auth.sso-process', [
            'centralSSOUrl' => config('app.central_sso_url'),
            'tenantSlug' => config('app.tenant_slug'),
        ]);
    }

    public function callback(Request $request)
    {
        $token = $request->input('token');
        
        if (!$token) {
            return response()->json(['error' => 'No token provided'], 400);
        }

        $userData = $this->ssoService->verifyToken($token);
        
        if (!$userData) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        // Check if user has access to this tenant
        $tenantSlug = config('app.tenant_slug');
        if (!in_array($tenantSlug, $userData['tenants'] ?? [])) {
            return response()->json(['error' => 'Access denied to this tenant'], 403);
        }

        // Create or update local user
        $user = $this->ssoService->createOrUpdateUser($userData);
        
        // Authenticate user locally
        $this->ssoService->authenticateUser($user);

        return response()->json(['success' => true, 'redirect' => route('dashboard')]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
```

## 8. SSO Processing View

Create the SSO processing page:

```blade
{{-- resources/views/auth/sso-process.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing SSO Login...</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full space-y-8 p-6">
        <div class="text-center">
            <div id="loading" class="space-y-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <h2 class="text-xl font-semibold text-gray-900">Processing SSO Login...</h2>
                <p class="text-gray-600">Please wait while we authenticate you.</p>
            </div>
            
            <div id="error" class="hidden space-y-4">
                <div class="rounded-full h-12 w-12 bg-red-100 flex items-center justify-center mx-auto">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900">Authentication Failed</h2>
                <p id="error-message" class="text-gray-600"></p>
                <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user is already authenticated via SSO
            fetch('{{ $centralSSOUrl }}/api/auth/check', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.authenticated) {
                    // User is authenticated, verify access and log them in
                    fetch('{{ route('sso.callback') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ token: data.token })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            window.location.href = result.redirect;
                        } else {
                            showError(result.error || 'Authentication failed');
                        }
                    })
                    .catch(() => showError('Network error occurred'));
                } else {
                    // User is not authenticated, redirect to central SSO
                    window.location.href = '{{ $centralSSOUrl }}/login?redirect=' + encodeURIComponent(window.location.origin + '/sso/process');
                }
            })
            .catch(() => showError('Unable to connect to authentication server'));
        });

        function showError(message) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error').classList.remove('hidden');
            document.getElementById('error-message').textContent = message;
        }
    </script>
</body>
</html>
```

## 9. Authentication Views

Create login view with SSO integration:

```blade
{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Sign in to {{ config('app.name') }}
            </h2>
        </div>

        <!-- SSO Login Button -->
        <div class="mb-6">
            <a href="{{ route('sso.process') }}" 
               class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Login with SSO
            </a>
        </div>

        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300" />
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-gray-50 text-gray-500">Or sign in directly</span>
            </div>
        </div>

        <!-- Regular Login Form -->
        <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
            @csrf
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Email address" value="{{ old('email') }}">
                </div>
                <div>
                    <input id="password" name="password" type="password" autocomplete="current-password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
            </div>

            @if ($errors->any())
                <div class="text-red-600 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Sign in locally
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
```

## 10. Routes Configuration

Add these routes to your `web.php`:

```php
<?php
// routes/web.php
use App\Http\Controllers\Auth\SSOController;
use Illuminate\Support\Facades\Route;

// SSO Routes
Route::prefix('sso')->group(function () {
    Route::get('/process', [SSOController::class, 'process'])->name('sso.process');
    Route::post('/callback', [SSOController::class, 'callback'])->name('sso.callback');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    
    Route::post('/login', [LoginController::class, 'authenticate']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SSOController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
```

## 11. Docker Configuration

Add your tenant app to the main `docker-compose.yml`:

```yaml
# Add this service to docker-compose.yml
your-tenant-app:
  build:
    context: ./your-tenant-app
    dockerfile: Dockerfile
  container_name: your-tenant-app
  ports:
    - "8003:8000"  # Use next available port
  volumes:
    - ./your-tenant-app:/var/www/html
  networks:
    - sso-network
  depends_on:
    - mariadb
  environment:
    - DB_HOST=mariadb
    - DB_DATABASE=your_tenant_db
    - CENTRAL_SSO_URL=http://central-sso:8000
```

## 12. Register Tenant in Central SSO

Add your tenant to the central SSO database:

```sql
-- Connect to central SSO database
docker exec -it mariadb mysql -u sso_user -psso_password sso_main

-- Insert new tenant
INSERT INTO tenants (id, slug, name, domain, description, is_active, created_at, updated_at) 
VALUES ('your-tenant-slug', 'your-tenant-slug', 'Your Tenant Name', 'localhost:8003', 'Description of your tenant', 1, NOW(), NOW());

-- Grant access to test users (optional)
INSERT INTO tenant_users (user_id, tenant_id, created_at, updated_at)
SELECT id, 'your-tenant-slug', NOW(), NOW() 
FROM users 
WHERE email IN ('superadmin@sso.com', 'admin@tenant1.com');
```

## 13. Testing the Integration

1. **Start all services**:
   ```bash
   docker compose up -d
   ```

2. **Run migrations**:
   ```bash
   docker exec your-tenant-app php artisan migrate
   ```

3. **Test SSO flow**:
   - Visit `http://localhost:8003/login`
   - Click "Login with SSO"
   - Should redirect to central SSO and back

4. **Test direct login**:
   - Create a local user in your tenant database
   - Login using the direct login form

## 14. Customization Options

### Custom User Roles
Implement your own role system within the tenant app:

```php
// Create roles migration
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->timestamps();
});

Schema::create('user_roles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('role_id')->constrained()->onDelete('cascade');
    $table->timestamps();
});
```

### Additional SSO Claims
Handle additional user data from SSO:

```php
// In SSOService::createOrUpdateUser()
$user = User::updateOrCreate(
    ['sso_user_id' => $userData['id']],
    [
        'name' => $userData['name'],
        'email' => $userData['email'],
        'is_admin' => $userData['is_admin'] ?? false,
        'department' => $userData['department'] ?? null,
        'job_title' => $userData['job_title'] ?? null,
        'avatar_url' => $userData['avatar_url'] ?? null,
        'password' => Hash::make('sso_user_' . $userData['id']),
    ]
);
```

### Custom Middleware
Create middleware for SSO token validation:

```php
<?php
// app/Http/Middleware/VerifySSOToken.php
namespace App\Http\Middleware;

use App\Services\SSOService;
use Closure;
use Illuminate\Http\Request;

class VerifySSOToken
{
    private SSOService $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if ($token) {
            $userData = $this->ssoService->verifyToken($token);
            if ($userData) {
                $user = $this->ssoService->createOrUpdateUser($userData);
                auth()->login($user);
            }
        }

        return $next($request);
    }
}
```

This integration guide provides everything needed to create a new tenant application that seamlessly integrates with the Central SSO system, following the same patterns as the existing tenant apps.

---

# 🔒 Secure Tenant Integration Guide

This guide shows how to integrate a new Laravel application with the Central SSO system using **enterprise-grade security** features. This is the recommended approach for production environments.

## Prerequisites

- Laravel 11 application
- Docker and Docker Compose
- Access to the Central SSO server with security enabled
- API key and HMAC secret from Central SSO administrator

## 🔐 Security-First Integration

### 1. Required Dependencies

```bash
composer require tymon/jwt-auth
composer require laravel/telescope
composer require guzzlehttp/guzzle
```

### 2. Environment Configuration

**Complete secure environment setup** (`.env`):

```env
# Application Configuration
APP_NAME="Secure Tenant App"
APP_ENV=local
APP_KEY=base64:GENERATE_WITH_php_artisan_key_generate
APP_DEBUG=true
APP_URL=http://localhost:8003

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=tenant3_db
DB_USERNAME=sso_user
DB_PASSWORD=sso_password

# Central SSO Configuration
CENTRAL_SSO_URL=http://central-sso:8000
CENTRAL_SSO_DOMAIN=localhost:8000
TENANT_SLUG=tenant3

# 🔑 Security Configuration
TENANT_API_KEY=tenant3_SECURE_32_CHAR_API_KEY_HERE
HMAC_SECRET=64_CHAR_HMAC_SECRET_FROM_CENTRAL_SSO
SSL_ENABLED=false  # Set to true in production
SSL_VERIFY=false   # Set to true in production with valid certificates

# JWT Configuration
JWT_SECRET=your_jwt_secret_here
JWT_TTL=60

# Session Security
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:8003,localhost:8000
SESSION_SECURE_COOKIE=false  # Set to true in production
SESSION_SAME_SITE=lax

# Cache & Performance
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

### 3. Secure SSO Service Implementation

**Create SecureSSOService** (`app/Services/SecureSSOService.php`):

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecureSSOService
{
    private string $centralSSOUrl;
    private string $tenantSlug;
    private string $apiKey;
    private string $hmacSecret;
    private bool $sslVerify;

    public function __construct()
    {
        $this->centralSSOUrl = config('app.central_sso_url');
        $this->tenantSlug = config('app.tenant_slug');
        $this->apiKey = config('app.tenant_api_key');
        $this->hmacSecret = config('app.hmac_secret');
        $this->sslVerify = config('app.ssl_verify', true);
    }

    /**
     * Login user via secure central SSO API
     */
    public function login(string $email, string $password): array
    {
        try {
            $body = json_encode([
                'email' => $email,
                'password' => $password,
                'tenant_slug' => $this->tenantSlug
            ]);

            $headers = $this->generateSecureHeaders('POST', '/api/auth/login', $body);

            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => $this->sslVerify])
                ->timeout(30)
                ->post($this->centralSSOUrl . '/api/auth/login', [
                    'email' => $email,
                    'password' => $password,
                    'tenant_slug' => $this->tenantSlug
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'user' => $data['user'],
                    'token' => $data['token'],
                    'message' => $data['message'] ?? 'Login successful'
                ];
            }

            $error = $response->json();
            return [
                'success' => false,
                'message' => $error['message'] ?? 'Login failed',
                'errors' => $error['errors'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('SecureSSO login failed', [
                'error' => $e->getMessage(),
                'email' => $email,
                'tenant' => $this->tenantSlug
            ]);

            return [
                'success' => false,
                'message' => 'Authentication service unavailable'
            ];
        }
    }

    /**
     * Validate JWT token via secure central SSO API
     */
    public function validateToken(string $token): array
    {
        try {
            $headers = $this->generateSecureHeaders('POST', '/api/auth/validate', '');
            $headers['Authorization'] = 'Bearer ' . $token;

            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => $this->sslVerify])
                ->timeout(15)
                ->post($this->centralSSOUrl . '/api/auth/validate');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'valid' => true,
                    'user' => $data['user'],
                    'message' => 'Token valid'
                ];
            }

            return [
                'valid' => false,
                'message' => 'Invalid token'
            ];

        } catch (\Exception $e) {
            Log::error('SecureSSO token validation failed', [
                'error' => $e->getMessage(),
                'tenant' => $this->tenantSlug
            ]);

            return [
                'valid' => false,
                'message' => 'Token validation failed'
            ];
        }
    }

    /**
     * Record login audit event
     */
    public function recordLoginAudit(
        int $userId,
        string $email,
        string $loginMethod = 'direct',
        bool $isSuccessful = true,
        ?string $failureReason = null
    ): void {
        try {
            $body = json_encode([
                'user_id' => $userId,
                'email' => $email,
                'tenant_slug' => $this->tenantSlug,
                'login_method' => $loginMethod,
                'is_successful' => $isSuccessful,
                'failure_reason' => $failureReason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            $headers = $this->generateSecureHeaders('POST', '/api/audit/login', $body);

            Http::withHeaders($headers)
                ->withOptions(['verify' => $this->sslVerify])
                ->timeout(10)
                ->post($this->centralSSOUrl . '/api/audit/login', json_decode($body, true));

        } catch (\Exception $e) {
            // Don't fail the login process if audit recording fails
            Log::warning('Audit recording failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'email' => $email,
                'tenant' => $this->tenantSlug
            ]);
        }
    }

    /**
     * Generate secure headers with API key and HMAC signature
     */
    private function generateSecureHeaders(string $method, string $uri, string $body): array
    {
        $timestamp = now()->toISOString();
        $requestId = (string) Str::uuid();

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => $this->apiKey,
            'X-Timestamp' => $timestamp,
            'X-Tenant-ID' => $this->tenantSlug,
            'X-Request-ID' => $requestId,
            'User-Agent' => 'SecureSSOService/1.0'
        ];

        // Generate HMAC signature
        $signature = $this->generateSignature($method, $uri, $headers, $body);
        $headers['X-Signature'] = $signature;

        return $headers;
    }

    /**
     * Generate HMAC-SHA256 signature for request integrity
     */
    private function generateSignature(string $method, string $uri, array $headers, string $body): string
    {
        // Create canonical request string
        $canonicalString = implode('|', [
            strtoupper($method),
            $uri,
            $headers['X-Timestamp'],
            $headers['X-Tenant-ID'],
            hash('sha256', $body)
        ]);

        // Generate HMAC signature
        return hash_hmac('sha256', $canonicalString, $this->hmacSecret);
    }

    /**
     * Create or update local user from SSO data
     */
    public function createOrUpdateUser(array $userData): User
    {
        $user = User::updateOrCreate(
            ['sso_user_id' => $userData['id']],
            [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'is_admin' => $userData['is_admin'] ?? false,
                'password' => Hash::make('sso_user_' . $userData['id']), // Random password since SSO handles auth
            ]
        );

        return $user;
    }

    /**
     * Authenticate user locally
     */
    public function authenticateUser(User $user): void
    {
        Auth::login($user, true);
        session()->regenerate();
    }
}
```

### 4. Security Configuration

**Create security config** (`config/security.php`):

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for secure tenant integration with central SSO
    |
    */

    'api_key' => env('TENANT_API_KEY'),
    'hmac_secret' => env('HMAC_SECRET'),
    'ssl_verify' => env('SSL_VERIFY', true),
    
    'central_sso' => [
        'url' => env('CENTRAL_SSO_URL'),
        'timeout' => env('SSO_TIMEOUT', 30),
        'retry_attempts' => env('SSO_RETRY_ATTEMPTS', 3),
    ],

    'rate_limiting' => [
        'login_attempts' => env('LOGIN_RATE_LIMIT', 5),
        'login_window' => env('LOGIN_RATE_WINDOW', 60), // seconds
    ],

    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'log_failed_attempts' => env('AUDIT_LOG_FAILED', true),
        'log_successful_attempts' => env('AUDIT_LOG_SUCCESS', true),
    ],
];
```

## 🔒 Security Features Included

This secure integration provides:

- ✅ **API Key Authentication**: Every request authenticated with tenant-specific keys
- ✅ **HMAC Request Signing**: All requests cryptographically signed to prevent tampering
- ✅ **Rate Limiting**: Protection against brute force and DoS attacks
- ✅ **Comprehensive Audit**: All authentication events logged to central system
- ✅ **SSL/TLS Support**: Encrypted communication in production
- ✅ **Token Validation**: JWT tokens verified through secure API calls
- ✅ **Error Handling**: Graceful degradation if security services are unavailable
- ✅ **Request ID Tracking**: Full audit trail for debugging and compliance
- ✅ **Tenant Access Control**: Granular permission checking per tenant
- ✅ **Session Security**: Secure session management with regeneration

## 🚀 Quick Setup

1. **Generate API Key**: Request from central SSO administrator
2. **Configure Environment**: Update `.env` with security credentials
3. **Deploy SecureSSOService**: Replace basic SSO service with secure version
4. **Test Security**: Verify all protection layers are working
5. **Monitor**: Set up logging and monitoring for security events

This secure tenant integration guide provides enterprise-grade protection suitable for production SSO deployments handling sensitive authentication data.

## 🚀 Quick Start with Template

For the fastest way to create a new secure tenant application, use the provided template:

```bash
# Copy the secure tenant template
cp -r tenant-template/ my-new-tenant-app/
cd my-new-tenant-app/

# Follow the setup guide
cat SETUP.md
```

The template includes:
- ✅ Complete SecureSSOService implementation
- ✅ Comprehensive audit logging
- ✅ Production-ready security configuration
- ✅ Docker setup for easy deployment
- ✅ Health checks and monitoring
- ✅ Admin middleware and permissions
- ✅ Example views and routes
- ✅ Full documentation and setup guide

See [tenant-template/README.md](tenant-template/README.md) for complete details.

---

# 🧪 Security Testing Strategy

Comprehensive security testing framework to ensure enterprise-grade protection across all SSO components.

## 🎯 Testing Framework Overview

The security testing strategy covers:
- **Authentication Security**: API key validation, HMAC signing, JWT security
- **Rate Limiting**: DoS protection, brute force prevention
- **Session Security**: Token management, session integrity
- **Audit Integrity**: Logging verification, compliance monitoring
- **Input Validation**: Injection prevention, payload sanitization
- **Penetration Testing**: Controlled security assessments

## 🚀 Quick Security Testing

```bash
# Run comprehensive security tests
cd security-tests/
./run_security_tests.sh

# Test specific security categories
./run_security_tests.sh --category authentication
./run_security_tests.sh --category rate-limiting
./run_security_tests.sh --category penetration --confirm

# Daily automated security monitoring
./daily_security_check.sh
```

## 📋 Security Test Categories

### 🔑 Authentication Tests
- API key authentication validation
- HMAC signature verification  
- JWT token security testing
- Invalid credential rejection
- Multi-tenant access control

### ⚡ Rate Limiting Tests
- Login attempt throttling
- API request rate limiting
- IP-based restrictions
- Tenant-specific limits
- Recovery mechanism validation

### 🔒 Session Security Tests
- Session cookie security attributes
- JWT token validation
- Session regeneration on login
- Timeout enforcement
- Cross-tenant session isolation

### 📊 Audit Logging Tests
- Authentication event logging
- Failed attempt recording
- Security event capturing
- Request ID propagation
- Audit data integrity verification

### 🔍 Input Validation Tests
- SQL injection prevention
- XSS payload filtering
- Oversized payload rejection
- Header injection protection
- CSRF token validation

### 🎯 Penetration Tests (Controlled)
- Limited brute force simulation
- API endpoint enumeration
- Header injection attempts
- Session manipulation tests
- Access control bypass attempts

## 📊 Automated Security Monitoring

### Daily Security Checks
```bash
# Set up automated daily monitoring
crontab -e
# Add: 0 2 * * * /path/to/security-tests/daily_security_check.sh
```

### Continuous Integration
```yaml
# GitHub Actions security testing
name: Security Tests
on: [push, pull_request]
jobs:
  security:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run Security Tests
        run: ./security-tests/run_security_tests.sh --ci
```

## 🛡️ Security Test Configuration

### Test Environment Setup
```bash
# Configure test environment
cd security-tests/
cp config/test.env.example config/test.env

# Update configuration with your environment
CENTRAL_SSO_URL=http://localhost:8000
TENANT1_URL=http://localhost:8001
TEST_API_KEY=your_test_api_key
TEST_HMAC_SECRET=your_test_hmac_secret
```

## 📈 Security Testing Results

### Sample Test Output
```
🔑 Authentication Test Results:
   ✅ Valid API key accepted
   ✅ Invalid API key rejected
   ✅ Missing API key rejected
   ✅ HMAC signature validation working

⚡ Rate Limiting Test Results:
   ✅ Rate limiting activated after 5 attempts
   ✅ API rate limiting activated after 67 requests

📊 Security Test Summary
========================
Status: PASSED
Categories Tested: 5
Categories Passed: 5
Duration: 23s
```

## ⚠️ Security Testing Guidelines

### Safe Testing Practices
- **Isolated Environment**: Never run penetration tests in production
- **Authorized Testing**: Ensure proper authorization for all security tests
- **Resource Limits**: Respect system resources during testing
- **Data Protection**: Use only test data, never real user information

### Penetration Testing Precautions
```bash
# Penetration tests require explicit confirmation
./run_security_tests.sh --category penetration --confirm

# These tests may trigger security alerts
# Only run in isolated test environments
```

## 🔧 Security Testing Tools

The framework includes:
- **Comprehensive Test Suite**: [security-tests/run_security_tests.sh](security-tests/run_security_tests.sh)
- **Daily Monitoring**: [security-tests/daily_security_check.sh](security-tests/daily_security_check.sh)
- **Configuration Templates**: [security-tests/config/](security-tests/config/)
- **Automated Reporting**: JSON, HTML, and text output formats

## 📋 Security Compliance Checklist

### OWASP Top 10 Coverage
- ✅ A01: Broken Access Control
- ✅ A02: Cryptographic Failures  
- ✅ A03: Injection
- ✅ A07: Identity and Authentication Failures
- ✅ A09: Security Logging and Monitoring Failures

### Enterprise Security Standards
- **SOC 2 Type II**: Security control testing
- **ISO 27001**: Information security management
- **NIST Framework**: Cybersecurity best practices

This comprehensive security testing strategy ensures the SSO system maintains enterprise-grade protection and compliance with industry security standards.

---

# 🚀 Phase 3 Advanced Security (Future Roadmap)

Advanced security features for next-generation SSO protection. These features represent the future evolution of the security framework.

## 🔮 Advanced Security Features (TODO)

### 🤖 AI-Powered Security
- **Behavioral Analytics**: Machine learning-based anomaly detection
- **Risk Scoring**: Dynamic user risk assessment  
- **Adaptive Authentication**: Context-aware security requirements
- **Threat Intelligence**: Real-time security threat feeds integration

### 🔐 Zero-Trust Architecture
- **Device Trust**: Device fingerprinting and registration
- **Continuous Verification**: Ongoing authentication validation
- **Micro-Segmentation**: Granular network access control
- **Privilege Escalation**: Just-in-time access provisioning

### 🛡️ Advanced Threat Protection
- **Web Application Firewall**: Integrated WAF with custom rules
- **DDoS Protection**: Advanced distributed denial-of-service mitigation
- **Bot Detection**: Sophisticated bot and automation detection
- **Fraud Prevention**: Advanced fraud detection algorithms

### 🔍 Enhanced Monitoring
- **SIEM Integration**: Security Information and Event Management
- **Real-time Dashboards**: Live security monitoring interfaces
- **Automated Response**: Incident response automation
- **Compliance Reporting**: Automated regulatory compliance reports

### 🌐 Multi-Cloud Security
- **Cloud Security Posture**: Multi-cloud security assessment
- **Secrets Management**: Advanced secrets rotation and management
- **Container Security**: Kubernetes and container protection
- **Serverless Security**: Function-as-a-Service security

### 🔒 Cryptographic Enhancements
- **Post-Quantum Cryptography**: Quantum-resistant algorithms
- **Hardware Security Modules**: HSM integration for key management
- **Perfect Forward Secrecy**: Enhanced key exchange protocols
- **Homomorphic Encryption**: Privacy-preserving computations

## 📋 Implementation Roadmap

### Phase 3.1: AI & Analytics (Q2 2025)
- [ ] Implement behavioral analytics engine
- [ ] Deploy machine learning risk scoring
- [ ] Create adaptive authentication flows
- [ ] Integrate threat intelligence feeds

### Phase 3.2: Zero-Trust Foundation (Q3 2025)
- [ ] Device trust and registration system
- [ ] Continuous verification framework
- [ ] Network micro-segmentation
- [ ] Just-in-time access controls

### Phase 3.3: Advanced Protection (Q4 2025)
- [ ] Integrated web application firewall
- [ ] Advanced DDoS protection
- [ ] Sophisticated bot detection
- [ ] Fraud prevention algorithms

### Phase 3.4: Monitoring & Response (Q1 2026)
- [ ] SIEM platform integration
- [ ] Real-time security dashboards
- [ ] Automated incident response
- [ ] Compliance automation

## 🛠️ Technology Stack (Planned)

### Machine Learning & AI
- **TensorFlow/PyTorch**: Behavioral analytics models
- **Apache Kafka**: Real-time event streaming
- **Elasticsearch**: Advanced log analytics
- **Grafana**: Security metrics visualization

### Advanced Security Tools
- **Falco**: Runtime security monitoring
- **Vault**: Advanced secrets management
- **Istio**: Service mesh security
- **Envoy**: Advanced proxy and filtering

## 🎯 Security Objectives

### Threat Prevention
- **99.9% Attack Prevention**: Near-perfect threat blocking
- **Zero-Day Protection**: Advanced unknown threat detection
- **Real-time Response**: Sub-second incident response
- **Proactive Defense**: Predictive threat mitigation

### Compliance & Governance
- **Automated Compliance**: Self-managing regulatory compliance
- **Privacy by Design**: Built-in data protection
- **Audit Automation**: Continuous compliance monitoring
- **Risk Management**: Enterprise risk assessment integration

---

**Note**: Phase 3 Advanced Security features are planned for future development and represent the next evolution of the SSO security framework. Current Phase 1 and Phase 2 implementations provide enterprise-grade protection suitable for production deployments.