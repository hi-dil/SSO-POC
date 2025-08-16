# Architecture Overview

## System Components

### Central SSO Server (`localhost:8000`)
- **Framework**: Laravel 11
- **Purpose**: Authentication provider, tenant management, role management, admin dashboard
- **Features**:
  - JWT token generation and validation
  - Multi-tenant user management with pivot table relationships
  - Role-based access control (RBAC) with granular permissions
  - Modern admin dashboard with shadcn/ui design
  - User registration with tenant selection
  - API endpoints for client authentication
  - Laravel Telescope integration for debugging
  - Swagger/OpenAPI documentation
  - Toast notification system

### Tenant Applications
- **Tenant 1**: `localhost:8001` (Laravel 11)
- **Tenant 2**: `localhost:8002` (Laravel 11)
- **Features**:
  - **Dual-Session Architecture**: Local Laravel sessions + central SSO authentication
  - **Multiple Login Methods**: SSO redirect + direct login forms
  - **API-based Authentication**: All credentials validated through central SSO API
  - **JWT token validation** with tenant access control
  - **Seamless SSO processing** with JavaScript-based authentication
  - **Local user synchronization** from central SSO with automatic updates
  - **Laravel authentication system** for local session management
  - **Session data caching**: Stores JWT tokens and SSO user data for performance
  - **Support for both authenticated and guest users**

### Database Layer
- **Engine**: MariaDB
- **Structure**: Single database with proper tenant isolation
  - `sso_main` - Central SSO data with all tenant relationships
  - `tenant1_db` - Tenant 1 application data
  - `tenant2_db` - Tenant 2 application data

## Service Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Tenant App 1  │    │   Tenant App 2  │    │   Central SSO   │
│  localhost:8001 │    │  localhost:8002 │    │  localhost:8000 │
│                 │    │                 │    │                 │
│ ┌─────────────┐ │    │ ┌─────────────┐ │    │ ┌─────────────┐ │
│ │ SSO Service │ │    │ │ SSO Service │ │    │ │ Role Mgmt   │ │
│ │ Controllers │ │    │ │ Controllers │ │    │ │ API Endpoints│ │
│ │ Local Auth  │ │    │ │ Local Auth  │ │    │ │ Admin UI    │ │
│ └─────────────┘ │    │ └─────────────┘ │    │ │ Telescope   │ │
└─────────┬───────┘    └─────────┬───────┘    │ │ Swagger     │ │
          │                      │            │ └─────────────┘ │
          │              JWT Authentication & Session Management │
          └──────────────────────┼──────────────────────────────┘
                                 │
                    ┌─────────────┴───────────┐
                    │      MariaDB           │
                    │   ┌─────────────────┐   │
                    │   │   sso_main      │   │ ← Central auth, roles, tenants
                    │   │   tenant1_db    │   │ ← Tenant 1 app data
                    │   │   tenant2_db    │   │ ← Tenant 2 app data
                    │   └─────────────────┘   │
                    │     Port: 3306         │
                    └─────────────────────────┘
```

## Multi-Tenancy Strategy

### Tenant Identification
- **Method**: Port-based routing with domain consistency
- **Examples**: 
  - `localhost:8001` → Tenant 1
  - `localhost:8002` → Tenant 2
  - `localhost:8000` → Central SSO

### Data Isolation
- **Strategy**: Database-per-application with central identity management
- **Benefits**:
  - Clear separation between SSO and tenant application data
  - Scalable architecture for new tenant applications
  - Easy backup/restore per tenant
  - Independent tenant application deployments
  - Central user identity with local tenant user synchronization

### User Management
- **Central Identity**: User accounts managed in central SSO with roles and permissions
- **Cross-tenant Access**: Users can have access to multiple tenants via `tenant_users` pivot table
- **Local Synchronization**: Tenant apps maintain local user copies for Laravel authentication
- **Role-based Access**: Fine-grained permissions control access to SSO management functions
- **Tenant-specific Data**: Each tenant app manages its own user data and application-specific roles

## Role-Based Access Control (RBAC)

### Permission System
- **19 Built-in Permissions** across 6 categories:
  - **Users**: view, create, edit, delete (4 permissions)
  - **Roles**: view, create, edit, delete, assign (5 permissions)
  - **Tenants**: view, create, edit, delete (4 permissions)
  - **System**: settings, logs (2 permissions)
  - **API**: manage (1 permission)
  - **Developer**: telescope.access, swagger.access (2 permissions)

### Role Management
- **5 Default Roles**: Super Admin, Admin, Manager, User, Viewer
- **Custom Roles**: Create application-specific roles with custom permissions
- **Multi-tenant Assignment**: Roles can be global or tenant-specific
- **System Protection**: System roles and permissions cannot be deleted

### Access Control Flow
```
User Request → Middleware → Permission Check → Role Validation → Resource Access
```

## Security Considerations

### JWT Token Security
- **HMAC-SHA256 signing** for secure token validation
- **Tenant-specific claims** with current tenant information
- **1-hour expiration** with refresh token capability
- **Stateless authentication** for API endpoints

### Role-Based Security
- **Permission-based middleware** protecting admin functions
- **Multi-tenant role isolation** preventing cross-tenant privilege escalation
- **System permission protection** for critical functions
- **API endpoint protection** with proper authorization

### Database Security
- **Isolated tenant databases** with separate connections
- **Central identity management** with secure user relationships
- **Secure password hashing** using bcrypt with 12 rounds
- **No cross-tenant data leakage** through proper access controls

### Authentication Security
- **Dual-session architecture** with centralized credential validation
- **Session-based web authentication** with CSRF protection
- **Rate limiting** on authentication endpoints
- **Secure session management** with proper invalidation
- **JWT token storage** in secure session cookies
- **Automatic user data synchronization** on each authentication

## Dual-Session Architecture Details

### 🏗️ Architecture Overview

The system implements a **dual-session architecture** that combines the benefits of centralized authentication with local session management:

```
┌─────────────────────────────────────────────────────────────────┐
│                    Dual-Session Flow                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  User Login    →  Central SSO API  →  Local Session Creation    │
│      ↓               ↓                      ↓                   │
│  Credentials   →  JWT + User Data   →  Laravel Auth System      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐     API Call      ┌─────────────────┐
│   Tenant App    │ ←──────────────── │  Central SSO    │
│                 │                   │                 │
│ ┌─────────────┐ │     Response      │ ┌─────────────┐ │
│ │ Login Form  │ │ ──────────────→   │ │ JWT Service │ │
│ └─────────────┘ │                   │ └─────────────┘ │
│       ↓         │                   │                 │
│ ┌─────────────┐ │                   │ ┌─────────────┐ │
│ │Local Session│ │                   │ │User Validator│ │
│ │+ JWT Token  │ │                   │ │             │ │
│ └─────────────┘ │                   │ └─────────────┘ │
└─────────────────┘                   └─────────────────┘
```

### 🔄 Session Management Strategy

#### Central Authentication
- **All credentials** validated by central SSO API
- **Consistent security** across all tenant applications
- **Centralized user management** with roles and permissions
- **Audit trail** for all authentication events

#### Local Session Benefits
- **Performance**: No API calls for protected route access
- **Independence**: Tenant apps work independently after authentication
- **Flexibility**: Custom session lifetimes per tenant
- **Laravel Integration**: Full compatibility with Laravel's auth system

#### Data Synchronization
- **User data sync** on every login (name, email, tenant access)
- **Role information** cached for UI personalization
- **JWT storage** for potential API calls to central SSO
- **Automatic user creation/updates** in tenant databases

### 🔧 Implementation Benefits

#### For Developers
- **Familiar Laravel Auth**: Standard `auth()->user()` works as expected
- **Rich User Data**: Access to both local and SSO user information
- **Flexible Architecture**: Easy to extend for new requirements
- **Clear Separation**: Authentication vs. authorization clearly separated

#### For Users
- **Seamless Experience**: No difference between login methods
- **Consistent Access**: Same credentials work across all applications
- **Single Sign-On**: Optional SSO flow for convenience
- **Direct Access**: Can login directly to any tenant application

#### For System Administrators
- **Centralized Control**: All user management in one place
- **Audit Visibility**: Complete authentication tracking
- **Scalable Design**: Easy to add new tenant applications
- **Security Consistency**: Same security standards across all apps