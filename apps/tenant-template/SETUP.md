# 🚀 Secure Tenant App Setup Guide

Complete setup instructions for deploying your secure tenant application.

## 📋 Prerequisites

- PHP 8.2+
- Composer
- Node.js & npm
- Docker & Docker Compose (recommended)
- Git

## 🛠️ Installation Steps

### 1. Copy Template
```bash
cp -r tenant-template/ my-tenant-app/
cd my-tenant-app/
```

### 2. Install Dependencies
```bash
# PHP dependencies
composer install

# Node.js dependencies (if using frontend build tools)
npm install

# Generate application key
php artisan key:generate
```

### 3. Environment Configuration

Copy the example environment file:
```bash
cp .env.example .env
```

**Required Configuration:**
```env
# Application
APP_NAME="My Secure Tenant App"
APP_URL=http://localhost:8003
TENANT_SLUG=my-tenant

# Database
DB_DATABASE=my_tenant_db
DB_USERNAME=tenant_user
DB_PASSWORD=secure_password

# Security (Get from SSO Administrator)
TENANT_API_KEY=your_tenant_api_key_here
HMAC_SECRET=your_hmac_secret_here
CENTRAL_SSO_URL=http://localhost:8000
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed basic data (optional)
php artisan db:seed
```

### 5. Start Application

**Option A: Laravel Development Server**
```bash
php artisan serve --port=8003
```

**Option B: Docker (Recommended)**
```bash
docker-compose up -d
```

## 🔐 Security Configuration

### API Key Generation
Contact your central SSO administrator to generate:
- `TENANT_API_KEY`: Unique 32-character API key
- `HMAC_SECRET`: 64-character HMAC secret

### SSL/TLS Setup (Production)
```env
SSL_ENABLED=true
SSL_VERIFY=true
SESSION_SECURE_COOKIE=true
```

### Rate Limiting
```env
LOGIN_RATE_LIMIT=5
LOGIN_RATE_WINDOW=300
```

## 🧪 Testing Setup

### Test SSO Integration
```bash
curl http://localhost:8003/health
curl http://localhost:8003/dev/security-check
```

### Test Authentication
1. Visit `http://localhost:8003/login`
2. Try both direct login and SSO login
3. Verify audit logs in central SSO

## 📊 Monitoring Setup

### Health Checks
- Application: `GET /health`
- Security check: `GET /dev/security-check` (dev only)

### Log Files
- Application logs: `storage/logs/laravel.log`
- Security events: Logged to central SSO audit system

## 🚨 Troubleshooting

### Common Issues

**1. API Key Authentication Fails**
```bash
# Check configuration
php artisan config:clear
php artisan cache:clear

# Verify API key format
echo "API Key length: $(echo -n $TENANT_API_KEY | wc -c)"
```

**2. HMAC Signature Errors**
- Ensure HMAC_SECRET matches central SSO
- Check system time synchronization
- Verify request format

**3. SSL Certificate Issues**
```env
# For development only
SSL_VERIFY=false
```

**4. Database Connection**
```bash
# Test database connection
php artisan tinker
> DB::connection()->getPdo();
```

### Debug Mode
```env
# Enable for development only
APP_DEBUG=true
VERBOSE_SECURITY_LOGGING=true
```

## 🔧 Customization

### Branding
1. Update `config/app.php` - name and details
2. Modify views in `resources/views/`
3. Update CSS/assets in `public/`

### Additional Features
1. Add routes in `routes/web.php`
2. Create controllers in `app/Http/Controllers/`
3. Add models in `app/Models/`

### Security Settings
Modify `config/security.php` for custom:
- Rate limiting rules
- Audit preferences
- Timeout values
- Feature flags

## 📖 Documentation

- [Main SSO Documentation](../CLAUDE.md)
- [Security Integration Guide](../CLAUDE.md#-secure-tenant-integration-guide)
- [Production Deployment](../CLAUDE.md#-production-deployment-guide)

## 🆘 Support

1. Check logs: `tail -f storage/logs/laravel.log`
2. Contact SSO administrator for API key issues
3. Review security documentation for authentication problems
4. Test with development security check endpoint

## ✅ Deployment Checklist

### Development
- [ ] Environment configured
- [ ] Dependencies installed
- [ ] Database migrated
- [ ] API keys configured
- [ ] Health checks passing
- [ ] Authentication tested

### Production
- [ ] SSL certificates installed
- [ ] Security features enabled
- [ ] Rate limiting configured
- [ ] Monitoring setup
- [ ] Backup strategy implemented
- [ ] Documentation updated