# Documentation

Complete documentation for the multi-tenant SSO system, organized by audience and use case.

## 🚀 Quick Navigation

### **New to the project?**
Start with **[Getting Started](getting-started/README.md)** for setup and basic usage.

### **Need to understand the system?**
Read the **[Architecture Overview](architecture/README.md)** for design and concepts.

### **Ready for production?**
Follow the **[Deployment Guide](deployment/README.md)** for production deployment.

### **Working on specific tasks?**
Check the **[Guides](guides/)** for step-by-step instructions.

### **Looking for technical details?**
See the **[Reference](reference/)** section for APIs and configuration.

### **Planning future features?**
Check the **[TODO Documentation](todo/README.md)** for planned enhancements and roadmap.

## 📚 Documentation Sections

### 📖 [Getting Started](getting-started/README.md)
**Quick setup and local development**
- [Quick Start Guide](getting-started/quick-start.md) - 5-minute setup
- [Local Development Setup](getting-started/local-development.md) - Complete development environment

### 🏗️ [Architecture](architecture/README.md)
**System design and concepts**
- [Authentication Systems](architecture/authentication.md) - SSO flows and security
- [Multi-Tenancy Design](architecture/multi-tenancy.md) - Tenant isolation and management
- [Database Design](architecture/database-design.md) - Schema and relationships

### 🚀 [Deployment](deployment/README.md)
**Production deployment and operations**
- [Cloudflare Tunnel Deployment](deployment/cloudflare-tunnel-deployment.md) - Zero-trust production setup
- [CI/CD Pipeline](deployment/cicd-pipeline.md) - Automated deployment with GitHub Actions
- [Monitoring Setup](deployment/prometheus-grafana-monitoring.md) - Observability and alerting

### 📋 [Guides](guides/README.md)
**Step-by-step instructions for common tasks**
- [User Management](guides/user-management.md) - Managing users and profiles
- [Role Management](guides/role-management.md) - RBAC implementation
- [Tenant Management](guides/tenant-management.md) - Adding and configuring tenants
- [Tenant Integration](guides/tenant-integration.md) - Integrating new applications with SSO
- [Security Guide](guides/security.md) - Security best practices

### 📄 [Reference](reference/README.md)
**Technical reference and APIs**
- [API Documentation](reference/api.md) - Complete REST API reference
- [Configuration Reference](reference/configuration.md) - All configuration options
- [CLI Commands](reference/cli-commands.md) - Available Artisan commands
- [Troubleshooting Guide](reference/troubleshooting.md) - Common issues and solutions

### 📋 [TODO Documentation](todo/README.md)
**Future features and roadmap**
- [External SSO Integration](todo/external-sso-integration.md) - OAuth provider integration (Google, GitHub, etc.)
- Future enhancements and planned features

## 🎯 Quick Start

1. **Setup**: `docker compose up -d`
2. **Database**: `docker exec central-sso php artisan migrate`
3. **Test Data**: `docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder`
4. **Access**:
   - Central SSO: http://localhost:8000
   - Tenant 1: http://localhost:8001
   - Tenant 2: http://localhost:8002

## Key Features

### Authentication & SSO
- **🏗️ Dual-Session Architecture** - Direct login to tenant apps with centralized credential validation
- **Multiple Authentication Methods**:
  - **Direct Login**: Users login directly in tenant apps using SSO credentials
  - **SSO Redirect**: Traditional SSO flow with seamless JavaScript-based checking
  - **API Authentication**: Programmatic access with JWT tokens
- **Centralized Security** - All credentials validated through central SSO API
- **Local Session Management** - Each tenant app maintains independent Laravel sessions
- **Automatic User Sync** - User data synchronized from central SSO on every login
- **Multi-tenant user access** with proper access control
- **Performance Optimized** - Local sessions reduce API calls after authentication

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
- **🎨 shadcn/ui Design System** - Complete design system with dark/light themes and accessible components
- **Professional landing page** with live statistics and feature showcase
- **Modern admin interface** with consistent visual language and responsive design
- **Dark Mode Support** - Full dark/light theme system with user preference persistence
- **Toast notification system** with animated, dismissible notifications
- **Responsive design** working seamlessly on desktop and mobile devices
- **Alpine.js Interactivity** - Lightweight JavaScript framework for reactive components
- **Accessible Components** - WCAG compliant with proper color contrast and semantic HTML
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

### 🔄 Authentication Methods Available

✅ **All users can login using EITHER method:**
- **Direct Login**: Visit tenant apps directly (`localhost:8001/login`, `localhost:8002/login`)
- **SSO Redirect**: Click "Login with Central SSO" button in tenant apps

### Single Tenant Users
- `user@tenant1.com` - Tenant 1 User (Direct + SSO Login)
- `admin@tenant1.com` - Tenant 1 Admin (Direct + SSO Login)
- `user@tenant2.com` - Tenant 2 User (Direct + SSO Login)
- `admin@tenant2.com` - Tenant 2 Admin (Direct + SSO Login)

### Multi-Tenant User
- `superadmin@sso.com` - Super Admin with access to both tenants and all permissions (Direct + SSO Login)

### Example Usage
```bash
# Direct login to Tenant 1
# Visit: http://localhost:8001/login
# Enter: superadmin@sso.com / password
# Result: Authenticated via dual-session architecture

# Direct login to Tenant 2  
# Visit: http://localhost:8002/login
# Enter: superadmin@sso.com / password
# Result: Same seamless authentication experience
```