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

- **Central SSO Server** (`localhost:8000`): Laravel application handling authentication
- **Tenant Applications** (`localhost:8001`, `localhost:8002`): Laravel applications that authenticate via SSO
- **MariaDB Database**: Stores users, tenants, and relationships
- **Docker Network**: All services communicate via Docker network

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

### Single Tenant Users
- `user@tenant1.com` / `password` (Tenant 1 User)
- `admin@tenant1.com` / `password` (Tenant 1 Admin)
- `user@tenant2.com` / `password` (Tenant 2 User)
- `admin@tenant2.com` / `password` (Tenant 2 Admin)

### Multi-Tenant User
- `superadmin@sso.com` / `password` (Access to both tenants)

## Key Features

### Modern Landing Page
- **Professional UI**: Clean, modern landing page with gradient design and responsive layout
- **Feature Showcase**: Highlights all SSO capabilities with interactive elements
- **Live Statistics**: Real-time display of active tenants, users, roles, and permissions
- **Quick Start Guide**: Step-by-step instructions for getting started
- **Authentication State**: Dynamic navigation based on user login status

### Authentication Flows
1. **Central SSO Login**: Users login at `localhost:8000/login` and access tenant dashboard
2. **Seamless SSO Button**: Users click "Login with SSO" in tenant apps for automatic authentication
3. **Tenant Selection**: Multi-tenant users can select which tenant to access from central dashboard
4. **Direct Tenant Login**: Users can login directly within tenant applications
5. **API Authentication**: Direct API calls for programmatic access
6. **Multi-Tenant Support**: Users can have access to multiple tenants with proper access control

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

## Security Considerations

- JWT tokens signed with HMAC-SHA256
- Bcrypt password hashing with 12 rounds
- Tenant isolation enforced at token level
- No secrets committed to repository
- CORS and CSRF protection enabled
- Laravel Telescope for debugging (development only)

## Debugging

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

- Database is MariaDB running in Docker, not SQLite
- All services must be running via Docker Compose
- Test users are seeded via `AddTestUsersSeeder`
- Tenant relationships are stored in `tenant_users` pivot table
- JWT claims include `tenants` array and `current_tenant`
- **Domain Consistency**: All apps use `localhost` domain to ensure proper session sharing
- **Processing Page**: SSO authentication uses JavaScript-based checking for seamless UX
- **Laravel Authentication**: Tenant apps use Laravel's built-in auth system with local user accounts
- **User Synchronization**: SSO users are automatically created/updated as local users in tenant apps
- **Dual Authentication**: Supports both local login and SSO authentication in tenant applications
- **Laravel Telescope**: Required dependency for all applications to function properly

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