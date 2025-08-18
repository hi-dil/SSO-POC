#!/bin/bash

# =============================================================================
# Cloudflare Network Setup Script
# =============================================================================
# Creates the external bridge network and sets up Cloudflare tunnel integration

set -e

echo "🌐 Setting up Cloudflare Bridge Network"
echo "========================================"

# Create the external bridge network
NETWORK_NAME="cloudflare-net"

echo "📡 Creating bridge network: $NETWORK_NAME"

if docker network ls | grep -q "$NETWORK_NAME"; then
    echo "   ✅ Network $NETWORK_NAME already exists"
else
    docker network create \
        --driver bridge \
        --subnet=172.20.0.0/16 \
        --gateway=172.20.0.1 \
        $NETWORK_NAME
    echo "   ✅ Created network $NETWORK_NAME"
fi

echo ""
echo "🔍 Network Information:"
docker network inspect $NETWORK_NAME --format='{{json .IPAM.Config}}' | jq .

echo ""
echo "📋 Next Steps:"
echo ""
echo "1. Create your Cloudflare tunnel configuration:"
echo "   cp cloudflare/config.yml.example cloudflare/config.yml"
echo "   # Edit cloudflare/config.yml with your tunnel UUID and domains"
echo ""
echo "2. Add your tunnel credentials:"
echo "   # Place your tunnel credentials in cloudflare/tunnel-credentials.json"
echo ""
echo "3. Start the SSO services (will join cloudflare-net):"
echo "   docker-compose up -d"
echo ""
echo "4. Start the Cloudflare tunnel:"
echo "   docker-compose -f docker-compose.cloudflare.yml up -d"
echo ""
echo "5. Verify connectivity:"
echo "   docker exec central-sso ping cloudflared-sso"
echo "   docker exec cloudflared-sso ping central-sso"
echo ""
echo "🌐 Network Architecture:"
echo ""
echo "   ┌─────────────────────────────────────────────────────────┐"
echo "   │                 cloudflare-net                          │"
echo "   │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │"
echo "   │  │ cloudflared  │  │ central-sso  │  │ tenant1-app  │  │"
echo "   │  │              │  │              │  │              │  │"
echo "   │  │ :8080 (met.) │  │ :8000 (http) │  │ :8000 (http) │  │"
echo "   │  └──────────────┘  └──────────────┘  └──────────────┘  │"
echo "   │                     │              │                   │"
echo "   │                    ┌▼──────────────▼─┐                 │"
echo "   │                    │ tenant2-app     │                 │"
echo "   │                    │ :8000 (http)    │                 │"
echo "   │                    └─────────────────┘                 │"
echo "   └─────────────────────────────────────────────────────────┘"
echo "   ┌─────────────────────────────────────────────────────────┐"
echo "   │                  sso-network                            │"
echo "   │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │"
echo "   │  │ central-sso  │  │ tenant1-app  │  │ tenant2-app  │  │"
echo "   │  │              │◄─┤              │◄─┤              │  │"
echo "   │  │              │  │              │  │              │  │"
echo "   │  └──────────────┘  └──────────────┘  └──────────────┘  │"
echo "   │         ▲                                              │"
echo "   │         │                                              │"
echo "   │  ┌──────▼──────┐                                       │"
echo "   │  │  mariadb    │                                       │"
echo "   │  │ :3306 (db)  │                                       │"
echo "   │  └─────────────┘                                       │"
echo "   └─────────────────────────────────────────────────────────┘"
echo ""
echo "✅ Cloudflare network setup complete!"
echo ""
echo "💡 Benefits of this setup:"
echo "   • Cloudflare tunnel runs independently"
echo "   • Easy to manage tunnel configuration separately"
echo "   • SSO services can communicate with tunnel via container names"
echo "   • Database remains isolated in internal network"
echo "   • Tunnel can be restarted without affecting SSO services"