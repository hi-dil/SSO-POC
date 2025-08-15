# Multi-Tenant SSO Proof of Concept

A complete Single Sign-On (SSO) implementation with multi-tenant support built using Laravel and Docker. This project demonstrates secure authentication flows, JWT token management, and tenant isolation patterns.

## 🚀 Quick Start

```bash
# Clone and start services
git clone <repository-url>
cd sso-poc-claude3
docker compose up -d

# Run database migrations and seed test data
docker exec central-sso php artisan migrate
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder

# Access applications
# Central SSO: http://localhost:8000
# Tenant 1:    http://localhost:8001  
# Tenant 2:    http://localhost:8002
```

## 🏗️ Architecture Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Tenant 1 App  │    │   Tenant 2 App  │    │   Central SSO   │
│  localhost:8001 │    │  localhost:8002 │    │  localhost:8000 │
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
                    └─────────────────────────┘
```

### Core Components

- **Central SSO Server** (`localhost:8000`) - Laravel application handling authentication and tenant management
- **Tenant Applications** (`localhost:8001`, `localhost:8002`) - Laravel apps that authenticate via SSO
- **MariaDB Database** - Multi-tenant data storage with complete isolation
- **Docker Network** - All services communicate securely via Docker network

## ✨ Key Features

- **🏢 Multi-tenant Architecture** - Complete data isolation with separate databases per tenant
- **🔐 Dual Authentication Methods** - SSO redirect flows + direct API authentication
- **🎟️ JWT-based Authentication** - Stateless token authentication with tenant-specific claims
- **👥 Cross-tenant User Access** - Users can belong to multiple tenants
- **🛡️ Security Best Practices** - CSRF protection, rate limiting, secure password hashing
- **🐳 Dockerized Environment** - Complete development setup with Docker Compose
- **🔍 Laravel Telescope** - Built-in debugging and monitoring tools

## 📋 Test Credentials

All test users use password: **password**

| Email | Tenant Access | Role | Description |
|-------|---------------|------|-------------|
| `user@tenant1.com` | Tenant 1 | User | Regular user for Tenant 1 |
| `admin@tenant1.com` | Tenant 1 | Admin | Administrator for Tenant 1 |
| `user@tenant2.com` | Tenant 2 | User | Regular user for Tenant 2 |
| `admin@tenant2.com` | Tenant 2 | Admin | Administrator for Tenant 2 |
| `superadmin@sso.com` | Both Tenants | Admin | Super admin with multi-tenant access |

## 🔧 Common Commands

### Database Operations
```bash
# Run migrations
docker exec central-sso php artisan migrate

# Seed test data
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder

# Connect to database
docker exec -it mariadb mysql -u sso_user -psso_password sso_main
```

### Development
```bash
# View logs
docker compose logs central-sso
docker compose logs tenant1-app

# Restart services
docker compose restart

# Clear cache
docker exec central-sso php artisan cache:clear
```

## 📚 Documentation

Comprehensive documentation is available in the [docs/](./docs/) directory:

- **[Setup Guide](./docs/setup-guide.md)** - Detailed local development setup
- **[Architecture Overview](./docs/architecture.md)** - System design and components
- **[Authentication Flow](./docs/authentication-flow.md)** - Detailed auth workflows
- **[API Documentation](./docs/api-documentation.md)** - API endpoints and usage
- **[Tenant Management](./docs/tenant-management.md)** - Multi-tenancy implementation
- **[Database Schema](./docs/database-schema.md)** - Database structure and relationships
- **[Testing Guide](./docs/testing-guide.md)** - Testing SSO integration

## 🔐 Authentication Flows

### 1. SSO Redirect Flow
1. User visits tenant application
2. Redirected to Central SSO login
3. Successful authentication generates JWT token
4. Redirected back to tenant with token
5. Tenant validates token and creates session

### 2. Direct API Authentication
1. Client sends credentials to `/api/auth/login`
2. Central SSO validates and returns JWT token
3. Token includes tenant-specific claims
4. Client uses token for subsequent requests

### 3. Multi-Tenant Access
1. Users can belong to multiple tenants
2. JWT tokens include `tenants` array claim
3. `current_tenant` claim specifies active tenant
4. Token validation checks tenant access rights

## 🛠️ Development Tools

### Laravel Telescope
Monitor and debug your application at `http://localhost:8000/telescope`

- Request monitoring
- Database query inspection
- Exception tracking
- Job monitoring
- Cache operations

### Docker Services

| Service | Port | Purpose |
|---------|------|---------|
| central-sso | 8000 | Central SSO API and dashboard |
| tenant1-app | 8001 | First client application |
| tenant2-app | 8002 | Second client application |
| mariadb | 3307 | Database server (external access) |

## 🔒 Security Features

- **JWT Token Security** - Signed with HMAC-SHA256, configurable expiration
- **Password Security** - Bcrypt hashing with 12 rounds
- **Tenant Isolation** - Complete data separation at database level
- **CORS Protection** - Proper cross-origin request handling
- **Rate Limiting** - Authentication endpoint protection
- **CSRF Protection** - Form submission security

## 🧪 Testing

```bash
# Run PHPUnit tests (if available)
docker exec central-sso php artisan test

# Manual testing endpoints
curl http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@tenant1.com","password":"password","tenant_slug":"tenant1"}'
```

## 📁 Project Structure

```
sso-poc-claude3/
├── central-sso/          # Main SSO authentication server
├── tenant1-app/          # Tenant 1 application  
├── tenant2-app/          # Tenant 2 application
├── docs/                 # Complete documentation
├── docker/               # Docker configuration files
├── docker-compose.yml    # Services orchestration
└── README.md            # This file
```

## 🚨 Troubleshooting

### Common Issues

**Database Connection Failed**
- Ensure MariaDB container is running: `docker ps`
- Check credentials in `.env` files
- Restart services: `docker compose restart`

**Port Already in Use**
```bash
lsof -i :8000  # Check what's using the port
kill -9 <PID>  # Kill the process
```

**Cross-Origin Issues**
- Verify all containers are running
- Check JWT token is being passed correctly
- Review CORS configuration in Laravel apps

**Token Validation Errors**
- Ensure user has access to the specified tenant
- Check token expiration (default 1 hour)
- Verify JWT secret consistency across services

### Reset Everything
```bash
docker compose down -v  # Remove containers and volumes
docker system prune -f  # Clean up Docker resources
docker compose up -d    # Start fresh
```

## 🏷️ Version Information

- **Laravel**: 11.x
- **PHP**: 8.2+
- **Database**: MariaDB 10.6
- **Authentication**: JWT (tymon/jwt-auth)
- **Multi-tenancy**: stancl/tenancy package

## 📄 License

This is a proof of concept project for educational and demonstration purposes.

---

**Need Help?** Check the [documentation](./docs/) or review the [troubleshooting guide](./docs/setup-guide.md#troubleshooting).