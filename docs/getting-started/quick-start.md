# Quick Start Guide

Get the multi-tenant SSO system running in under 5 minutes with zero configuration.

## ⚡ Quick Setup

```bash
# Clone the repository
git clone <repository-url>
cd sso-poc-claude3

# Configure environment (copy template with defaults)
cp .env.docker .env

# Start all services
docker compose up -d

# Set up database and test data
docker exec central-sso php artisan migrate
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder
```

**That's it!** The system is now running and ready to use.

> 💡 **Customization Note**: The `.env.docker` file contains sensible defaults for local development. For production or custom configurations, edit your `.env` file to modify ports, database settings, security keys, and other options.

## 🌐 Access the Applications

| Service | URL | Purpose |
|---------|-----|---------|
| **Central SSO** | http://localhost:8000 | Main SSO server and admin dashboard |
| **Tenant 1** | http://localhost:8001 | First tenant application |
| **Tenant 2** | http://localhost:8002 | Second tenant application |

## 🔑 Test the System

### 1. Login to Central SSO
1. Visit http://localhost:8000
2. Click **"Login"**
3. Use credentials: `superadmin@sso.com` / `password`
4. Explore the admin dashboard

### 2. Test Cross-Tenant Access
1. Visit http://localhost:8001 (Tenant 1)
2. Click **"Login with Central SSO"**
3. You'll be automatically logged in (already authenticated)
4. Try the same with http://localhost:8002 (Tenant 2)

### 3. Test Direct Login
1. Open a new incognito/private browser window
2. Visit http://localhost:8001
3. Use the login form directly with: `admin@tenant1.com` / `password`
4. Note how you're logged in without visiting Central SSO

## 🧪 Verify Everything Works

### Check Services Status
```bash
# All containers should be running
docker compose ps

# Check logs if any issues
docker compose logs central-sso
docker compose logs tenant1-app
docker compose logs tenant2-app
```

### Test Database Connection
```bash
# Connect to database
docker exec -it sso-mariadb mysql -u sso_user -psso_password sso_main

# Check test users exist
SELECT email, id FROM users;
quit
```

### Test API Endpoints
```bash
# Test Central SSO API
curl http://localhost:8000/api/health

# Test authentication endpoint
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@tenant1.com","password":"password","tenant_slug":"tenant1"}'
```

## 🔧 Available Test Users

All users use password: **password**

| Email | Access | Description |
|-------|--------|-------------|
| `superadmin@sso.com` | Both tenants | Super admin with full access |
| `admin@tenant1.com` | Tenant 1 | Tenant 1 administrator |
| `user@tenant1.com` | Tenant 1 | Regular Tenant 1 user |
| `admin@tenant2.com` | Tenant 2 | Tenant 2 administrator |
| `user@tenant2.com` | Tenant 2 | Regular Tenant 2 user |

## 🎯 What You Just Created

Your quick setup includes:

✅ **Multi-Tenant SSO Server** - Central authentication hub  
✅ **Two Tenant Applications** - Independent apps using SSO  
✅ **MariaDB Database** - Multi-tenant data storage  
✅ **JWT Authentication** - Stateless token-based auth  
✅ **Cross-Domain Sessions** - Seamless user experience  
✅ **Admin Dashboard** - User and tenant management  
✅ **API Endpoints** - REST API for integrations  

## 🚨 Troubleshooting

### Services Won't Start
```bash
# Check if ports are available
lsof -i :8000
lsof -i :8001
lsof -i :8002

# Kill processes using the ports if needed
kill -9 <PID>

# Restart services
docker compose restart
```

### Database Connection Issues
```bash
# Restart MariaDB
docker compose restart mariadb

# Check database logs
docker compose logs mariadb

# Wait for database to be ready (can take 30-60 seconds)
docker exec central-sso php artisan migrate:status
```

### Browser Issues
- Clear browser cache and cookies
- Try incognito/private browsing mode
- Ensure you're using `localhost` (not `127.0.0.1`)

## ⚡ Quick Commands Reference

```bash
# Start everything
docker compose up -d

# Stop everything
docker compose down

# View logs
docker compose logs -f central-sso

# Reset database
docker compose down -v
docker compose up -d
docker exec central-sso php artisan migrate
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder

# Clear application cache
docker exec central-sso php artisan cache:clear
docker exec tenant1-app php artisan cache:clear
docker exec tenant2-app php artisan cache:clear
```

## 🎉 Success!

If everything is working, you now have:

- A working multi-tenant SSO system
- Cross-application authentication
- Admin dashboard for user management
- API endpoints for integration

## 📖 What's Next?

- **Learn More**: [Local Development Setup](local-development.md) for detailed configuration
- **Understand the Architecture**: [Architecture Overview](../architecture/README.md)
- **Production Deployment**: [Deployment Guide](../deployment/README.md)
- **API Integration**: [API Reference](../reference/api.md)

---

⚠️ **Note**: This quick setup uses default configurations suitable for development. For production deployment, see the [deployment section](../deployment/README.md).