# Cloudflare Tunnel Deployment Guide

## Overview

This guide provides a complete deployment strategy for the multi-tenant SSO system using Cloudflare Tunnel (formerly Argo Tunnel) with the Docker Compose setup. Cloudflare Tunnel creates secure, outbound-only connections from your origin servers to Cloudflare's edge, eliminating the need for public IP addresses and inbound firewall rules.

### Domain Architecture

- **Central SSO**: `sso.poc.hi-dil.com`
- **Tenant 1**: `tenant-one.poc.hi-dil.com`
- **Tenant 2**: `tenant-two.poc.hi-dil.com`

## Benefits of Cloudflare Tunnel

### 🔒 Security Benefits
- **Zero-Trust Architecture**: No inbound connections to your origin server
- **DDoS Protection**: Cloudflare's network absorbs attacks before they reach your servers
- **Automatic SSL/TLS**: Free SSL certificates for all subdomains
- **IP Allowlisting**: Control access at Cloudflare's edge
- **Web Application Firewall (WAF)**: Protection against common web vulnerabilities

### 🚀 Performance Benefits
- **Global CDN**: Content served from 300+ edge locations worldwide
- **Smart Routing**: Optimal path selection for improved latency
- **Caching**: Static content cached at the edge
- **Compression**: Automatic Brotli/Gzip compression
- **HTTP/3 Support**: Latest protocol for improved performance

### 🛠 Operational Benefits
- **Easy Setup**: No complex firewall rules or port forwarding
- **Automatic Updates**: Tunnel client auto-updates
- **High Availability**: Multiple tunnel replicas for redundancy
- **Monitoring**: Built-in analytics and logging
- **Cost Effective**: Reduce bandwidth costs through caching

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                 Cloudflare Edge Network                 │
│ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────┐ │
│ │ sso.poc.hi-dil  │ │tenant-one.poc..│ │tenant-two..│ │
│ │     .com        │ │    hi-dil.com   │ │  hi-dil.com │ │
│ └─────────┬───────┘ └─────────┬───────┘ └─────┬───────┘ │
└───────────┼───────────────────┼─────────────────┼─────────┘
            │                   │                 │
            │    Cloudflare Tunnel (Outbound Only)│
            │                   │                 │
    ┌───────┼───────────────────┼─────────────────┼─────────┐
    │       ▼                   ▼                 ▼         │
    │  ┌─────────────────┐ ┌─────────────┐ ┌─────────────┐ │
    │  │  cloudflared    │ │             │ │             │ │
    │  │   (tunnel)      │ │   Docker    │ │   Docker    │ │
    │  │                 │ │   Network   │ │   Network   │ │
    │  └─────────┬───────┘ └─────────────┘ └─────────────┘ │
    │            ▼                                         │
    │  ┌─────────────────┐                                 │
    │  │   central-sso   │←─────── 8000:8000 (internal)   │
    │  │     :8000       │                                 │
    │  └─────────────────┘                                 │
    │  ┌─────────────────┐                                 │
    │  │   tenant1-app   │←─────── 8001:8000 (internal)   │
    │  │     :8000       │                                 │
    │  └─────────────────┘                                 │
    │  ┌─────────────────┐                                 │
    │  │   tenant2-app   │←─────── 8002:8000 (internal)   │
    │  │     :8000       │                                 │
    │  └─────────────────┘                                 │
    │  ┌─────────────────┐                                 │
    │  │   sso-mariadb   │←─────── 3307:3306 (internal)   │
    │  │     :3306       │                                 │
    │  └─────────────────┘                                 │
    └─────────────────────────────────────────────────────┘
                    Your Server (No Public IPs)
```

## Prerequisites

### 1. Cloudflare Account Setup
1. Sign up for a Cloudflare account (free tier available)
2. Add your domain `hi-dil.com` to Cloudflare
3. Update nameservers to Cloudflare's nameservers
4. Verify domain is active in Cloudflare dashboard

### 2. Choose Your Installation Method

#### Option A: Docker-Only Setup (Recommended)
No local cloudflared installation required - everything runs in containers:

```bash
# Only Docker is required
docker --version
docker-compose --version

# Create Cloudflare API token at:
# https://dash.cloudflare.com/profile/api-tokens
# Required permissions: Zone:Edit for hi-dil.com
```

#### Option B: Local Cloudflared Installation
Traditional approach with local tool installation:

```bash
# macOS (using Homebrew)
brew install cloudflared

# Ubuntu/Debian
wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared-linux-amd64.deb

# CentOS/RHEL
wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.rpm
sudo rpm -i cloudflared-linux-amd64.rpm

# Docker (included in our docker-compose setup)
docker pull cloudflare/cloudflared:latest
```

### 3. Authentication Setup

#### For Docker-Only Setup:
```bash
# Create Cloudflare API token with these permissions:
# - Zone:Edit for hi-dil.com
# - Account:Read for account access
# No local authentication required
```

#### For Local Installation:
```bash
cloudflared tunnel login
```
This opens a browser window to authenticate with your Cloudflare account and downloads a certificate to `~/.cloudflared/cert.pem`.

## Setup Instructions

Choose your preferred setup method:

### Quick Setup Options

#### Option A: Docker-Only Setup (Recommended)
```bash
# Set your Cloudflare API credentials
export CLOUDFLARE_API_TOKEN="your-api-token-here"
export CLOUDFLARE_EMAIL="your-email@example.com"

# Run the automated Docker-only setup
chmod +x scripts/setup-cloudflare-tunnel-docker.sh
./scripts/setup-cloudflare-tunnel-docker.sh
```

#### Option B: Traditional Setup with Local Cloudflared
```bash
# Run the traditional setup script
chmod +x scripts/setup-cloudflare-tunnel.sh
./scripts/setup-cloudflare-tunnel.sh
```

### Manual Setup Instructions

If you prefer to understand each step or need custom configuration:

#### Method 1: Docker-Only Manual Setup

**Step 1: Create Tunnel via Docker**
```bash
# Create tunnel using Docker container
mkdir -p cloudflare
docker run --rm \
    -v "$(pwd)/cloudflare:/output" \
    -e CLOUDFLARE_API_TOKEN="your-api-token" \
    cloudflare/cloudflared:latest \
    tunnel create sso-poc-tunnel

# Extract tunnel ID and save credentials
TUNNEL_ID=$(cat cloudflare/tunnel-credentials.json | grep -o '"TunnelID":"[^"]*"' | cut -d'"' -f4)
echo $TUNNEL_ID > cloudflare/tunnel-id.txt
```

**Step 2: Setup DNS via API**
```bash
# Get zone ID
ZONE_ID=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones?name=hi-dil.com" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" | \
    grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

# Create DNS records
for SUBDOMAIN in "sso.poc" "tenant-one.poc" "tenant-two.poc"; do
    curl -X POST "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
        -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
        -H "Content-Type: application/json" \
        --data "{
            \"type\": \"CNAME\",
            \"name\": \"$SUBDOMAIN\",
            \"content\": \"$TUNNEL_ID.cfargotunnel.com\",
            \"proxied\": true
        }"
done
```

#### Method 2: Traditional Manual Setup

**Step 1: Create Cloudflare Tunnel**
```bash
# Create a tunnel (replace 'sso-poc-tunnel' with your preferred name)
cloudflared tunnel create sso-poc-tunnel

# Note the Tunnel ID from the output, you'll need it later
# Example output: Created tunnel sso-poc-tunnel with id: 12345678-1234-1234-1234-123456789abc
```

### Step 2: Configure DNS Records

Create CNAME records in your Cloudflare dashboard or via CLI:

```bash
# Get your tunnel ID
TUNNEL_ID=$(cloudflared tunnel list | grep sso-poc-tunnel | awk '{print $1}')

# Create DNS records
cloudflared tunnel route dns sso-poc-tunnel sso.poc.hi-dil.com
cloudflared tunnel route dns sso-poc-tunnel tenant-one.poc.hi-dil.com
cloudflared tunnel route dns sso-poc-tunnel tenant-two.poc.hi-dil.com
```

Or manually in Cloudflare dashboard:
- **Type**: CNAME
- **Name**: `sso.poc`, `tenant-one.poc`, `tenant-two.poc`
- **Target**: `{TUNNEL_ID}.cfargotunnel.com`
- **Proxy Status**: Proxied (orange cloud)

### Step 3: Setup Project Structure

Create the necessary directories and configuration files:

```bash
# Create Cloudflare configuration directory
mkdir -p cloudflare

# Create scripts directory if it doesn't exist
mkdir -p scripts

# Copy tunnel credentials (replace TUNNEL_ID with your actual tunnel ID)
TUNNEL_ID="your-tunnel-id-here"
cp ~/.cloudflared/${TUNNEL_ID}.json ./cloudflare/
```

### Step 4: Configure Environment Variables

Create a `.env.cloudflare` file based on the provided template:

```bash
cp .env.cloudflare.example .env.cloudflare
# Edit the file with your specific tunnel ID and domain configurations
```

### Step 5: Deploy with Docker Compose

```bash
# Deploy the complete stack with Cloudflare Tunnel
docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml up -d

# Or use the setup script
./scripts/setup-cloudflare-tunnel.sh
```

## Configuration Files

### Tunnel Configuration (`cloudflare/config.yml`)

```yaml
tunnel: sso-poc-tunnel
credentials-file: /etc/cloudflared/tunnel-credentials.json

# Ingress rules - order matters!
ingress:
  # Central SSO
  - hostname: sso.poc.hi-dil.com
    service: http://central-sso:8000
    originRequest:
      httpHostHeader: sso.poc.hi-dil.com
      noHappyEyeballs: true
      keepAliveTimeout: 30s
      tcpKeepAlive: 30s
  
  # Tenant 1 Application
  - hostname: tenant-one.poc.hi-dil.com
    service: http://tenant1-app:8000
    originRequest:
      httpHostHeader: tenant-one.poc.hi-dil.com
      noHappyEyeballs: true
      keepAliveTimeout: 30s
      tcpKeepAlive: 30s
  
  # Tenant 2 Application
  - hostname: tenant-two.poc.hi-dil.com
    service: http://tenant2-app:8000
    originRequest:
      httpHostHeader: tenant-two.poc.hi-dil.com
      noHappyEyeballs: true
      keepAliveTimeout: 30s
      tcpKeepAlive: 30s
  
  # Catch-all rule (must be last)
  - service: http_status:404

# Metrics and logging
metrics: 0.0.0.0:9090
```

### Docker Compose Override (`docker-compose.cloudflare.yml`)

```yaml
version: '3.8'

services:
  # Cloudflare Tunnel
  cloudflared:
    image: cloudflare/cloudflared:latest
    container_name: cloudflared-tunnel
    command: tunnel --config /etc/cloudflared/config.yml run
    volumes:
      - ./cloudflare/config.yml:/etc/cloudflared/config.yml:ro
      - ./cloudflare/tunnel-credentials.json:/etc/cloudflared/tunnel-credentials.json:ro
    networks:
      - sso-network
    restart: unless-stopped
    depends_on:
      - central-sso
      - tenant1-app
      - tenant2-app
    environment:
      - TUNNEL_METRICS=0.0.0.0:9090
    ports:
      - "9090:9090"  # Metrics endpoint (optional, for monitoring)

  # Update Central SSO with production environment
  central-sso:
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://sso.poc.hi-dil.com
      - ASSET_URL=https://sso.poc.hi-dil.com
      - SANCTUM_STATEFUL_DOMAINS=sso.poc.hi-dil.com,tenant-one.poc.hi-dil.com,tenant-two.poc.hi-dil.com
      - SESSION_DOMAIN=.poc.hi-dil.com
      - CORS_ALLOWED_ORIGINS=https://sso.poc.hi-dil.com,https://tenant-one.poc.hi-dil.com,https://tenant-two.poc.hi-dil.com

  # Update Tenant 1 with production environment
  tenant1-app:
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://tenant-one.poc.hi-dil.com
      - ASSET_URL=https://tenant-one.poc.hi-dil.com
      - CENTRAL_SSO_URL=https://sso.poc.hi-dil.com
      - CENTRAL_SSO_API=http://central-sso:8000/api
      - SESSION_DOMAIN=.poc.hi-dil.com
      - CORS_ALLOWED_ORIGINS=https://sso.poc.hi-dil.com,https://tenant-one.poc.hi-dil.com

  # Update Tenant 2 with production environment
  tenant2-app:
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - APP_URL=https://tenant-two.poc.hi-dil.com
      - ASSET_URL=https://tenant-two.poc.hi-dil.com
      - CENTRAL_SSO_URL=https://sso.poc.hi-dil.com
      - CENTRAL_SSO_API=http://central-sso:8000/api
      - SESSION_DOMAIN=.poc.hi-dil.com
      - CORS_ALLOWED_ORIGINS=https://sso.poc.hi-dil.com,https://tenant-two.poc.hi-dil.com

networks:
  sso-network:
    external: true
```

## Security Configuration

### Cloudflare Access (Optional but Recommended)

Add an extra layer of authentication using Cloudflare Access:

1. **Navigate** to Cloudflare Dashboard → Access → Applications
2. **Create Application** with these settings:
   - **Application Name**: SSO POC Environment
   - **Subdomain**: `*.poc`
   - **Domain**: `hi-dil.com`
   - **Path**: Leave empty for all paths

3. **Configure Policies**:
   ```yaml
   # Development Team Access
   Policy Name: Dev Team Access
   Action: Allow
   Rules:
     - Email domain: @yourdomain.com
     - OR Email: specific-developer@example.com
   
   # Admin Access
   Policy Name: Admin Access
   Action: Allow
   Rules:
     - Email: admin@yourdomain.com
   ```

### WAF Rules

Configure Web Application Firewall rules in Cloudflare:

1. **Rate Limiting**:
   ```yaml
   Rule: Login Rate Limiting
   If: (http.request.uri.path contains "/login") and (http.request.method eq "POST")
   Then: Rate limit 5 requests per minute per IP
   ```

2. **API Protection**:
   ```yaml
   Rule: API Rate Limiting
   If: (http.request.uri.path contains "/api/") and (http.request.method ne "GET")
   Then: Rate limit 100 requests per minute per IP
   ```

3. **Geoblocking** (if needed):
   ```yaml
   Rule: Geographic Restrictions
   If: (ip.geoip.country ne "US" and ip.geoip.country ne "CA")
   Then: Block
   ```

### SSL/TLS Configuration

1. **SSL/TLS Encryption Mode**: Full (Strict)
2. **Minimum TLS Version**: 1.2
3. **HTTP Strict Transport Security (HSTS)**:
   - Enable HSTS
   - Max Age Header: 12 months
   - Include subdomains: Yes
   - No-Sniff Header: Yes

## Monitoring and Troubleshooting

### Tunnel Metrics

Monitor tunnel health via the metrics endpoint:

```bash
# Check tunnel metrics
curl http://localhost:9090/metrics

# Key metrics to monitor:
# - cloudflared_tunnel_connections_registered
# - cloudflared_tunnel_request_duration_seconds
# - cloudflared_tunnel_response_by_code
```

### Logging

Enable comprehensive logging:

```yaml
# Add to cloudflare/config.yml
tunnel: sso-poc-tunnel
credentials-file: /etc/cloudflared/tunnel-credentials.json

# Logging configuration
log-level: info
log-file: /var/log/cloudflared.log
log-format: json

ingress:
  # ... your ingress rules
```

### Common Issues and Solutions

#### 1. **502 Bad Gateway Errors**
```bash
# Check if services are running
docker-compose ps

# Check service logs
docker-compose logs central-sso
docker-compose logs tenant1-app
docker-compose logs cloudflared

# Verify internal connectivity
docker exec cloudflared-tunnel nslookup central-sso
```

#### 2. **DNS Resolution Issues**
```bash
# Verify DNS records
dig sso.poc.hi-dil.com
dig tenant-one.poc.hi-dil.com
dig tenant-two.poc.hi-dil.com

# Check tunnel status
cloudflared tunnel list
cloudflared tunnel info sso-poc-tunnel
```

#### 3. **Authentication Issues**
```bash
# Verify tunnel credentials
ls -la cloudflare/tunnel-credentials.json

# Re-authenticate if needed
cloudflared tunnel login
```

#### 4. **Performance Issues**
- Check Cloudflare Analytics dashboard
- Monitor origin server resources
- Review cache settings
- Optimize ingress rules order

### Health Checks

Create a health check endpoint for monitoring:

```bash
# Add to your application
curl https://sso.poc.hi-dil.com/health
curl https://tenant-one.poc.hi-dil.com/health
curl https://tenant-two.poc.hi-dil.com/health
```

## Deployment Workflow

### Development to Production

1. **Test Locally** with tunnel:
   ```bash
   # Start development environment with tunnel
   docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml up -d
   ```

2. **Verify Functionality**:
   - Test SSO authentication flow
   - Verify cross-tenant functionality
   - Test API endpoints
   - Validate security headers

3. **Production Deployment**:
   ```bash
   # Deploy to production server
   ./scripts/deploy-production.sh
   ```

### Backup and Recovery

1. **Tunnel Configuration Backup**:
   ```bash
   # Backup tunnel credentials and config
   tar -czf cloudflare-backup-$(date +%Y%m%d).tar.gz cloudflare/
   ```

2. **Disaster Recovery**:
   ```bash
   # Restore tunnel on new server
   cloudflared tunnel login
   # Copy backed up credentials
   # Redeploy using same tunnel ID
   ```

## Cost Optimization

### Caching Strategy

Configure caching rules in Cloudflare:

1. **Static Assets**: Cache for 1 year
   - CSS, JS, images, fonts
   - Rules: `/*.css`, `/*.js`, `/*.png`, `/*.jpg`, `/*.woff2`

2. **API Responses**: Cache for 5 minutes (if appropriate)
   - Non-sensitive, frequently accessed data
   - Use cache-control headers in your application

3. **Dynamic Content**: No cache
   - Authentication endpoints
   - User-specific data

### Bandwidth Optimization

1. **Enable Cloudflare's optimization features**:
   - Auto Minify (CSS, JS, HTML)
   - Brotli compression
   - Image optimization

2. **Optimize your applications**:
   - Use efficient image formats (WebP)
   - Implement proper caching headers
   - Minimize API payload sizes

## Conclusion

This Cloudflare Tunnel deployment provides:
- **Enterprise-grade security** with zero-trust architecture
- **Global performance** through Cloudflare's CDN
- **Simplified networking** with no firewall configuration needed
- **Professional domain structure** for your POC environment
- **Scalable foundation** for production deployment

The setup eliminates the complexity of traditional reverse proxy configurations while providing superior security and performance characteristics suitable for a production SSO system.