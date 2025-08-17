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
CLOUDFLARE_ZONE=yourdomain.com

# Domain Configuration
CENTRAL_SSO_DOMAIN=sso.yourdomain.com
TENANT1_DOMAIN=app1.yourdomain.com
TENANT2_DOMAIN=app2.yourdomain.com

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
CENTRAL_SSO_APP_URL=https://sso.yourdomain.com
TENANT1_APP_URL=https://app1.yourdomain.com
TENANT2_APP_URL=https://app2.yourdomain.com

# External URLs for SSO
CENTRAL_SSO_URL=https://sso.yourdomain.com

# Security Settings
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.yourdomain.com

# Generate new secrets (see security section below)
CENTRAL_SSO_APP_KEY=base64:YOUR_GENERATED_KEY
TENANT1_APP_KEY=base64:YOUR_GENERATED_KEY  
TENANT2_APP_KEY=base64:YOUR_GENERATED_KEY
JWT_SECRET=YOUR_JWT_SECRET
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

# Run database migrations
docker exec central-sso php artisan migrate

# Seed initial data
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder

# View logs if needed
docker-compose logs -f
```

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
  - hostname: sso.yourdomain.com
    service: http://central-sso:8000
  
  # Tenant Applications  
  - hostname: app1.yourdomain.com
    service: http://tenant1-app:8000
    
  - hostname: app2.yourdomain.com
    service: http://tenant2-app:8000
    
  # Catch-all rule (required)
  - service: http_status:404
EOF
```

### Step 4.2: Start Cloudflare Tunnel
```bash
# Add tunnel service to docker-compose
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
      - sso-network
    depends_on:
      - central-sso
      - tenant1-app
      - tenant2-app
EOF

# Restart with tunnel
docker-compose up -d
```

## ✅ Part 5: Verification and Testing

### Step 5.1: Test External Access
```bash
# Test each endpoint
curl -I https://sso.yourdomain.com
curl -I https://app1.yourdomain.com
curl -I https://app2.yourdomain.com

# Check tunnel status
docker logs cloudflared
```

### Step 5.2: Test SSO Flow
1. Visit `https://sso.yourdomain.com`
2. Login with test credentials:
   - Email: `superadmin@sso.com`
   - Password: `password`
3. Visit `https://app1.yourdomain.com/login`
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

## 🔧 Troubleshooting

### Common Issues

**Tunnel Connection Issues:**
```bash
# Check tunnel status
docker logs cloudflared

# Verify DNS records
dig sso.yourdomain.com
nslookup app1.yourdomain.com
```

**SSL Certificate Issues:**
- Cloudflare automatically provides SSL certificates
- Ensure DNS records are pointing to Cloudflare (orange cloud enabled)
- Wait 15-30 minutes for certificate propagation

**Application Not Loading:**
```bash
# Check service health
docker-compose ps
docker-compose logs central-sso

# Check database connectivity
docker exec central-sso php artisan migrate:status
```

**Permission Issues:**
```bash
# Fix Docker permissions
sudo chmod 666 /var/run/docker.sock

# Fix application permissions
docker exec central-sso chown -R www-data:www-data /var/www/html/storage
```

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