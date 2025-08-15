# Setup Guide

## Prerequisites

- Docker and Docker Compose installed
- Git
- Text editor/IDE

## Quick Setup

### 1. Clone Repository
```bash
git clone <repository-url>
cd sso-poc-claude3
```

### 2. Start Services
```bash
docker-compose up -d
```

### 3. Setup Hosts File

Add these entries to your `/etc/hosts` file (Linux/Mac) or `C:\Windows\System32\drivers\etc\hosts` (Windows):

```
127.0.0.1 sso.localhost
127.0.0.1 tenant1.localhost
127.0.0.1 tenant2.localhost
```

### 4. Access Applications

- **Central SSO**: http://sso.localhost:8000
- **Tenant 1**: http://tenant1.localhost:8001
- **Tenant 2**: http://tenant2.localhost:8002
- **Database**: localhost:3307 (MariaDB)

## Detailed Setup

### Docker Services Overview

| Service | Port | Purpose |
|---------|------|---------|
| central-sso | 8000 | Central SSO API and dashboard |
| tenant1-app | 8001 | First client application |
| tenant2-app | 8002 | Second client application |
| mariadb | 3307 | Database server |

### Environment Configuration

Each Laravel application has its own `.env` file:

#### Central SSO (`.env`)
```env
APP_NAME="Central SSO"
APP_ENV=local
APP_KEY=base64:generated_key_here
APP_DEBUG=true
APP_URL=http://sso.localhost:8000

DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=sso_main
DB_USERNAME=sso_user
DB_PASSWORD=sso_password

JWT_SECRET=your_jwt_secret_here
JWT_TTL=60

CENTRAL_DOMAINS=tenant1.localhost:8001,tenant2.localhost:8002
```

#### Tenant Apps (`.env`)
```env
APP_NAME="Tenant 1"
APP_ENV=local
APP_KEY=base64:generated_key_here
APP_DEBUG=true
APP_URL=http://tenant1.localhost:8001

DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=tenant1_db
DB_USERNAME=sso_user
DB_PASSWORD=sso_password

CENTRAL_SSO_URL=http://sso.localhost:8000
CENTRAL_SSO_API=http://central-sso:8000/api
TENANT_SLUG=tenant1
```

### Database Setup

The setup automatically creates three databases:

1. **sso_main** - Central SSO data
   - Users table
   - Tenants table
   - User-tenant relationships

2. **tenant1_db** - Tenant 1 specific data
   - Local sessions
   - Tenant-specific user data

3. **tenant2_db** - Tenant 2 specific data
   - Local sessions
   - Tenant-specific user data

### Initial Data

#### Default Admin User
- **Email**: admin@sso.localhost
- **Password**: password
- **Role**: Super Admin

#### Default Tenants
- **Tenant 1**: 
  - Name: "Tenant One"
  - Slug: "tenant1"
  - Domain: "tenant1.localhost:8001"

- **Tenant 2**: 
  - Name: "Tenant Two"
  - Slug: "tenant2"
  - Domain: "tenant2.localhost:8002"

## Development Workflow

### Starting Development
```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

### Working with Individual Services

#### Central SSO
```bash
# Enter container
docker-compose exec central-sso bash

# Run artisan commands
docker-compose exec central-sso php artisan migrate
docker-compose exec central-sso php artisan tenants:seed
```

#### Tenant Apps
```bash
# Enter tenant1 container
docker-compose exec tenant1-app bash

# Run artisan commands
docker-compose exec tenant1-app php artisan migrate
```

### Database Access

#### Using docker-compose
```bash
# Access MariaDB CLI
docker-compose exec mariadb mysql -u sso_user -p
```

#### Using external client
- **Host**: localhost
- **Port**: 3307
- **Username**: sso_user
- **Password**: sso_password

### Rebuilding Services

#### Rebuild specific service
```bash
docker-compose build central-sso
docker-compose up -d central-sso
```

#### Rebuild all services
```bash
docker-compose build
docker-compose up -d
```

### Clearing Data

#### Reset databases
```bash
docker-compose down -v
docker-compose up -d
```

#### Reset application cache
```bash
docker-compose exec central-sso php artisan cache:clear
docker-compose exec central-sso php artisan config:clear
docker-compose exec tenant1-app php artisan cache:clear
docker-compose exec tenant2-app php artisan cache:clear
```

## Troubleshooting

### Common Issues

#### Port Already in Use
```bash
# Check what's using the port
lsof -i :8000

# Kill the process
kill -9 <PID>
```

#### Database Connection Failed
- Ensure MariaDB container is running
- Check database credentials in `.env` files
- Verify database exists

#### Cross-Origin Issues
- Ensure hosts file is configured correctly
- Check CORS configuration in Laravel apps
- Verify JWT token is being passed correctly

#### Container Won't Start
```bash
# Check container logs
docker-compose logs central-sso

# Rebuild container
docker-compose build central-sso --no-cache
```

### Useful Commands

#### View running containers
```bash
docker-compose ps
```

#### Access container shell
```bash
docker-compose exec central-sso bash
```

#### Follow logs
```bash
docker-compose logs -f central-sso
```

#### Reset everything
```bash
docker-compose down -v
docker system prune -f
docker-compose up -d
```