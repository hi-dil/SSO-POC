# Multi-Tenant SSO Proof of Concept

This documentation covers the architecture, implementation, and usage of a complete multi-tenant Single Sign-On (SSO) system built with Laravel 11 and Docker, featuring role-based access control, modern UI, and comprehensive API documentation.

## Documentation Structure

- [Architecture Overview](./architecture.md) - System design and component relationships
- [Authentication Flow](./authentication-flow.md) - Detailed auth workflows and JWT handling
- [API Documentation](./api-documentation.md) - Central SSO API endpoints and usage
- [Setup Guide](./setup-guide.md) - Local development setup with Docker
- [User Management](./user-management.md) - Central SSO user administration and tenant access
- [Tenant Management](./tenant-management.md) - How tenancy works and tenant integration
- [Role Management](./role-management.md) - Role-based access control system
- [Database Schema](./database-schema.md) - Database structure for multi-tenancy
- [Testing Guide](./testing-guide.md) - Comprehensive testing suite and audit system validation
- [Deployment Guide](./deployment-guide.md) - Production deployment considerations
- [Tenant Integration Guide](./tenant-integration.md) - How to create new tenant applications

## Quick Start

1. Clone the repository
2. Run `docker compose up -d`
3. Run migrations: `docker exec central-sso php artisan migrate`
4. Seed test data: `docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder`
5. Access:
   - Central SSO: `http://localhost:8000`
   - Tenant 1: `http://localhost:8001`
   - Tenant 2: `http://localhost:8002`

## Key Features

### Authentication & SSO
- **Seamless SSO Flow** with JavaScript-based authentication checking
- **Dual authentication methods** (SSO redirect + local forms)
- **JWT-based authentication** for stateless auth
- **Multi-tenant user access** with proper access control
- **Session-based auth** for web interfaces

### User & Access Management
- **Complete user management** with CRUD operations and tenant access control
- **Centralized user administration** for managing all SSO users
- **Tenant access assignment** with granular control over user permissions
- **Password management** with secure confirmation and hashing
- **Admin flag management** for elevated privileges

### User Profile Management
- **Extended user profiles** with personal, professional, and contact information
- **Multi-dimensional profile data** organized into family, contacts, addresses, and social media
- **Family member management** with emergency contacts and relationship tracking
- **Contact information management** supporting multiple contact methods and verification
- **Address management** for home, work, billing, and shipping addresses with international support
- **Social media integration** for professional networks and online presence management
- **Profile completion analytics** and data quality reporting
- **Admin profile management** with bulk operations and comprehensive editing capabilities

### Role-Based Access Control (RBAC)
- **Granular permissions** across 7 categories (Users, Roles, Tenants, System, API, Developer, Profile)
- **25+ built-in permissions** with extensible architecture
- **Multi-tenant role assignment** with global and tenant-specific roles
- **Interactive role management UI** with modern shadcn/ui design
- **API-driven role management** with complete REST endpoints

### Modern UI & UX
- **Professional landing page** with live statistics and feature showcase
- **Modern admin interface** using shadcn/ui design system
- **Toast notifications** for user-friendly feedback
- **Responsive design** working on desktop and mobile
- **Developer tools integration** with permission-controlled access

### Developer Experience
- **Complete API documentation** with Swagger/OpenAPI 3.0
- **Laravel Telescope integration** for debugging and monitoring
- **Dockerized development environment** for easy setup
- **Comprehensive testing suite** with example test cases
- **Detailed integration guides** for new tenant applications

### Login Audit System
- **Comprehensive authentication tracking** across all applications (central SSO + tenants)
- **Real-time analytics dashboard** with live statistics and auto-refresh
- **Multi-method tracking** for direct logins, SSO authentication, and API access
- **Failed attempt monitoring** with detailed failure reasons and analysis
- **Session management** with active session tracking and automatic cleanup
- **Cross-tenant visibility** providing centralized view of all user activity

### Security & Monitoring
- **JWT tokens** with tenant-specific claims and secure validation
- **Permission-based access control** for all admin functions
- **CORS and CSRF protection** enabled across all applications
- **Laravel Telescope** for monitoring and debugging (development only)
- **Secure password hashing** with bcrypt and proper token management
- **Login audit trails** with comprehensive authentication event logging

## Test Credentials

All users use password: **password**

### Single Tenant Users
- `user@tenant1.com` - Tenant 1 User
- `admin@tenant1.com` - Tenant 1 Admin
- `user@tenant2.com` - Tenant 2 User
- `admin@tenant2.com` - Tenant 2 Admin

### Multi-Tenant User
- `superadmin@sso.com` - Super Admin with access to both tenants and all permissions