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
```bash
# Run tests (if available)
docker exec central-sso php artisan test

# Check application status
curl http://localhost:8000/telescope
curl http://localhost:8001
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

### Authentication Flows
1. **Central SSO Login**: Users login at `localhost:8000/login` and get redirected to tenant apps
2. **Seamless SSO**: Users already authenticated are automatically redirected without login form
3. **Tenant-Specific Login**: Users can login directly via tenant-specific SSO pages
4. **API Authentication**: Direct API calls for programmatic access
5. **Multi-Tenant Support**: Users can have access to multiple tenants

### Seamless SSO Process
- **Processing Page**: Shows loading state while checking authentication
- **Auto-Detection**: JavaScript checks if user is already authenticated
- **Smart Redirect**: Automatically redirects authenticated users to tenant apps
- **Graceful Fallback**: Shows login form only when authentication is required

### Token Management
- JWT tokens with tenant-specific claims
- 1-hour token expiration
- Token validation across tenant boundaries
- Refresh token capability

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

### Common Issues
- **Invalid credentials**: Ensure using MariaDB, not SQLite
- **Database connection**: Check Docker containers are running
- **Token validation**: Verify tenant associations in database
- **CORS issues**: Check tenant app configurations
- **Domain consistency**: All apps must use `localhost` domain for session sharing

## Important Notes

- Database is MariaDB running in Docker, not SQLite
- All services must be running via Docker Compose
- Test users are seeded via `AddTestUsersSeeder`
- Tenant relationships are stored in `tenant_users` pivot table
- JWT claims include `tenants` array and `current_tenant`
- **Domain Consistency**: All apps use `localhost` domain to ensure proper session sharing
- **Processing Page**: SSO authentication uses JavaScript-based checking for seamless UX