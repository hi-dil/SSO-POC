#!/bin/bash

# =============================================================================
# SSL/TLS Connection Troubleshooting Script
# =============================================================================
# Diagnoses SSL connection issues with Cloudflare tunnel deployment

set -e

echo "🔍 SSL/TLS Connection Troubleshooting"
echo "======================================"

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Error: docker-compose.yml not found."
    echo "   Please run this script from the project root directory."
    exit 1
fi

echo ""
echo "1️⃣ Checking Docker Container Status..."
echo "---------------------------------------"
docker-compose ps

echo ""
echo "2️⃣ Checking Cloudflare Tunnel Status..."
echo "----------------------------------------"
if docker ps | grep -q cloudflared; then
    echo "✅ Cloudflared container is running"
    echo ""
    echo "📋 Cloudflare Tunnel Logs (last 20 lines):"
    docker logs --tail 20 $(docker ps | grep cloudflared | awk '{print $1}') 2>/dev/null || echo "❌ Could not get cloudflared logs"
else
    echo "❌ Cloudflared container is not running"
    echo ""
    echo "🔧 Starting Cloudflare tunnel..."
    if [ -f "docker-compose.cloudflare.yml" ]; then
        docker-compose -f docker-compose.cloudflare.yml up -d
    else
        echo "⚠️  No docker-compose.cloudflare.yml found"
        echo "   You may need to start the tunnel manually"
    fi
fi

echo ""
echo "3️⃣ Checking Internal Service Connectivity..."
echo "---------------------------------------------"
echo "Testing if central-sso is accessible internally..."
docker exec central-sso curl -I http://localhost:8000/health 2>/dev/null || echo "❌ Central SSO health check failed"

echo ""
echo "4️⃣ Checking Cloudflare DNS Configuration..."
echo "--------------------------------------------"
echo "Checking DNS resolution for your domains..."

DOMAINS=("sso.poc.hi-dil.com" "tenant-one.poc.hi-dil.com" "tenant-two.poc.hi-dil.com")

for domain in "${DOMAINS[@]}"; do
    echo -n "Checking $domain: "
    if dig +short $domain | grep -q .; then
        echo "✅ Resolves to: $(dig +short $domain | head -1)"
    else
        echo "❌ DNS resolution failed"
    fi
done

echo ""
echo "5️⃣ Testing SSL/TLS Connection..."
echo "--------------------------------"
echo "Testing SSL connection to your domain..."
echo "Domain: sso.poc.hi-dil.com"
echo ""

# Test SSL connection
if command -v openssl &> /dev/null; then
    echo "🔍 SSL Certificate Information:"
    timeout 10 openssl s_client -connect sso.poc.hi-dil.com:443 -servername sso.poc.hi-dil.com < /dev/null 2>/dev/null | openssl x509 -text -noout | grep -A 2 "Subject:"
    echo ""
    
    echo "🔍 SSL Connection Test:"
    if timeout 10 openssl s_client -connect sso.poc.hi-dil.com:443 -servername sso.poc.hi-dil.com < /dev/null 2>/dev/null | grep -q "CONNECTED"; then
        echo "✅ SSL connection successful"
    else
        echo "❌ SSL connection failed"
    fi
else
    echo "⚠️  OpenSSL not available for testing"
fi

echo ""
echo "6️⃣ Checking Cloudflare Configuration..."
echo "---------------------------------------"
if [ -f "cloudflare/config.yml" ]; then
    echo "✅ Cloudflare config file exists"
    echo ""
    echo "📋 Tunnel Configuration:"
    cat cloudflare/config.yml | grep -A 20 "ingress:" || echo "❌ Could not read ingress configuration"
else
    echo "❌ cloudflare/config.yml not found"
    echo "   You need to create the tunnel configuration file"
fi

echo ""
echo "🔧 Common Solutions:"
echo "==================="
echo ""
echo "If you're getting SSL/TLS errors, try these steps:"
echo ""
echo "1. **Check Tunnel Status:**"
echo "   docker logs cloudflared-sso"
echo "   # Look for connection errors or authentication issues"
echo ""
echo "2. **Verify Cloudflare DNS:**"
echo "   # Make sure your domains point to Cloudflare (orange cloud enabled)"
echo "   # Check that DNS records are set to 'Proxied' not 'DNS only'"
echo ""
echo "3. **Restart Tunnel:**"
echo "   docker-compose -f docker-compose.cloudflare.yml down"
echo "   docker-compose -f docker-compose.cloudflare.yml up -d"
echo ""
echo "4. **Check Tunnel Authentication:**"
echo "   # Ensure tunnel-credentials.json is valid and not expired"
echo "   # Verify tunnel UUID matches your Cloudflare dashboard"
echo ""
echo "5. **Test Internal Services:**"
echo "   docker exec central-sso curl http://localhost:8000"
echo "   # Ensure your services are running and accessible"
echo ""
echo "6. **Cloudflare SSL Mode:**"
echo "   # In Cloudflare dashboard: SSL/TLS → Overview → SSL/TLS encryption mode"
echo "   # Should be set to 'Full' or 'Full (strict)' for proper HTTPS"
echo ""
echo "🆘 If problems persist:"
echo "   1. Check Cloudflare tunnel logs: docker logs cloudflared-sso"
echo "   2. Verify DNS propagation: https://www.whatsmydns.net/"
echo "   3. Check Cloudflare tunnel status in dashboard"
echo "   4. Ensure tunnel credentials are valid and not expired"