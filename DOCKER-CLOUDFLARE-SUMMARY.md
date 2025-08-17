# Docker-Only Cloudflare Tunnel Setup - Quick Reference

## 🐳 Docker-Only Approach Benefits

### ✅ **Advantages**
- **No Local Dependencies**: No need to install cloudflared on your host system
- **Container Isolation**: Everything runs in isolated Docker containers
- **CI/CD Ready**: Perfect for automated deployment pipelines
- **Consistent Environment**: Same setup across development and production
- **Easy Updates**: Update tunnel client by pulling new Docker images
- **API-Driven**: Uses Cloudflare API for all management operations

### 📋 **Requirements**
- Docker and Docker Compose
- Cloudflare API token with Zone:Edit permissions
- Domain (`hi-dil.com`) added to Cloudflare account

## 🚀 Quick Start (Docker-Only)

### Step 1: Create Cloudflare API Token
1. Go to: https://dash.cloudflare.com/profile/api-tokens
2. Click "Create Token"
3. Use "Custom token" with these permissions:
   - **Zone:Edit** for `hi-dil.com`
   - **Account:Read** for account access
4. Save the token securely

### Step 2: Set Environment Variables
```bash
export CLOUDFLARE_API_TOKEN="your-api-token-here"
export CLOUDFLARE_EMAIL="your-cloudflare-email@example.com"
```

### Step 3: Run Automated Setup
```bash
# Make script executable and run
chmod +x scripts/setup-cloudflare-tunnel-docker.sh
./scripts/setup-cloudflare-tunnel-docker.sh
```

## 📁 Files Created (Docker Approach)

### 🔧 **Configuration Files**
- `cloudflare/config.yml` - Tunnel routing configuration
- `cloudflare/tunnel-credentials.json` - Tunnel authentication (auto-generated)
- `cloudflare/tunnel-id.txt` - Tunnel ID reference
- `.env.cloudflare` - Environment variables for production

### 🐳 **Docker Configuration**
- `docker-compose.cloudflare.yml` - Docker Compose override with tunnel service
- Container: `cloudflared-tunnel` runs the tunnel client

### 📜 **Scripts**
- `scripts/setup-cloudflare-tunnel-docker.sh` - Automated Docker-only setup
- `scripts/setup-cloudflare-tunnel.sh` - Traditional setup (for comparison)

## 🌐 Domain Structure

After successful setup, your applications will be accessible at:

- **Central SSO**: `https://sso.poc.hi-dil.com`
- **Tenant 1**: `https://tenant-one.poc.hi-dil.com`
- **Tenant 2**: `https://tenant-two.poc.hi-dil.com`

## 🔄 Management Commands

### Container Operations
```bash
# View tunnel logs
docker-compose logs -f cloudflared

# Restart tunnel
docker-compose restart cloudflared

# Stop all services
docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml down

# Start all services
docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml up -d

# Update tunnel (pull latest image)
docker-compose pull cloudflared && docker-compose up -d cloudflared
```

### Monitoring
```bash
# Check tunnel metrics
curl http://localhost:9090/metrics

# Check tunnel health
docker exec cloudflared-tunnel cloudflared tunnel info sso-poc-tunnel

# View tunnel status
docker exec cloudflared-tunnel cloudflared tunnel list
```

### DNS Management (via API)
```bash
# List DNS records
curl -X GET "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"

# Check zone status
curl -X GET "https://api.cloudflare.com/client/v4/zones?name=hi-dil.com" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"
```

## 🔒 Security Features (Included)

### Zero-Trust Architecture
- ✅ No inbound connections to your server
- ✅ Outbound-only tunnel connections
- ✅ Automatic SSL/TLS certificates
- ✅ Cloudflare DDoS protection

### Access Control
- ✅ Cloudflare WAF (Web Application Firewall)
- ✅ Rate limiting at edge
- ✅ IP geoblocking capabilities
- ✅ Trusted proxy configuration

## 📊 Monitoring & Observability

### Built-in Metrics
- Tunnel connection status: `http://localhost:9090/metrics`
- Application logs: `docker-compose logs [service]`
- Cloudflare Analytics: Available in Cloudflare dashboard

### Health Checks
```bash
# Automated health check script
#!/bin/bash
curl -f http://localhost:9090/metrics && \
docker exec cloudflared-tunnel cloudflared tunnel info sso-poc-tunnel && \
curl -f https://sso.poc.hi-dil.com/health
```

## 🛠 Troubleshooting

### Common Issues & Solutions

#### 1. **API Token Issues**
```bash
# Verify token permissions
curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"
```

#### 2. **Container Won't Start**
```bash
# Check logs
docker logs cloudflared-tunnel

# Verify credentials
ls -la cloudflare/tunnel-credentials.json

# Check configuration
docker exec cloudflared-tunnel cat /etc/cloudflared/config.yml
```

#### 3. **DNS Not Resolving**
```bash
# Test DNS resolution
dig sso.poc.hi-dil.com
nslookup tenant-one.poc.hi-dil.com

# Check Cloudflare DNS records
curl -X GET "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"
```

#### 4. **Services Not Accessible**
```bash
# Test internal connectivity
docker exec cloudflared-tunnel nslookup central-sso
docker exec cloudflared-tunnel curl -I http://central-sso:8000

# Check tunnel ingress
docker exec cloudflared-tunnel cloudflared tunnel info sso-poc-tunnel
```

## 🚀 Production Deployment

### CI/CD Pipeline Example
```yaml
# .github/workflows/deploy-cloudflare.yml
name: Deploy with Cloudflare Tunnel (Docker)

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Cloudflare Tunnel
        env:
          CLOUDFLARE_API_TOKEN: ${{ secrets.CLOUDFLARE_API_TOKEN }}
          CLOUDFLARE_EMAIL: ${{ secrets.CLOUDFLARE_EMAIL }}
        run: |
          chmod +x scripts/setup-cloudflare-tunnel-docker.sh
          ./scripts/setup-cloudflare-tunnel-docker.sh
      
      - name: Health Check
        run: |
          sleep 30
          curl -f http://localhost:9090/metrics
          curl -f https://sso.poc.hi-dil.com/health
```

## 📖 Documentation Links

### Detailed Guides
- **Complete Setup**: `docs/cloudflare-tunnel-deployment.md`
- **Docker-Only Guide**: `docs/cloudflare-docker-only-setup.md`
- **Application Config**: `docs/cloudflare-application-config.md`

### Configuration Examples
- **Docker Compose**: `docker-compose.cloudflare.yml`
- **Environment Variables**: `.env.cloudflare.example`
- **Tunnel Config**: `cloudflare/config.yml`

## 🎯 Summary

The Docker-only approach provides:

1. **🐳 Simplified Deployment**: No local tool installation required
2. **🔧 Container-Native**: Everything runs in Docker containers
3. **🚀 Production Ready**: Suitable for any environment with Docker
4. **📡 API-Driven**: Fully automated via Cloudflare API
5. **🔒 Enterprise Security**: Zero-trust architecture with Cloudflare protection
6. **📊 Monitoring**: Built-in metrics and health checks
7. **🔄 Maintainable**: Easy updates and configuration management

Your SSO POC system will be accessible globally through Cloudflare's edge network with enterprise-grade security and performance!