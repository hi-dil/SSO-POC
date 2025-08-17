# Complete Home Server Deployment Guide

**Deploy your multi-tenant SSO system on a home server with Cloudflare Tunnel and automated CI/CD**

## 🏠 Overview

This guide provides complete step-by-step instructions for deploying the multi-tenant SSO system on your home server using:

- **Cloudflare Tunnel**: Secure external access without port forwarding
- **GitHub Actions**: Automated CI/CD pipeline for deployments
- **Docker Architecture**: Consistent containerized environment
- **Production Security**: SSL certificates, secrets management, and monitoring

### ★ Insight ─────────────────────────────────────
**Home Server Deployment Benefits:**
- **No Port Forwarding**: Cloudflare Tunnel provides secure access without exposing your router
- **Free SSL Certificates**: Automatic SSL via Cloudflare with enterprise-grade security
- **Automated Deployments**: GitHub Actions handles testing, building, and deployment automatically
─────────────────────────────────────────────────

## 📋 Prerequisites

### Hardware Requirements
- **CPU**: 2+ cores (4+ recommended)
- **RAM**: 4GB minimum (8GB recommended for monitoring)
- **Storage**: 50GB+ SSD space
- **Network**: Stable internet connection (10Mbps+ upload recommended)

### Software Requirements
- **Ubuntu 20.04+ LTS** (or compatible Linux distribution)
- **Domain Name**: Registered domain managed by Cloudflare
- **GitHub Account**: For repository hosting and CI/CD
- **Cloudflare Account**: Free tier is sufficient

---

## 🔧 Part 1: Home Server Preparation

### Step 1.1: Install Docker and Docker Compose

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add your user to docker group
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Restart session to apply group changes
newgrp docker

# Verify installation
docker --version
docker-compose --version
```

### Step 1.2: Configure System Security

```bash
# Configure UFW firewall (allow SSH and Docker)
sudo ufw enable
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 2376/tcp  # Docker daemon (if using remote access)

# Enable automatic security updates
sudo apt install unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades

# Create deployment user (optional but recommended)
sudo adduser deploy
sudo usermod -aG docker deploy
sudo usermod -aG sudo deploy
```

### Step 1.3: Generate SSH Keys for GitHub Actions

```bash
# Generate SSH key pair for GitHub Actions deployment
ssh-keygen -t ed25519 -f ~/.ssh/github_actions_deploy -N ""

# Add public key to authorized_keys for deployment user
mkdir -p ~/.ssh
cat ~/.ssh/github_actions_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh

# Display private key to copy to GitHub secrets
echo "Copy this private key to GitHub secret SSH_PRIVATE_KEY:"
cat ~/.ssh/github_actions_deploy
```

---

## 🌐 Part 2: Cloudflare Setup

### Step 2.1: Domain and Account Setup

1. **Add Domain to Cloudflare**
   - Sign up at [cloudflare.com](https://cloudflare.com)
   - Add your domain (e.g., `hi-dil.com`)
   - Update nameservers at your domain registrar
   - Wait for activation (usually 5-30 minutes)

2. **Create API Token**
   - Go to [API Tokens](https://dash.cloudflare.com/profile/api-tokens)
   - Click "Create Token"
   - Use "Custom token" template

   **Token Configuration:**
   ```
   Permissions:
   - Zone:Edit (for DNS management)
   - Zone:Read (for zone information access)
   
   Zone Resources:
   - Include: Specific zone - hi-dil.com
   
   Account Resources:
   - Leave default (not required for basic tunnel setup)
   ```

3. **Save Token Information**
   ```bash
   # Note these values for later configuration
   CLOUDFLARE_API_TOKEN="your-generated-token"
   CLOUDFLARE_EMAIL="your-cloudflare-email@example.com"
   CLOUDFLARE_ZONE_ID="your-zone-id"  # Found in domain overview
   ```

### Step 2.2: Create Cloudflare Tunnel

#### Method A: Manual Setup (Recommended)

1. **Go to Cloudflare Zero Trust Dashboard**
   - Visit: https://one.dash.cloudflare.com/
   - Navigate to **Access** → **Tunnels**
   - Click **Create a tunnel**

2. **Create Tunnel**
   - Choose **Cloudflared** as connector type
   - Enter tunnel name: `sso-home-server`
   - Click **Save tunnel**

3. **Download Tunnel Credentials**
   - Copy the tunnel token shown (starts with `eyJ...`)
   - Note the tunnel ID (shown in the dashboard)
   - Click **Next**

4. **Configure Routes** (we'll do this later)
   - Skip the route configuration for now
   - Click **Save tunnel**

5. **Save Tunnel Information**
   ```bash
   # Create project directory
   mkdir -p ~/sso-production/cloudflare
   cd ~/sso-production
   
   # Save tunnel information (replace with your values)
   echo "TUNNEL_TOKEN=eyJhIjoiYWJjZGVmZ..." > cloudflare/tunnel-token.txt
   echo "TUNNEL_ID=your-tunnel-id-here" > cloudflare/tunnel-id.txt
   ```

#### Method B: CLI Setup (Alternative)

```bash
# Create project directory
mkdir -p ~/sso-production/cloudflare
cd ~/sso-production

# Create tunnel using Docker with API token authentication
docker run --rm \
    -v "$(pwd)/cloudflare:/home/nonroot/.cloudflared" \
    -e CLOUDFLARE_API_TOKEN="your-api-token-here" \
    cloudflare/cloudflared:latest \
    tunnel create sso-home-server

# Verify tunnel creation
ls -la cloudflare/
# Should show: tunnel-credentials.json and config files
```

### Step 2.3: Configure Tunnel

#### For Manual Setup (Method A):

```bash
# Get your tunnel ID
TUNNEL_ID=$(cat cloudflare/tunnel-id.txt)

# Create tunnel configuration
cat > cloudflare/config.yml << EOF
tunnel: ${TUNNEL_ID}
# credentials-file not needed when using tunnel token

ingress:
  # Central SSO Server
  - hostname: sso.poc.hi-dil.com
    service: http://central-sso:8000
    originRequest:
      httpHostHeader: sso.poc.hi-dil.com
      
  # Tenant 1 Application
  - hostname: tenant-one.poc.hi-dil.com
    service: http://tenant1-app:8000
    originRequest:
      httpHostHeader: tenant-one.poc.hi-dil.com
      
  # Tenant 2 Application
  - hostname: tenant-two.poc.hi-dil.com
    service: http://tenant2-app:8000
    originRequest:
      httpHostHeader: tenant-two.poc.hi-dil.com
      
  # Catch-all rule (required)
  - service: http_status:404

# Metrics configuration
metrics: 0.0.0.0:9090
EOF
```

#### For CLI Setup (Method B):

```bash
# Create tunnel configuration using credentials file
cat > cloudflare/config.yml << 'EOF'
tunnel: sso-home-server
credentials-file: /etc/cloudflared/tunnel-credentials.json

ingress:
  # Central SSO Server
  - hostname: sso.poc.hi-dil.com
    service: http://central-sso:8000
    originRequest:
      httpHostHeader: sso.poc.hi-dil.com
      
  # Tenant 1 Application
  - hostname: tenant-one.poc.hi-dil.com
    service: http://tenant1-app:8000
    originRequest:
      httpHostHeader: tenant-one.poc.hi-dil.com
      
  # Tenant 2 Application
  - hostname: tenant-two.poc.hi-dil.com
    service: http://tenant2-app:8000
    originRequest:
      httpHostHeader: tenant-two.poc.hi-dil.com
      
  # Catch-all rule (required)
  - service: http_status:404

# Metrics configuration
metrics: 0.0.0.0:9090
EOF
```

### Step 2.4: Create DNS Records

```bash
# Create DNS records via API (replace YOUR_ZONE_ID and YOUR_API_TOKEN)
curl -X POST "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/dns_records" \
     -H "Authorization: Bearer YOUR_API_TOKEN" \
     -H "Content-Type: application/json" \
     --data '{
       "type": "CNAME",
       "name": "sso.poc",
       "content": "TUNNEL_ID.cfargotunnel.com",
       "ttl": 1
     }'

curl -X POST "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/dns_records" \
     -H "Authorization: Bearer YOUR_API_TOKEN" \
     -H "Content-Type: application/json" \
     --data '{
       "type": "CNAME",
       "name": "tenant-one.poc",
       "content": "TUNNEL_ID.cfargotunnel.com",
       "ttl": 1
     }'

curl -X POST "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/dns_records" \
     -H "Authorization: Bearer YOUR_API_TOKEN" \
     -H "Content-Type: application/json" \
     --data '{
       "type": "CNAME",
       "name": "tenant-two.poc",
       "content": "TUNNEL_ID.cfargotunnel.com",
       "ttl": 1
     }'

# Find your TUNNEL_ID in the tunnel credentials file:
grep -o '"TunnelID":"[^"]*' cloudflare/tunnel-credentials.json | cut -d'"' -f4
```

---

## 📦 Part 3: Repository Setup

### Step 3.1: Fork and Clone Repository

```bash
# Clone your forked repository
git clone https://github.com/YOUR_USERNAME/sso-poc-claude3.git
cd sso-poc-claude3

# Create production branch
git checkout -b production
git push -u origin production
```

### Step 3.2: Configure GitHub Secrets

Go to your GitHub repository → Settings → Secrets and variables → Actions

**Add these Repository Secrets:**

#### Server Access
```
SSH_PRIVATE_KEY          # Private key generated in Step 1.3
SERVER_HOST              # Your home server IP or hostname
SERVER_USER              # Deployment user (e.g., deploy)
```

#### Cloudflare Configuration
```
CLOUDFLARE_API_TOKEN     # API token from Step 2.1
CLOUDFLARE_EMAIL         # Your Cloudflare email
CLOUDFLARE_ZONE_ID       # Zone ID from Cloudflare dashboard
```

#### Application Secrets
```bash
# Generate secure secrets using openssl
JWT_SECRET=$(openssl rand -base64 32)
REDIS_PASSWORD=$(openssl rand -base64 24)
DB_PASSWORD=$(openssl rand -base64 24)
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 24)

# Tenant API keys (32+ characters each)
TENANT1_API_KEY="tenant1_$(openssl rand -hex 16)"
TENANT2_API_KEY="tenant2_$(openssl rand -hex 16)"

# HMAC secrets (64+ characters each)
TENANT1_HMAC_SECRET=$(openssl rand -hex 32)
TENANT2_HMAC_SECRET=$(openssl rand -hex 32)
```

**Copy these generated values to GitHub Secrets:**
```
JWT_SECRET
REDIS_PASSWORD
DB_PASSWORD
MYSQL_ROOT_PASSWORD
TENANT1_API_KEY
TENANT2_API_KEY
TENANT1_HMAC_SECRET
TENANT2_HMAC_SECRET
```

### Step 3.3: Create Environment Configurations

```bash
# Create production environment file
cat > .env.production << EOF
# Application Configuration
APP_NAME="Multi-Tenant SSO"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sso.poc.hi-dil.com
APP_KEY=base64:$(openssl rand -base64 32)

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=sso_main
DB_USERNAME=sso_user
DB_PASSWORD=\${DB_PASSWORD}

# JWT Configuration
JWT_SECRET=\${JWT_SECRET}
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Redis Configuration
REDIS_HOST=redis
REDIS_PASSWORD=\${REDIS_PASSWORD}
REDIS_PORT=6379

# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_DOMAIN=.poc.hi-dil.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Cache Configuration
CACHE_DRIVER=redis

# Queue Configuration
QUEUE_CONNECTION=redis

# Mail Configuration (optional)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@hi-dil.com
MAIL_FROM_NAME="Multi-Tenant SSO"

# Cloudflare Configuration
CLOUDFLARE_API_TOKEN=\${CLOUDFLARE_API_TOKEN}
CLOUDFLARE_EMAIL=\${CLOUDFLARE_EMAIL}

# Security Configuration
BCRYPT_ROUNDS=12
PASSWORD_MIN_LENGTH=8
THROTTLE_LOGIN_MAX_ATTEMPTS=5
THROTTLE_LOGIN_DECAY_MINUTES=5

# Monitoring (optional)
TELESCOPE_ENABLED=false
LOG_CHANNEL=daily
LOG_LEVEL=warning
EOF
```

---

## 🚀 Part 4: CI/CD Pipeline Configuration

### Step 4.1: Update GitHub Actions Workflow

The existing `.github/workflows/ci-cd-pipeline.yml` already includes home server deployment. Key features:

- **Automated Testing**: Unit, feature, and integration tests
- **Security Scanning**: Vulnerability and code quality checks
- **Docker Image Building**: Multi-platform image builds
- **Blue-Green Deployment**: Zero-downtime deployments
- **Health Checks**: Automatic service validation

### Step 4.2: Trigger Initial Deployment

```bash
# Create a deployment tag to trigger production deployment
git tag -a v1.0.0 -m "Initial production deployment"
git push origin v1.0.0

# Or push to main branch (if configured for auto-deployment)
git checkout main
git merge production
git push origin main
```

### Step 4.3: Monitor Deployment

1. **GitHub Actions**: Watch deployment progress in your repository's Actions tab
2. **Deployment Logs**: Check logs for each deployment stage
3. **Manual Approval**: Approve production deployment when prompted

---

## 🏗️ Part 5: Initial Server Setup

### Step 5.1: Prepare Server for Deployment

```bash
# Create application directory
sudo mkdir -p /opt/sso-production
sudo chown $USER:$USER /opt/sso-production
cd /opt/sso-production

# Clone repository
git clone https://github.com/YOUR_USERNAME/sso-poc-claude3.git .
git checkout production

# Create required directories
mkdir -p logs/{central-sso,tenant1-app,tenant2-app,mariadb,redis,cloudflared}
mkdir -p data/{mariadb,redis}
mkdir -p backups

# Set proper permissions
chmod -R 755 logs data backups
```

### Step 5.2: Create Production Docker Compose

```bash
# Create production docker-compose file
cat > docker-compose.production.yml << 'EOF'
version: '3.8'

networks:
  sso-network:
    driver: bridge

volumes:
  mariadb-data:
  redis-data:

services:
  # MariaDB Database
  mariadb:
    image: mariadb:10.11
    container_name: sso-mariadb
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: sso_main
      MYSQL_USER: sso_user
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mariadb-data:/var/lib/mysql
      - ./infrastructure/database/mariadb/init:/docker-entrypoint-initdb.d:ro
      - ./logs/mariadb:/var/log/mysql
    networks:
      - sso-network
    restart: unless-stopped
    command: >
      --log-error=/var/log/mysql/error.log
      --general-log=1
      --general-log-file=/var/log/mysql/general.log
      --slow-query-log=1
      --slow-query-log-file=/var/log/mysql/slow.log
      --long-query-time=2

  # Redis Cache
  redis:
    image: redis:7-alpine
    container_name: sso-redis
    command: redis-server --requirepass ${REDIS_PASSWORD} --appendonly yes
    volumes:
      - redis-data:/data
      - ./logs/redis:/var/log/redis
    networks:
      - sso-network
    restart: unless-stopped

  # Central SSO Application
  central-sso:
    build:
      context: ./central-sso
      dockerfile: Dockerfile.prod
    container_name: sso-central-sso
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://sso.poc.hi-dil.com
      - DB_HOST=mariadb
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=redis
      - REDIS_PASSWORD=${REDIS_PASSWORD}
      - JWT_SECRET=${JWT_SECRET}
      - SESSION_DOMAIN=.poc.hi-dil.com
      - SESSION_SECURE_COOKIE=true
    volumes:
      - ./logs/central-sso:/var/www/html/storage/logs
    networks:
      - sso-network
    depends_on:
      - mariadb
      - redis
    restart: unless-stopped

  # Tenant 1 Application
  tenant1-app:
    build:
      context: ./tenant1-app
      dockerfile: Dockerfile.prod
    container_name: sso-tenant1-app
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://tenant-one.poc.hi-dil.com
      - CENTRAL_SSO_URL=http://central-sso:8000
      - TENANT_SLUG=tenant1
      - TENANT_API_KEY=${TENANT1_API_KEY}
      - HMAC_SECRET=${TENANT1_HMAC_SECRET}
      - DB_HOST=mariadb
      - DB_DATABASE=tenant1_db
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=redis
      - REDIS_PASSWORD=${REDIS_PASSWORD}
      - JWT_SECRET=${JWT_SECRET}
      - SESSION_DOMAIN=.poc.hi-dil.com
    volumes:
      - ./logs/tenant1-app:/var/www/html/storage/logs
    networks:
      - sso-network
    depends_on:
      - mariadb
      - redis
      - central-sso
    restart: unless-stopped

  # Tenant 2 Application
  tenant2-app:
    build:
      context: ./tenant2-app
      dockerfile: Dockerfile.prod
    container_name: sso-tenant2-app
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://tenant-two.poc.hi-dil.com
      - CENTRAL_SSO_URL=http://central-sso:8000
      - TENANT_SLUG=tenant2
      - TENANT_API_KEY=${TENANT2_API_KEY}
      - HMAC_SECRET=${TENANT2_HMAC_SECRET}
      - DB_HOST=mariadb
      - DB_DATABASE=tenant2_db
      - DB_PASSWORD=${DB_PASSWORD}
      - REDIS_HOST=redis
      - REDIS_PASSWORD=${REDIS_PASSWORD}
      - JWT_SECRET=${JWT_SECRET}
      - SESSION_DOMAIN=.poc.hi-dil.com
    volumes:
      - ./logs/tenant2-app:/var/www/html/storage/logs
    networks:
      - sso-network
    depends_on:
      - mariadb
      - redis
      - central-sso
    restart: unless-stopped

  # Cloudflare Tunnel
  cloudflared:
    image: cloudflare/cloudflared:latest
    container_name: sso-cloudflared
    # For Manual Setup (Method A): Use tunnel token
    command: tunnel --token ${TUNNEL_TOKEN} run
    # For CLI Setup (Method B): Use config file
    # command: tunnel --config /etc/cloudflared/config.yml run
    volumes:
      - ./cloudflare/config.yml:/etc/cloudflared/config.yml:ro
      # Only needed for CLI setup (Method B):
      # - ./cloudflare/tunnel-credentials.json:/etc/cloudflared/tunnel-credentials.json:ro
      - ./logs/cloudflared:/var/log/cloudflared
    networks:
      - sso-network
    depends_on:
      - central-sso
      - tenant1-app
      - tenant2-app
    restart: unless-stopped
    environment:
      - TUNNEL_METRICS=0.0.0.0:9090
    ports:
      - "9090:9090"  # Metrics endpoint
EOF
```

### Step 5.3: Deploy Application Stack

```bash
# Copy Cloudflare configuration from setup
cp ~/sso-production/cloudflare/* ./cloudflare/

# Build and start all services
docker-compose -f docker-compose.production.yml up -d --build

# Check service status
docker-compose -f docker-compose.production.yml ps

# View logs
docker-compose -f docker-compose.production.yml logs -f
```

### Step 5.4: Initialize Database

```bash
# Wait for MariaDB to start (check logs)
docker-compose -f docker-compose.production.yml logs mariadb

# Run migrations
docker-compose -f docker-compose.production.yml exec central-sso php artisan migrate --force

# Seed initial data
docker-compose -f docker-compose.production.yml exec central-sso php artisan db:seed --force --class=AddTestUsersSeeder

# Create tenant databases
docker-compose -f docker-compose.production.yml exec central-sso php artisan tenancy:migrate --tenants=tenant1,tenant2 --force
```

---

## ✅ Part 6: Testing and Verification

### Step 6.1: Test Cloudflare Tunnel

```bash
# Test tunnel connectivity
curl -I https://sso.poc.hi-dil.com
curl -I https://tenant-one.poc.hi-dil.com
curl -I https://tenant-two.poc.hi-dil.com

# Check tunnel metrics
curl http://localhost:9090/metrics
```

### Step 6.2: Test SSO Authentication

1. **Access Central SSO**
   - Open: https://sso.poc.hi-dil.com
   - Login with: `superadmin@sso.com` / `password`

2. **Test Tenant 1**
   - Open: https://tenant-one.poc.hi-dil.com
   - Try direct login with SSO credentials
   - Test "Login with SSO" button

3. **Test Tenant 2**
   - Open: https://tenant-two.poc.hi-dil.com
   - Verify cross-tenant access works

### Step 6.3: Test API Endpoints

```bash
# Test API authentication
curl -X POST https://sso.poc.hi-dil.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "superadmin@sso.com",
    "password": "password",
    "tenant_slug": "tenant1"
  }'

# Test health endpoints
curl https://sso.poc.hi-dil.com/health
curl https://tenant-one.poc.hi-dil.com/health
curl https://tenant-two.poc.hi-dil.com/health
```

---

## 🔧 Part 7: Production Optimizations

### Step 7.1: SSL and Security

```bash
# Verify SSL certificates
echo | openssl s_client -connect sso.poc.hi-dil.com:443 -servername sso.poc.hi-dil.com | openssl x509 -noout -dates

# Check security headers
curl -I https://sso.poc.hi-dil.com

# Enable HSTS and security headers in Cloudflare
# Go to Cloudflare Dashboard → SSL/TLS → Edge Certificates → Enable HSTS
```

### Step 7.2: Performance Tuning

```bash
# Enable Redis persistence for production
cat >> docker-compose.production.yml << 'EOF'
  redis:
    command: redis-server --requirepass ${REDIS_PASSWORD} --appendonly yes --save 900 1 --save 300 10 --save 60 10000
EOF

# Optimize MariaDB for production
cat > mariadb-production.cnf << 'EOF'
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 32M
query_cache_type = 1
EOF

# Add to docker-compose.yml MariaDB service:
#   volumes:
#     - ./mariadb-production.cnf:/etc/mysql/conf.d/production.cnf:ro
```

### Step 7.3: Monitoring Setup

```bash
# Install monitoring tools (optional)
cat >> docker-compose.production.yml << 'EOF'
  # Nginx for metrics collection (optional)
  nginx-exporter:
    image: nginx/nginx-prometheus-exporter
    container_name: nginx-exporter
    command: -nginx.scrape-uri=http://nginx/nginx_status
    ports:
      - "9113:9113"
    networks:
      - sso-network

  # Node exporter for system metrics
  node-exporter:
    image: prom/node-exporter
    container_name: node-exporter
    ports:
      - "9100:9100"
    networks:
      - sso-network
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.sysfs=/host/sys'
      - '--collector.filesystem.ignored-mount-points'
      - '^/(sys|proc|dev|host|etc|rootfs/var/lib/docker/containers|rootfs/var/lib/docker/overlay2|rootfs/run/docker/netns|rootfs/var/lib/docker/aufs)($$|/)'
EOF
```

---

## 🔄 Part 8: Automated Updates via CI/CD

### Step 8.1: Deployment Workflow

The CI/CD pipeline automatically:

1. **Runs Tests**: Unit, feature, and integration tests
2. **Security Scan**: Checks for vulnerabilities
3. **Builds Images**: Creates production Docker images
4. **Deploys to Server**: Updates services with zero downtime
5. **Health Checks**: Verifies deployment success

### Step 8.2: Manual Deployment Trigger

```bash
# Deploy specific version
git tag -a v1.0.1 -m "Bug fixes and improvements"
git push origin v1.0.1

# Deploy from main branch
git checkout main
git pull origin main
git push origin main
```

### Step 8.3: Monitor Deployments

- **GitHub Actions**: Monitor progress in repository Actions tab
- **Server Logs**: `docker-compose -f docker-compose.production.yml logs -f`
- **Application Health**: Visit https://sso.poc.hi-dil.com/health

---

## 📊 Part 9: Backup and Maintenance

### Step 9.1: Database Backups

```bash
# Create backup script
cat > backup-database.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/sso-production/backups"

# Create backup
docker-compose -f docker-compose.production.yml exec -T mariadb \
  mysqldump -u root -p${MYSQL_ROOT_PASSWORD} --all-databases > \
  ${BACKUP_DIR}/sso_backup_${DATE}.sql

# Compress backup
gzip ${BACKUP_DIR}/sso_backup_${DATE}.sql

# Keep only last 7 days of backups
find ${BACKUP_DIR} -name "sso_backup_*.sql.gz" -mtime +7 -delete

echo "Backup completed: sso_backup_${DATE}.sql.gz"
EOF

chmod +x backup-database.sh

# Add to crontab for daily backups
echo "0 2 * * * /opt/sso-production/backup-database.sh" | crontab -
```

### Step 9.2: Log Management

```bash
# Setup log rotation
cat > /etc/logrotate.d/sso-production << 'EOF'
/opt/sso-production/logs/*/*.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
}
EOF

# Test log rotation
sudo logrotate -d /etc/logrotate.d/sso-production
```

### Step 9.3: System Updates

```bash
# Create update script
cat > update-system.sh << 'EOF'
#!/bin/bash
set -e

echo "Starting system update..."

# Update system packages
sudo apt update && sudo apt upgrade -y

# Update Docker images
docker-compose -f docker-compose.production.yml pull

# Restart services if needed
docker-compose -f docker-compose.production.yml up -d

# Clean up old images
docker image prune -f

echo "System update completed"
EOF

chmod +x update-system.sh

# Schedule monthly updates
echo "0 3 1 * * /opt/sso-production/update-system.sh" | crontab -
```

---

## 🔍 Part 10: Troubleshooting

### Common Issues and Solutions

#### Issue: Services Not Starting
```bash
# Check logs
docker-compose -f docker-compose.production.yml logs

# Check disk space
df -h

# Check memory usage
free -h

# Restart specific service
docker-compose -f docker-compose.production.yml restart central-sso
```

#### Issue: Cloudflare Tunnel Not Working
```bash
# Check tunnel status
docker-compose -f docker-compose.production.yml logs cloudflared

# Verify tunnel configuration
docker-compose -f docker-compose.production.yml exec cloudflared \
  cloudflared tunnel info sso-home-server

# Test internal connectivity
docker-compose -f docker-compose.production.yml exec cloudflared \
  curl http://central-sso:8000/health
```

#### Issue: Database Connection Problems
```bash
# Check MariaDB logs
docker-compose -f docker-compose.production.yml logs mariadb

# Test database connection
docker-compose -f docker-compose.production.yml exec central-sso \
  php artisan tinker --execute="DB::connection()->getPdo();"

# Reset database connection
docker-compose -f docker-compose.production.yml restart mariadb central-sso
```

#### Issue: SSL Certificate Problems
```bash
# Check certificate status
echo | openssl s_client -connect sso.poc.hi-dil.com:443 -servername sso.poc.hi-dil.com

# Verify DNS records
dig sso.poc.hi-dil.com
nslookup tenant-one.poc.hi-dil.com

# Force SSL renewal in Cloudflare (dashboard)
```

### Performance Monitoring

```bash
# Monitor resource usage
docker stats

# Check application metrics
curl http://localhost:9090/metrics

# Monitor logs in real-time
docker-compose -f docker-compose.production.yml logs -f --tail=100
```

---

## 🎯 Success Criteria

Your deployment is successful when:

✅ **All services are running**: `docker-compose ps` shows all services as "Up"  
✅ **SSL certificates work**: All domains accessible via HTTPS  
✅ **Authentication flows work**: Can login via SSO and direct methods  
✅ **Cross-tenant access works**: Same user can access multiple tenants  
✅ **API endpoints respond**: All health checks pass  
✅ **Backups are working**: Daily database backups created  
✅ **CI/CD pipeline works**: Deployments complete successfully  
✅ **Monitoring is active**: Can view metrics and logs  

## 🎉 Conclusion

You now have a production-ready multi-tenant SSO system running on your home server with:

- **Secure External Access**: Cloudflare Tunnel with automatic SSL
- **Automated Deployments**: GitHub Actions CI/CD pipeline
- **High Availability**: Docker containers with restart policies
- **Comprehensive Monitoring**: Logs, metrics, and health checks
- **Automated Backups**: Daily database backups with retention
- **Security Best Practices**: Encrypted secrets, secure sessions, HTTPS everywhere

Your system is ready for production use and will automatically update itself through the CI/CD pipeline whenever you push changes to your repository.

---

## 🔗 Related Documentation

- **[CI/CD Pipeline Guide](../deployment/cicd-pipeline.md)** - Detailed CI/CD documentation
- **[Cloudflare Tunnel Setup](../deployment/cloudflare-tunnel-deployment.md)** - Alternative setup methods
- **[Security Guide](security.md)** - Additional security hardening
- **[Troubleshooting Guide](../reference/troubleshooting.md)** - Comprehensive troubleshooting