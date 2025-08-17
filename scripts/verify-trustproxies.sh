#!/bin/bash

# =============================================================================
# TrustProxies Configuration Verification Script
# =============================================================================
# Verifies that TrustProxies middleware is properly configured

set -e

echo "🔍 TrustProxies Configuration Verification"
echo "=========================================="

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Error: docker-compose.yml not found."
    echo "   Please run this script from the project root directory."
    exit 1
fi

echo ""
echo "1️⃣ Checking TrustProxies Middleware Files..."
echo "--------------------------------------------"

APPS=("central-sso" "tenant1-app" "tenant2-app")

for app in "${APPS[@]}"; do
    MIDDLEWARE_FILE="$app/app/Http/Middleware/TrustProxies.php"
    if [ -f "$MIDDLEWARE_FILE" ]; then
        echo "   ✅ $app: TrustProxies middleware exists"
        
        # Check if the file contains proper Cloudflare configuration
        if grep -q "getCloudflareProxies" "$MIDDLEWARE_FILE"; then
            echo "      ✅ Contains Cloudflare IP configuration"
        else
            echo "      ❌ Missing Cloudflare IP configuration"
        fi
    else
        echo "   ❌ $app: TrustProxies middleware missing"
    fi
done

echo ""
echo "2️⃣ Checking Bootstrap Configuration..."
echo "-------------------------------------"

for app in "${APPS[@]}"; do
    BOOTSTRAP_FILE="$app/bootstrap/app.php"
    if [ -f "$BOOTSTRAP_FILE" ]; then
        echo "   📁 $app: bootstrap/app.php exists"
        
        # Check if TrustProxies is configured in bootstrap
        if grep -q "trustProxies.*TrustProxies" "$BOOTSTRAP_FILE"; then
            echo "      ✅ TrustProxies middleware is configured"
        else
            echo "      ❌ TrustProxies middleware not configured in bootstrap"
        fi
    else
        echo "   ❌ $app: bootstrap/app.php missing"
    fi
done

echo ""
echo "3️⃣ Checking Environment Configuration..."
echo "---------------------------------------"

if [ -f ".env" ]; then
    echo "   ✅ .env file exists"
    
    # Check for TRUSTED_PROXIES configuration
    if grep -q "TRUSTED_PROXIES" .env; then
        TRUSTED_PROXIES=$(grep "^TRUSTED_PROXIES=" .env | cut -d'=' -f2)
        echo "      ✅ TRUSTED_PROXIES configured: $TRUSTED_PROXIES"
    else
        echo "      ⚠️  TRUSTED_PROXIES not set (will use Cloudflare defaults)"
    fi
    
    # Check for HTTPS session configuration
    if grep -q "SESSION_SECURE_COOKIE=true" .env; then
        echo "      ✅ SESSION_SECURE_COOKIE=true (HTTPS ready)"
    else
        echo "      ⚠️  SESSION_SECURE_COOKIE not set to true (may cause CSRF issues on HTTPS)"
    fi
    
    # Check for session domain configuration
    if grep -q "SESSION_DOMAIN=" .env; then
        SESSION_DOMAIN=$(grep "^SESSION_DOMAIN=" .env | cut -d'=' -f2)
        echo "      ✅ SESSION_DOMAIN configured: $SESSION_DOMAIN"
    else
        echo "      ⚠️  SESSION_DOMAIN not configured (may need for cross-subdomain sessions)"
    fi
else
    echo "   ❌ .env file missing"
    echo "      Copy from .env.docker: cp .env.docker .env"
fi

echo ""
echo "4️⃣ Testing Container Configuration..."
echo "------------------------------------"

if docker-compose ps | grep -q "Up"; then
    echo "   ✅ Docker containers are running"
    
    # Test if TrustProxies middleware is loaded
    echo "   🔍 Testing TrustProxies middleware loading..."
    
    for app in "${APPS[@]}"; do
        echo -n "      $app: "
        if docker exec "$app" php -r "
            require '/var/www/html/vendor/autoload.php';
            \$app = require '/var/www/html/bootstrap/app.php';
            echo 'TrustProxies middleware accessible';
        " 2>/dev/null; then
            echo " ✅"
        else
            echo " ❌ Error loading application"
        fi
    done
    
else
    echo "   ⚠️  Docker containers not running"
    echo "      Start with: docker-compose up -d"
fi

echo ""
echo "5️⃣ HTTPS Detection Test..."
echo "--------------------------"

if docker-compose ps | grep -q "Up"; then
    echo "   🧪 Testing HTTPS detection in containers..."
    
    # Test HTTPS detection by simulating Cloudflare headers
    echo "   Testing with simulated Cloudflare headers..."
    
    for app in "${APPS[@]}"; do
        echo -n "      $app: "
        RESPONSE=$(docker exec "$app" curl -s -H "X-Forwarded-Proto: https" -H "X-Forwarded-For: 1.1.1.1" http://localhost:8000/health 2>/dev/null || echo "error")
        if [ "$RESPONSE" != "error" ]; then
            echo "✅ Responds to health check"
        else
            echo "❌ Health check failed"
        fi
    done
else
    echo "   ⚠️  Containers not running - cannot test HTTPS detection"
fi

echo ""
echo "📋 Configuration Summary"
echo "========================"

# Count successful configurations
MIDDLEWARE_COUNT=0
BOOTSTRAP_COUNT=0

for app in "${APPS[@]}"; do
    if [ -f "$app/app/Http/Middleware/TrustProxies.php" ]; then
        ((MIDDLEWARE_COUNT++))
    fi
    
    if [ -f "$app/bootstrap/app.php" ] && grep -q "trustProxies.*TrustProxies" "$app/bootstrap/app.php"; then
        ((BOOTSTRAP_COUNT++))
    fi
done

echo "✅ TrustProxies Middleware: $MIDDLEWARE_COUNT/3 applications configured"
echo "✅ Bootstrap Configuration: $BOOTSTRAP_COUNT/3 applications configured"

if [ "$MIDDLEWARE_COUNT" -eq 3 ] && [ "$BOOTSTRAP_COUNT" -eq 3 ]; then
    echo ""
    echo "🎉 TrustProxies Configuration Complete!"
    echo "   All applications are properly configured for HTTPS detection behind Cloudflare."
    echo ""
    echo "🔧 Next Steps:"
    echo "   1. Set TRUSTED_PROXIES=* in your .env file for development"
    echo "   2. Set SESSION_SECURE_COOKIE=true for HTTPS deployment"
    echo "   3. Restart containers: docker-compose restart"
    echo "   4. Test your SSL connection and forms"
else
    echo ""
    echo "⚠️  Configuration Incomplete"
    echo "   Some applications are missing TrustProxies configuration."
    echo "   Run this script again after fixing the issues."
fi

echo ""
echo "🆘 If you need to reconfigure:"
echo "   ./scripts/fix-https-csrf.sh    # Complete HTTPS and CSRF fix"
echo "   ./scripts/troubleshoot-ssl.sh  # Diagnose SSL connection issues"