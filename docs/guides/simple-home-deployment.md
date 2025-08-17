# Simple Home Server Deployment Guide

**Deploy your SSO system on a home server with direct Docker Compose and Cloudflare Tunnel**

## 🏠 Overview

This guide provides a simplified approach to deploying the multi-tenant SSO system on your home server using:

- **Direct Docker Compose**: No CI/CD pipeline complexity
- **Cloudflare Tunnel**: Secure external access without port forwarding
- **Manual Updates**: Simple git pull and restart process
- **Minimal Setup**: Just Docker, domain, and Cloudflare account needed

`★ Insight ─────────────────────────────────────`
This approach is perfect for home users who want a secure, professional SSO system without the complexity of automated CI/CD pipelines. You get enterprise-grade security with simple manual management.
`─────────────────────────────────────────────────`

## 📋 Prerequisites

### Hardware Requirements
- **CPU**: 2+ cores
- **RAM**: 4GB minimum (8GB recommended)
- **Storage**: 20GB+ free space
- **Network**: Stable internet connection

### Required Accounts & Services
- **Domain**: Registered domain managed by Cloudflare (free tier is fine)
- **Cloudflare Account**: For tunnel and DNS management
- **Home Server**: Ubuntu 20.04+ or similar Linux distribution

### 🌐 Example Domain Setup
This guide uses `hi-dil.com` as the example domain with these subdomains:
- **Central SSO**: `sso.poc.hi-dil.com` - Main authentication server
- **Tenant 1**: `tenant-one.poc.hi-dil.com` - First tenant application  
- **Tenant 2**: `tenant-two.poc.hi-dil.com` - Second tenant application

Replace these with your actual domain throughout the guide.

---

## 🚀 Part 1: Server Preparation

### Step 1.1: Install Docker
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add user to docker group
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Restart session
newgrp docker

# Verify installation
docker --version
docker-compose --version
```

### Step 1.2: Basic Security Setup
```bash
# Configure firewall
sudo ufw enable
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh

# Create deployment directory
mkdir -p ~/sso-deployment
cd ~/sso-deployment
```

## 🌐 Part 2: Cloudflare Tunnel Setup

### Step 2.1: Get Cloudflare API Token
1. Go to [Cloudflare API Tokens](https://dash.cloudflare.com/profile/api-tokens)
2. Click "Create Token" → "Custom token"
3. Configure permissions:
   - **Zone:Zone:Read** for your domain
   - **Zone:DNS:Edit** for your domain
   - **Account:Cloudflare Tunnel:Edit** for all accounts

### Step 2.2: Set Up Environment Variables
```bash
# Create Cloudflare configuration
cat > .env.cloudflare << 'EOF'
# Cloudflare Configuration
CLOUDFLARE_API_TOKEN=your_api_token_here
CLOUDFLARE_EMAIL=your_cloudflare_email@example.com
CLOUDFLARE_ZONE=hi-dil.com

# Domain Configuration
CENTRAL_SSO_DOMAIN=sso.poc.hi-dil.com
TENANT1_DOMAIN=tenant-one.poc.hi-dil.com
TENANT2_DOMAIN=tenant-two.poc.hi-dil.com

# Tunnel Configuration
TUNNEL_NAME=home-sso-tunnel
TUNNEL_UUID=will_be_generated_automatically
EOF

# Edit with your actual values
nano .env.cloudflare
```

### Step 2.3: Create Cloudflare Tunnel
```bash
# Download setup script
curl -o setup-tunnel.sh https://raw.githubusercontent.com/your-repo/sso-poc-claude3/main/scripts/setup-cloudflare-tunnel.sh
chmod +x setup-tunnel.sh

# Run tunnel setup (will create tunnel and DNS records)
./setup-tunnel.sh
```

## 📦 Part 3: SSO System Deployment

### Step 3.1: Clone and Configure
```bash
# Clone the repository
git clone https://github.com/your-repo/sso-poc-claude3.git
cd sso-poc-claude3

# Copy and configure environment
cp .env.docker .env

# Edit configuration for your domain
nano .env
```

### Step 3.2: Update Environment Configuration
Edit your `.env` file with these important changes:

```bash
# Production Environment
APP_ENV=production
APP_DEBUG=false

# Your Domains (matching Cloudflare setup)
CENTRAL_SSO_APP_URL=https://sso.poc.hi-dil.com
TENANT1_APP_URL=https://tenant-one.poc.hi-dil.com
TENANT2_APP_URL=https://tenant-two.poc.hi-dil.com

# External URLs for SSO
CENTRAL_SSO_URL=https://sso.poc.hi-dil.com

# Security Settings
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.poc.hi-dil.com

# Generate new secrets (see security section below)
CENTRAL_SSO_APP_KEY=base64:YOUR_GENERATED_KEY
TENANT1_APP_KEY=base64:YOUR_GENERATED_KEY  
TENANT2_APP_KEY=base64:YOUR_GENERATED_KEY
JWT_SECRET=YOUR_JWT_SECRET

# Additional Security Configuration
TENANT1_API_KEY=tenant1_GENERATED_API_KEY_HERE
TENANT2_API_KEY=tenant2_GENERATED_API_KEY_HERE
HMAC_SECRET=YOUR_GENERATED_HMAC_SECRET
```

**Complete Example Configuration:**
```bash
# Example showing all required variables for hi-dil.com domain
APP_ENV=production
APP_DEBUG=false

# Service URLs
CENTRAL_SSO_APP_URL=https://sso.poc.hi-dil.com
TENANT1_APP_URL=https://tenant-one.poc.hi-dil.com
TENANT2_APP_URL=https://tenant-two.poc.hi-dil.com
CENTRAL_SSO_URL=https://sso.poc.hi-dil.com
CENTRAL_SSO_API=http://central-sso:8000/api

# Security
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.poc.hi-dil.com
CENTRAL_SSO_APP_KEY=base64:FmhWkTVoDR3t2v05Xkcif7M4ODUrgqRlbdbUEVBS9XU=
TENANT1_APP_KEY=base64:HqiigYO+Xlti2S2EsiyLvWUULEoQtM5ss5d8EUe5rdA=
TENANT2_APP_KEY=base64:QLs20sZ3pWZOPf9ZpIFTmINE8ZD7VxgJ/DVO9CTjRIs=
JWT_SECRET=U6HY6rcTfmpHNYeCH83Y1GL9aoRzp4rwFWp7RMhAf5vYDjrjy58sVX9QyliHdT4y

# API Keys  
TENANT1_API_KEY=tenant1_0059abacdb1bd536fd605b520902f89658672011
TENANT2_API_KEY=tenant2_0010258f78e44ca7ad9de92a1a1c9307b278bbd7
HMAC_SECRET=your_generated_hmac_secret_here

# Database (using defaults)
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=sso_main
DB_USERNAME=sso_user
DB_PASSWORD=sso_password
```

### Step 3.3: Generate Security Keys
```bash
# Generate APP_KEY for each service
docker run --rm php:8.2-cli php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"

# Generate JWT secret
openssl rand -base64 32

# Generate API keys
openssl rand -hex 32

# Generate HMAC secret
openssl rand -base64 64
```

### Step 3.4: Deploy the System
```bash
# Start the services
docker-compose up -d

# Check service status
docker-compose ps

# IMPORTANT: Fix permissions for Laravel storage
# This prevents 500 errors and permission issues
sudo chown -R 33:33 central-sso/storage central-sso/bootstrap/cache
sudo chown -R 33:33 tenant1-app/storage tenant1-app/bootstrap/cache
sudo chown -R 33:33 tenant2-app/storage tenant2-app/bootstrap/cache
sudo chmod -R 775 central-sso/storage central-sso/bootstrap/cache
sudo chmod -R 775 tenant1-app/storage tenant1-app/bootstrap/cache
sudo chmod -R 775 tenant2-app/storage tenant2-app/bootstrap/cache

# IMPORTANT: Configure for HTTPS (required for production deployment)
# Add TrustProxies configuration to detect HTTPS behind Cloudflare
echo 'TRUSTED_PROXIES=*' >> .env
echo 'SESSION_SECURE_COOKIE=true' >> .env
echo 'SESSION_DOMAIN=.poc.hi-dil.com' >> .env

# Restart containers after configuration changes
docker-compose restart

# Run database migrations
docker exec central-sso php artisan migrate

# Seed initial data
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder

# View logs if needed
docker-compose logs -f
```

> ⚠️ **Critical Step**: The permission fix above is essential when using bind mounts. Without it, you'll get 500 errors because Laravel can't write to its storage directories.

## 🌉 Part 4: Connect to Cloudflare Tunnel

### Step 4.1: Create Tunnel Configuration
```bash
# Create tunnel config directory
mkdir -p cloudflare

# Create tunnel configuration
cat > cloudflare/config.yml << 'EOF'
tunnel: YOUR_TUNNEL_UUID
credentials-file: /etc/cloudflared/tunnel-credentials.json

ingress:
  # Central SSO
  - hostname: sso.poc.hi-dil.com
    service: http://central-sso:8000
  
  # Tenant Applications  
  - hostname: tenant-one.poc.hi-dil.com
    service: http://tenant1-app:8000
    
  - hostname: tenant-two.poc.hi-dil.com
    service: http://tenant2-app:8000
    
  # Catch-all rule (required)
  - service: http_status:404
EOF
```

### Step 4.2: Setup Bridge Network and Start Tunnel

The SSO system now uses a separate bridge network for Cloudflare tunnel communication:

```bash
# Create the bridge network
./scripts/setup-cloudflare-network.sh

# Copy tunnel configuration template
cp cloudflare/config.yml.example cloudflare/config.yml

# Edit with your tunnel details
nano cloudflare/config.yml
# Replace YOUR_TUNNEL_UUID_HERE with your actual tunnel UUID
# Update domain names to match your Cloudflare domains

# Start SSO services (they'll join the cloudflare-net network)
docker-compose up -d

# Start Cloudflare tunnel in separate container
docker-compose -f docker-compose.cloudflare.yml up -d
```

**Alternative: Single Docker Compose (if you prefer)**
```bash
# If you want everything in one file, add to docker-compose.yml:
cat >> docker-compose.yml << 'EOF'

  # Cloudflare Tunnel
  cloudflared:
    image: cloudflare/cloudflared:latest
    container_name: cloudflared
    command: tunnel --config /etc/cloudflared/config.yml run
    volumes:
      - ./cloudflare/config.yml:/etc/cloudflared/config.yml:ro
      - ./cloudflare/tunnel-credentials.json:/etc/cloudflared/tunnel-credentials.json:ro
    networks:
      - cloudflare-net
    restart: unless-stopped
EOF

# Restart with tunnel
docker-compose up -d
```

## ✅ Part 5: Verification and Testing

### Step 5.1: Test External Access
```bash
# Test each endpoint
curl -I https://sso.poc.hi-dil.com
curl -I https://tenant-one.poc.hi-dil.com
curl -I https://tenant-two.poc.hi-dil.com

# Check tunnel status
docker logs cloudflared
```

### Step 5.2: Test SSO Flow
1. Visit `https://sso.poc.hi-dil.com`
2. Login with test credentials:
   - Email: `superadmin@sso.com`
   - Password: `password`
3. Visit `https://tenant-one.poc.hi-dil.com/login`
4. Test both direct login and SSO redirect

## 🔄 Part 6: Maintenance and Updates

### Daily Operations
```bash
# Check system status
docker-compose ps

# View logs
docker-compose logs -f [service_name]

# Restart specific service
docker-compose restart [service_name]

# Full system restart
docker-compose down && docker-compose up -d
```

### Updating the System
```bash
# Navigate to deployment directory
cd ~/sso-deployment/sso-poc-claude3

# Pull latest changes
git pull origin main

# Rebuild and restart services
docker-compose build --no-cache
docker-compose down
docker-compose up -d

# Run any new migrations
docker exec central-sso php artisan migrate

# Clear application caches
docker exec central-sso php artisan cache:clear
docker exec tenant1-app php artisan cache:clear
docker exec tenant2-app php artisan cache:clear
```

### Backup and Recovery
```bash
# Create backup script
cat > backup.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="backups/$DATE"

mkdir -p $BACKUP_DIR

# Export database
docker exec sso-mariadb mysqldump -u root -proot_password --all-databases > $BACKUP_DIR/database.sql

# Backup configuration
cp .env $BACKUP_DIR/
cp docker-compose.yml $BACKUP_DIR/
cp -r cloudflare/ $BACKUP_DIR/

echo "Backup created in $BACKUP_DIR"
EOF

chmod +x backup.sh

# Run backup
./backup.sh
```

## 🛡️ Understanding TrustProxies Middleware

The SSO system includes pre-configured TrustProxies middleware that is **essential for HTTPS deployments** behind Cloudflare Tunnel.

### Why TrustProxies is Critical

When your application runs behind Cloudflare's proxy, Laravel cannot automatically detect that requests are using HTTPS. This causes several issues:

- **Session Cookies**: Secure cookies won't be set properly
- **CSRF Protection**: Token validation fails because Laravel thinks requests are HTTP
- **URL Generation**: Links and redirects may use HTTP instead of HTTPS

### How It Works

The TrustProxies middleware is automatically configured in all applications:

- **`central-sso/app/Http/Middleware/TrustProxies.php`**
- **`tenant1-app/app/Http/Middleware/TrustProxies.php`**
- **`tenant2-app/app/Http/Middleware/TrustProxies.php`**

Each middleware:
1. **Detects Cloudflare IPs**: Automatically trusts Cloudflare's IP ranges
2. **Reads Proxy Headers**: Processes `X-Forwarded-Proto`, `X-Forwarded-For`, etc.
3. **Environment Configurable**: Use `TRUSTED_PROXIES=*` for development

### Configuration Options

```env
# Development: Trust all proxies (convenient but less secure)
TRUSTED_PROXIES=*

# Production: Trust specific Cloudflare IP ranges (automatic)
# TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,...

# Required for HTTPS
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.poc.hi-dil.com
SESSION_SAME_SITE=lax
```

### Verification

Check that TrustProxies is working:

```bash
# Verify middleware configuration
./scripts/verify-trustproxies.sh

# Test HTTPS detection
docker exec central-sso curl -H "X-Forwarded-Proto: https" http://localhost:8000/health
```

**✅ Pre-configured**: The middleware is already installed and configured in all applications. You just need to set the environment variables for your deployment.

## 🔧 Troubleshooting

### Common Issues

**Tunnel Connection Issues:**
```bash
# Check tunnel status
docker logs cloudflared

# Verify DNS records
dig sso.poc.hi-dil.com
nslookup tenant-one.poc.hi-dil.com
```

**SSL Certificate Issues:**
- Cloudflare automatically provides SSL certificates
- Ensure DNS records are pointing to Cloudflare (orange cloud enabled)
- Wait 15-30 minutes for certificate propagation

**Application Not Loading (500 Error):**
```bash
# Check service health
docker-compose ps
docker-compose logs central-sso

# Check database connectivity
docker exec central-sso php artisan migrate:status

# Check for specific Laravel errors
docker exec central-sso tail -n 50 /var/www/html/storage/logs/laravel.log
```

**Permission Issues (Most Common Issue):**

If you see errors like "Permission denied" or "Operation not permitted":

```bash
# This is the most common issue with bind mounts
# Fix from the HOST machine (not inside Docker):

# Navigate to your project directory
cd ~/sso-deployment/sso-poc-claude3

# Fix ownership for www-data (UID 33)
sudo chown -R 33:33 central-sso/storage central-sso/bootstrap/cache
sudo chown -R 33:33 tenant1-app/storage tenant1-app/bootstrap/cache  
sudo chown -R 33:33 tenant2-app/storage tenant2-app/bootstrap/cache

# Set proper permissions
sudo chmod -R 775 central-sso/storage central-sso/bootstrap/cache
sudo chmod -R 775 tenant1-app/storage tenant1-app/bootstrap/cache
sudo chmod -R 775 tenant2-app/storage tenant2-app/bootstrap/cache

# Restart containers
docker-compose restart
```

**Why This Happens:**
- Docker bind mounts preserve host file ownership
- Laravel needs www-data (UID 33) to write to storage directories
- Your host files are owned by your user, not www-data

**Alternative: Run Quick Fix Script**
```bash
# Download and run the fix script
curl -o fix-permissions.sh https://raw.githubusercontent.com/your-repo/sso-poc-claude3/main/scripts/fix-permissions.sh
chmod +x fix-permissions.sh
./fix-permissions.sh
```

**SSL/TLS Connection Errors (ERR_SSL_VERSION_OR_CIPHER_MISMATCH):**

If you get SSL connection errors when trying to access your domain:

```bash
# Run comprehensive SSL troubleshooting
./scripts/troubleshoot-ssl.sh

# Common fixes:
# 1. Check if Cloudflare tunnel is running
docker logs cloudflared-sso

# 2. Verify DNS is pointing to Cloudflare
dig sso.poc.hi-dil.com

# 3. Check Cloudflare SSL settings
# - Go to SSL/TLS → Overview in Cloudflare dashboard
# - Set to "Full" or "Full (strict)" mode
# - Ensure DNS records are "Proxied" (orange cloud)

# 4. Restart tunnel if needed
docker-compose -f docker-compose.cloudflare.yml restart
```

**419 Page Expired (CSRF Token Errors):**

If you get "Page Expired" errors when submitting forms:

```bash
# Run automatic CSRF fix
./scripts/fix-https-csrf.sh

# Manual fix if needed:
# 1. Update session configuration for HTTPS
echo 'SESSION_SECURE_COOKIE=true' >> .env
echo 'SESSION_DOMAIN=.poc.hi-dil.com' >> .env
echo 'SESSION_SAME_SITE=lax' >> .env
echo 'TRUSTED_PROXIES=*' >> .env

# 2. Restart containers
docker-compose restart

# 3. Clear caches
docker exec central-sso php artisan config:clear
docker exec central-sso php artisan cache:clear
```

**Why CSRF Errors Happen:**
- Laravel's CSRF protection fails when it can't detect HTTPS properly
- Session cookies need secure flag enabled for HTTPS
- TrustProxies middleware is required behind Cloudflare
- SameSite cookie policy can block CSRF tokens

**SSL Configuration Checklist:**
- [ ] Cloudflare tunnel is running (`docker ps | grep cloudflared`)
- [ ] DNS records point to Cloudflare with "Proxied" status
- [ ] SSL/TLS mode is "Full" or "Full (strict)" in Cloudflare
- [ ] Tunnel configuration file exists (`cloudflare/config.yml`)
- [ ] Tunnel credentials are valid (`cloudflare/tunnel-credentials.json`)

## 🔒 Security Considerations

### Production Security Checklist
- [ ] All APP_KEY values are unique and properly generated
- [ ] JWT_SECRET is strong and unique
- [ ] Database passwords are changed from defaults
- [ ] HMAC secrets are properly configured
- [ ] APP_DEBUG is set to false
- [ ] APP_ENV is set to production
- [ ] Regular backups are scheduled
- [ ] System updates are applied regularly

### Network Security
- The system is only accessible through Cloudflare Tunnel
- No direct ports are exposed on your home router
- All traffic is encrypted via Cloudflare's SSL certificates
- Rate limiting is configured in the application

## 📚 Next Steps

After successful deployment:

1. **Set up monitoring** - Consider adding basic monitoring for system health
2. **Configure backups** - Set up automated database and configuration backups
3. **Add tenants** - Use the admin interface to add new tenant applications
4. **User management** - Create additional users and configure permissions
5. **Review logs** - Set up log rotation and monitoring for security events

For more advanced features, see:
- [User Management Guide](user-management.md)
- [Tenant Management Guide](tenant-management.md)
- [Security Guide](security.md)
- [Full CI/CD Deployment](home-server-deployment.md) (for automated updates)