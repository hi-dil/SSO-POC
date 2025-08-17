# Cloudflare Tunnel - Docker-Only Setup Guide

## Overview

This guide provides a complete Docker-only approach to setting up Cloudflare Tunnel for your SSO system. No local `cloudflared` installation is required - everything runs in containers using the Cloudflare API for management.

## Benefits of Docker-Only Approach

### 🐳 **Container Advantages**
- **No Host Dependencies**: No need to install cloudflared locally
- **Consistent Environment**: Same setup across development and production
- **Easy Updates**: Update tunnel client by pulling new Docker images
- **Isolation**: Tunnel runs in isolated container environment
- **Portability**: Works on any system with Docker

### 🔌 **API-Driven Management**
- **Automated Setup**: Uses Cloudflare API for DNS and tunnel management
- **Scriptable**: Fully automated deployment process
- **CI/CD Ready**: Perfect for automated deployment pipelines
- **Remote Management**: Manage tunnels without direct server access

## Prerequisites

### 1. Docker Environment
```bash
# Verify Docker installation
docker --version
docker-compose --version
```

### 2. Cloudflare API Token
Create an API token at: https://dash.cloudflare.com/profile/api-tokens

**Required Permissions:**
- **Zone:Edit** for your domain (`hi-dil.com`)
- **Account:Read** for account access
- **Zone.Zone:Read** for zone access

**Token Scopes:**
```
Zone Resources:
  Include: Specific zone - hi-dil.com

Account Resources:
  Include: All accounts
```

### 3. Domain Setup
Ensure `hi-dil.com` is added to your Cloudflare account with active status.

## Quick Start (Automated)

### Option 1: Interactive Setup Script

```bash
# Make script executable
chmod +x scripts/setup-cloudflare-tunnel-docker.sh

# Run the automated setup
./scripts/setup-cloudflare-tunnel-docker.sh
```

The script will prompt for:
- Cloudflare API token
- Cloudflare account email

### Option 2: Environment Variables

```bash
# Set environment variables
export CLOUDFLARE_API_TOKEN="your-api-token-here"
export CLOUDFLARE_EMAIL="your-email@example.com"

# Run the setup
./scripts/setup-cloudflare-tunnel-docker.sh
```

## Manual Setup Process

If you prefer to understand each step or need to customize the process:

### Step 1: Create Tunnel via Docker

```bash
# Create cloudflare directory
mkdir -p cloudflare

# Create tunnel using Docker container
docker run --rm \
    -v "$(pwd)/cloudflare:/output" \
    -e CLOUDFLARE_API_TOKEN="your-api-token" \
    cloudflare/cloudflared:latest \
    tunnel create sso-poc-tunnel

# The tunnel credentials will be saved to cloudflare/tunnel-credentials.json
```

### Step 2: Setup DNS Records via API

```bash
# Get your zone ID
ZONE_ID=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones?name=hi-dil.com" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
    -H "Content-Type: application/json" | \
    jq -r '.result[0].id')

# Get tunnel ID from the created credentials
TUNNEL_ID=$(cat cloudflare/tunnel-credentials.json | jq -r '.TunnelID')

# Create DNS records
curl -X POST "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
    -H "Content-Type: application/json" \
    --data '{
        "type": "CNAME",
        "name": "sso.poc",
        "content": "'$TUNNEL_ID'.cfargotunnel.com",
        "proxied": true
    }'

curl -X POST "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
    -H "Content-Type: application/json" \
    --data '{
        "type": "CNAME",
        "name": "tenant-one.poc",
        "content": "'$TUNNEL_ID'.cfargotunnel.com",
        "proxied": true
    }'

curl -X POST "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
    -H "Content-Type: application/json" \
    --data '{
        "type": "CNAME",
        "name": "tenant-two.poc",
        "content": "'$TUNNEL_ID'.cfargotunnel.com",
        "proxied": true
    }'
```

### Step 3: Update Configuration

Update `cloudflare/config.yml` with your tunnel ID:

```yaml
tunnel: your-tunnel-id-here  # Replace with actual tunnel ID
credentials-file: /etc/cloudflared/tunnel-credentials.json

# Rest of configuration remains the same
ingress:
  - hostname: sso.poc.hi-dil.com
    service: http://central-sso:8000
  # ... other ingress rules
```

### Step 4: Deploy with Docker Compose

```bash
# Start the complete stack
docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml up -d
```

## Docker Compose Configuration

The `docker-compose.cloudflare.yml` file includes the cloudflared service:

```yaml
services:
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
    ports:
      - "9090:9090"  # Metrics endpoint
```

## Management Commands

### Tunnel Operations

```bash
# View tunnel logs
docker-compose logs -f cloudflared

# Restart tunnel
docker-compose restart cloudflared

# Check tunnel status
docker exec cloudflared-tunnel cloudflared tunnel info sso-poc-tunnel

# Update tunnel (pull latest image)
docker-compose pull cloudflared
docker-compose up -d cloudflared
```

### DNS Management via API

```bash
# List DNS records
curl -X GET "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records?type=CNAME" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
    -H "Content-Type: application/json"

# Update DNS record
curl -X PUT "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records/$RECORD_ID" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
    -H "Content-Type: application/json" \
    --data '{
        "type": "CNAME",
        "name": "sso.poc",
        "content": "new-tunnel-id.cfargotunnel.com",
        "proxied": true
    }'
```

### Monitoring

```bash
# Check tunnel metrics
curl http://localhost:9090/metrics

# Monitor tunnel health
docker exec cloudflared-tunnel cloudflared tunnel info sso-poc-tunnel

# View connection status
docker exec cloudflared-tunnel cloudflared tunnel list
```

## Environment Variables

Create `.env.cloudflare` with Docker-specific configuration:

```env
# Cloudflare API Configuration
CLOUDFLARE_API_TOKEN=your-api-token-here
CLOUDFLARE_EMAIL=your-email@example.com
CLOUDFLARE_ZONE_ID=your-zone-id

# Tunnel Configuration
TUNNEL_NAME=sso-poc-tunnel
TUNNEL_ID=your-tunnel-id-here

# Docker-specific settings
CLOUDFLARED_IMAGE=cloudflare/cloudflared:latest
CLOUDFLARED_LOG_LEVEL=info

# Domain Configuration
DOMAIN=hi-dil.com
CENTRAL_SSO_URL=https://sso.poc.hi-dil.com
TENANT1_URL=https://tenant-one.poc.hi-dil.com
TENANT2_URL=https://tenant-two.poc.hi-dil.com

# ... rest of configuration
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Deploy with Cloudflare Tunnel

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
      
      - name: Deploy Services
        run: |
          docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml up -d
      
      - name: Health Check
        run: |
          sleep 30
          curl -f http://localhost:9090/metrics
```

### Docker Swarm Deployment

```yaml
# docker-stack.yml
version: '3.8'

services:
  cloudflared:
    image: cloudflare/cloudflared:latest
    command: tunnel --config /etc/cloudflared/config.yml run
    configs:
      - source: tunnel_config
        target: /etc/cloudflared/config.yml
    secrets:
      - source: tunnel_credentials
        target: /etc/cloudflared/tunnel-credentials.json
    networks:
      - sso-network
    deploy:
      replicas: 2
      restart_policy:
        condition: on-failure

configs:
  tunnel_config:
    external: true

secrets:
  tunnel_credentials:
    external: true

networks:
  sso-network:
    external: true
```

## Troubleshooting

### Common Issues

#### 1. **Container Can't Create Tunnel**

```bash
# Check API token permissions
curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"

# Verify zone access
curl -X GET "https://api.cloudflare.com/client/v4/zones?name=hi-dil.com" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"
```

#### 2. **DNS Records Not Created**

```bash
# Check zone ID
ZONE_ID=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones?name=hi-dil.com" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" | jq -r '.result[0].id')

echo "Zone ID: $ZONE_ID"

# List existing records
curl -X GET "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"
```

#### 3. **Tunnel Connection Issues**

```bash
# Check tunnel container logs
docker logs cloudflared-tunnel

# Verify tunnel configuration
docker exec cloudflared-tunnel cat /etc/cloudflared/config.yml

# Test internal connectivity
docker exec cloudflared-tunnel nslookup central-sso
```

#### 4. **Metrics Not Available**

```bash
# Check if metrics port is exposed
docker port cloudflared-tunnel

# Test metrics endpoint
curl -v http://localhost:9090/metrics
```

## Advanced Configuration

### Custom Cloudflared Image

```dockerfile
# Dockerfile.cloudflared
FROM cloudflare/cloudflared:latest

# Add custom scripts or configuration
COPY scripts/ /usr/local/bin/
RUN chmod +x /usr/local/bin/*.sh

# Custom entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
```

### Health Check Script

```bash
#!/bin/bash
# health-check.sh

# Check tunnel metrics
if ! curl -f http://localhost:9090/metrics > /dev/null 2>&1; then
    echo "Tunnel metrics unavailable"
    exit 1
fi

# Check tunnel status
if ! docker exec cloudflared-tunnel cloudflared tunnel info sso-poc-tunnel > /dev/null 2>&1; then
    echo "Tunnel not healthy"
    exit 1
fi

echo "Tunnel healthy"
exit 0
```

## Benefits Summary

### Docker-Only Advantages

1. **🔧 No Local Dependencies**: Everything containerized
2. **📦 Easy Updates**: Pull new images to update
3. **🚀 CI/CD Ready**: Perfect for automated deployments
4. **🔄 Consistency**: Same environment everywhere
5. **📊 Better Monitoring**: Container-native logging and metrics
6. **🛡️ Security**: Isolated environment with minimal attack surface

### API-Driven Benefits

1. **🤖 Full Automation**: Script-driven setup and management
2. **📡 Remote Management**: Manage without server access
3. **🔄 Version Control**: All configuration in Git
4. **🎯 Precise Control**: Granular DNS and tunnel management
5. **📈 Scalable**: Easy to replicate across environments

This Docker-only approach provides a production-ready, maintainable solution for Cloudflare Tunnel deployment without requiring local tool installation.