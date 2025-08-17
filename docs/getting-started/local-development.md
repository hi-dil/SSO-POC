# Local Development Setup

Complete guide for setting up a development environment for the multi-tenant SSO system.

## 📋 Prerequisites

### Required Software
- **Docker** (v20.10+) and **Docker Compose** (v2.0+)
- **Git** for version control
- **Text editor/IDE** (VS Code, PhpStorm, etc.)
- **Web browser** with developer tools

### System Requirements
- **8GB+ RAM** recommended
- **5GB+ free disk space**
- **Ports 8000-8002, 3307** available

### Verification
```bash
# Check Docker installation
docker --version
docker compose version

# Check available ports
lsof -i :8000 :8001 :8002 :3307
```

## 🚀 Development Setup

### 1. Repository Setup
```bash
# Clone the repository
git clone <repository-url>
cd sso-poc-claude3

# Verify project structure
ls -la
```

### 2. Environment Configuration

Each application needs its own `.env` file. Use the provided template:

```bash
# Copy environment template
cp .env.example central-sso/.env
cp .env.example tenant1-app/.env
cp .env.example tenant2-app/.env
```

#### Central SSO Configuration
Edit `central-sso/.env`:

```env
# Application Settings
APP_NAME="Central SSO"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=sso_main
DB_USERNAME=sso_user
DB_PASSWORD=sso_password

# JWT Configuration
JWT_SECRET=your_32_character_jwt_secret_here
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=localhost

# Multi-tenant Configuration
CENTRAL_DOMAINS=localhost:8001,localhost:8002
```

#### Tenant 1 Configuration
Edit `tenant1-app/.env`:

```env
# Application Settings
APP_NAME="Tenant 1 Application"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8001

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=tenant1_db
DB_USERNAME=sso_user
DB_PASSWORD=sso_password

# SSO Configuration
CENTRAL_SSO_URL=http://localhost:8000
CENTRAL_SSO_API=http://central-sso:8000/api
TENANT_SLUG=tenant1

# Session Configuration
SESSION_DRIVER=database
SESSION_DOMAIN=localhost
```

#### Tenant 2 Configuration
Edit `tenant2-app/.env`:

```env
# Application Settings
APP_NAME="Tenant 2 Application"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8002

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=tenant2_db
DB_USERNAME=sso_user
DB_PASSWORD=sso_password

# SSO Configuration
CENTRAL_SSO_URL=http://localhost:8000
CENTRAL_SSO_API=http://central-sso:8000/api
TENANT_SLUG=tenant2

# Session Configuration
SESSION_DRIVER=database
SESSION_DOMAIN=localhost
```

### 3. Generate Application Keys

```bash
# Generate Laravel application keys
docker compose run --rm central-sso php artisan key:generate
docker compose run --rm tenant1-app php artisan key:generate
docker compose run --rm tenant2-app php artisan key:generate

# Generate JWT secret
docker compose run --rm central-sso php artisan jwt:secret
```

### 4. Start Development Environment

```bash
# Start all services
docker compose up -d

# Check all containers are running
docker compose ps

# View startup logs
docker compose logs -f
```

## 🗄️ Database Setup

### Initialize Database Structure

```bash
# Run migrations for Central SSO
docker exec central-sso php artisan migrate

# Run migrations for tenant applications
docker exec tenant1-app php artisan migrate
docker exec tenant2-app php artisan migrate
```

### Seed Test Data

```bash
# Seed central SSO with users and tenants
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder

# Optional: Seed additional test data
docker exec central-sso php artisan db:seed --class=RolesAndPermissionsSeeder
```

### Database Access

```bash
# Connect to database via CLI
docker exec -it sso-mariadb mysql -u sso_user -psso_password

# Show databases
SHOW DATABASES;

# Use main SSO database
USE sso_main;

# Check users table
SELECT email, created_at FROM users;

# Exit
quit
```

## 🔧 Development Tools

### Laravel Telescope (Debug Dashboard)

Access debugging tools at http://localhost:8000/telescope

Features available:
- **Requests** - HTTP request monitoring
- **Commands** - Artisan command execution
- **Schedule** - Scheduled task monitoring
- **Jobs** - Queue job monitoring
- **Exceptions** - Error tracking
- **Logs** - Application log viewing
- **Dumps** - Variable dump output
- **Queries** - Database query analysis

### API Documentation

Access Swagger API docs at http://localhost:8000/api/documentation

### Useful Development Commands

```bash
# Application commands
docker exec central-sso php artisan cache:clear
docker exec central-sso php artisan config:clear
docker exec central-sso php artisan route:list
docker exec central-sso php artisan tinker

# Database commands
docker exec central-sso php artisan migrate:status
docker exec central-sso php artisan migrate:fresh --seed
docker exec central-sso php artisan db:seed

# Testing commands
docker exec central-sso php artisan test
./run_tests.sh  # Run complete test suite

# Check system status
docker exec central-sso php artisan route:list | grep auth
docker exec central-sso php artisan queue:work --once
```

## 🧪 Testing & Validation

### Manual Testing Workflow

1. **Central SSO Login**
   - Visit http://localhost:8000
   - Login with `superadmin@sso.com` / `password`
   - Verify dashboard loads

2. **Direct Tenant Login**
   - Visit http://localhost:8001
   - Login with `admin@tenant1.com` / `password`
   - Verify you're logged into tenant app

3. **Cross-Tenant Access**
   - In same browser, visit http://localhost:8002
   - Click "Login with Central SSO"
   - Should auto-login without entering credentials

4. **API Testing**
   ```bash
   # Test authentication endpoint
   curl -X POST http://localhost:8000/api/auth/login \
     -H "Content-Type: application/json" \
     -d '{"email":"user@tenant1.com","password":"password","tenant_slug":"tenant1"}'
   ```

### Automated Testing

```bash
# Run all tests
./run_tests.sh

# Run specific test suites
docker exec central-sso php artisan test --testsuite=Unit
docker exec central-sso php artisan test --testsuite=Feature
docker exec tenant1-app php artisan test
docker exec tenant2-app php artisan test

# Run with coverage
docker exec central-sso php artisan test --coverage
```

## 🔄 Development Workflows

### Making Code Changes

1. **Backend Changes** (PHP/Laravel)
   - Edit files in `central-sso/`, `tenant1-app/`, `tenant2-app/`
   - Changes are automatically reflected (volume mounts)
   - Clear cache if needed: `docker exec central-sso php artisan cache:clear`

2. **Frontend Changes** (CSS/JS)
   - Edit files in `resources/` directories
   - Rebuild assets: `docker exec central-sso npm run dev`

3. **Database Changes**
   - Create migration: `docker exec central-sso php artisan make:migration create_new_table`
   - Run migration: `docker exec central-sso php artisan migrate`

### Working with Multiple Tenants

```bash
# Add a new tenant via API
curl -X POST http://localhost:8000/api/tenants \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{"name":"New Tenant","slug":"new-tenant","domain":"localhost:8003"}'

# Assign user to tenant
docker exec central-sso php artisan tinker
# In tinker:
$user = App\Models\User::where('email', 'user@example.com')->first();
$tenant = App\Models\Tenant::where('slug', 'new-tenant')->first();
$user->tenants()->attach($tenant);
```

### Debugging Common Issues

```bash
# Clear all caches
docker exec central-sso php artisan optimize:clear

# Check logs
docker compose logs central-sso
docker compose logs tenant1-app
tail -f central-sso/storage/logs/laravel.log

# Restart specific service
docker compose restart central-sso

# Rebuild containers
docker compose down
docker compose build --no-cache
docker compose up -d
```

## 🔒 Security in Development

### HTTPS Setup (Optional)

For testing HTTPS locally:

```bash
# Generate self-signed certificates
./scripts/generate-ssl-certs.sh

# Use secure Docker Compose
docker compose -f docker-compose.yml -f infrastructure/docker/compose/docker-compose.secure.yml up -d
```

### JWT Token Management

```bash
# Generate new JWT secret
docker exec central-sso php artisan jwt:secret

# Test token generation
docker exec central-sso php artisan tinker
# In tinker:
$user = App\Models\User::first();
$token = auth()->login($user);
echo $token;
```

## 📊 Performance Monitoring

### Application Performance

```bash
# Monitor container resources
docker stats

# Check application response times
time curl http://localhost:8000/api/health

# Database query monitoring
# Use Laravel Telescope at http://localhost:8000/telescope
```

### Database Performance

```bash
# Check database connections
docker exec sso-mariadb mysql -u root -p -e "SHOW PROCESSLIST;"

# Monitor slow queries
docker exec sso-mariadb mysql -u root -p -e "SHOW VARIABLES LIKE 'slow_query_log';"
```

## 🛠️ IDE Configuration

### VS Code Setup

Recommended extensions:
- PHP Intelephense
- Laravel Blade Snippets
- Docker
- GitLens

Workspace settings (`.vscode/settings.json`):
```json
{
  "php.validate.executablePath": "/usr/local/bin/php",
  "files.associations": {
    "*.blade.php": "blade"
  }
}
```

### PHPStorm Setup

1. Configure Docker integration
2. Set up Laravel plugin
3. Configure database connection
4. Set up Xdebug for debugging

## 🐞 Advanced Debugging

### Xdebug Setup

Add to Docker Compose for debugging:
```yaml
central-sso:
  environment:
    - XDEBUG_MODE=debug
    - XDEBUG_CONFIG=client_host=host.docker.internal
```

### Database Debugging

```bash
# Enable query logging
docker exec central-sso php artisan tinker
# In tinker:
DB::listen(function ($query) {
    Log::info($query->sql, $query->bindings);
});
```

## 📁 Project Structure Understanding

```
sso-poc-claude3/
├── central-sso/          # Main SSO server
│   ├── app/Http/Controllers/Api/  # API endpoints
│   ├── app/Models/               # User, Tenant models
│   ├── database/migrations/      # Database structure
│   └── routes/api.php           # API routes
├── tenant1-app/          # First tenant application
├── tenant2-app/          # Second tenant application
├── infrastructure/      # Docker & deployment configs
└── docs/               # Documentation (you are here)
```

## 🔄 Reset Development Environment

```bash
# Complete reset (removes all data)
docker compose down -v
docker system prune -f
docker compose up -d

# Re-setup database
docker exec central-sso php artisan migrate
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder
docker exec tenant1-app php artisan migrate
docker exec tenant2-app php artisan migrate
```

---

## 🎯 Next Steps

After completing local development setup:

1. **Understand the Architecture**: [Architecture Overview](../architecture/README.md)
2. **Learn User Management**: [User Management Guide](../guides/user-management.md)
3. **API Integration**: [API Reference](../reference/api.md)
4. **Production Deployment**: [Deployment Guide](../deployment/README.md)