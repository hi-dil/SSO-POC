# Architecture Overview

## System Components

### Central SSO API (`sso.localhost:8000`)
- **Framework**: Laravel 11
- **Purpose**: Authentication provider, tenant management, admin dashboard
- **Features**:
  - JWT token generation and validation
  - Multi-tenant user management with Stancl/tenancy
  - Admin dashboard for tenant creation
  - User registration with tenant selection
  - API endpoints for client authentication

### Client Applications
- **Tenant 1**: `tenant1.localhost:8001` (Laravel 11)
- **Tenant 2**: `tenant2.localhost:8002` (Laravel 11)
- **Features**:
  - Dual login methods (SSO redirect + local forms)
  - JWT token validation
  - Auto-tenant detection from subdomain
  - User registration with auto-tenant assignment

### Database Layer
- **Engine**: MariaDB
- **Structure**: Separate databases per tenant
  - `sso_main` - Central SSO data
  - `tenant1_db` - Tenant 1 specific data
  - `tenant2_db` - Tenant 2 specific data

## Service Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Client App 1  │    │   Client App 2  │    │   Central SSO   │
│  (tenant1.*)    │    │  (tenant2.*)    │    │    (sso.*)      │
│   Port: 8001    │    │   Port: 8002    │    │   Port: 8000    │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          │              JWT Authentication              │
          └──────────────────────┼──────────────────────┘
                                 │
                    ┌─────────────┴───────────┐
                    │      MariaDB           │
                    │   ┌─────────────────┐   │
                    │   │   sso_main      │   │
                    │   │   tenant1_db    │   │
                    │   │   tenant2_db    │   │
                    │   └─────────────────┘   │
                    │     Port: 3307         │
                    └─────────────────────────┘
```

## Multi-Tenancy Strategy

### Tenant Identification
- **Method**: Subdomain-based routing
- **Examples**: 
  - `tenant1.localhost:8001` → Tenant 1
  - `tenant2.localhost:8002` → Tenant 2

### Data Isolation
- **Strategy**: Database-per-tenant using Stancl/tenancy package
- **Benefits**:
  - Complete data isolation
  - Scalable architecture
  - Easy backup/restore per tenant
  - Custom configurations per tenant

### User Management
- **Cross-tenant users**: Users can belong to multiple tenants
- **Central identity**: User identity managed in central SSO
- **Tenant-specific data**: User preferences/roles stored per tenant

## Security Considerations

### JWT Token Security
- Stateless authentication
- Configurable expiration times
- Secure token generation with Laravel Sanctum/Passport

### Database Security
- Isolated tenant databases
- Connection-level tenant switching
- No cross-tenant data leakage

### Authentication Security
- Secure password hashing
- Rate limiting on auth endpoints
- CSRF protection on forms