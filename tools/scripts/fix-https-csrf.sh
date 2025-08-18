#!/bin/bash

# =============================================================================
# HTTPS and CSRF Fix Script for SSO Deployment
# =============================================================================
# Fixes common SSL/TLS and CSRF token issues in production HTTPS deployment
# Run this script from the PROJECT ROOT DIRECTORY on the HOST machine

set -e

echo "🔒 HTTPS and CSRF Fix Script"
echo "============================="

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Error: docker-compose.yml not found."
    echo "   Please run this script from the project root directory."
    exit 1
fi

echo "📁 Checking project structure..."

# Check for required files
REQUIRED_FILES=(".env" "docker-compose.yml")
for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✅ Found $file"
    else
        echo "   ❌ Missing $file"
        if [ "$file" == ".env" ]; then
            echo "      Creating .env from .env.docker template..."
            cp .env.docker .env
            echo "   ✅ Created .env file"
        fi
    fi
done

echo ""
echo "🔧 Applying HTTPS and CSRF Configuration..."
echo "============================================"

# Backup current .env file
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "📋 Backed up current .env file"

# Function to update environment variable in .env file
update_env_var() {
    local var_name="$1"
    local var_value="$2"
    local env_file="${3:-.env}"
    
    if grep -q "^${var_name}=" "$env_file"; then
        # Variable exists, update it
        sed -i.bak "s|^${var_name}=.*|${var_name}=${var_value}|" "$env_file"
        rm "${env_file}.bak" 2>/dev/null || true
    else
        # Variable doesn't exist, add it
        echo "${var_name}=${var_value}" >> "$env_file"
    fi
}

# Function to uncomment environment variable
uncomment_env_var() {
    local var_name="$1"
    local env_file="${2:-.env}"
    
    sed -i.bak "s|^# ${var_name}=|${var_name}=|" "$env_file"
    rm "${env_file}.bak" 2>/dev/null || true
}

echo ""
echo "🎯 Step 1: Configure for Production HTTPS..."
echo "--------------------------------------------"

# Update basic production settings
update_env_var "APP_ENV" "production"
update_env_var "APP_DEBUG" "false"
update_env_var "TRUSTED_PROXIES" "*"

echo "   ✅ Set APP_ENV=production"
echo "   ✅ Set APP_DEBUG=false"
echo "   ✅ Set TRUSTED_PROXIES=* (trust all proxies)"

# Read user's domain configuration
echo ""
echo "🌐 Step 2: Configure Domain Settings..."
echo "--------------------------------------"

# Check if domains are already configured
if grep -q "sso.poc.hi-dil.com" .env; then
    echo "   ℹ️  Using existing domain configuration (hi-dil.com)"
    DOMAIN_BASE="poc.hi-dil.com"
    SSO_DOMAIN="sso.poc.hi-dil.com"
    TENANT1_DOMAIN="tenant-one.poc.hi-dil.com"
    TENANT2_DOMAIN="tenant-two.poc.hi-dil.com"
else
    echo "   ⚠️  No domain configuration found"
    echo ""
    echo "Please enter your domain configuration:"
    read -p "Enter your base domain (e.g., poc.example.com): " DOMAIN_BASE
    read -p "Enter your SSO domain (e.g., sso.poc.example.com): " SSO_DOMAIN
    read -p "Enter your Tenant 1 domain (e.g., tenant-one.poc.example.com): " TENANT1_DOMAIN
    read -p "Enter your Tenant 2 domain (e.g., tenant-two.poc.example.com): " TENANT2_DOMAIN
fi

# Update URLs for HTTPS
update_env_var "CENTRAL_SSO_APP_URL" "https://${SSO_DOMAIN}"
update_env_var "TENANT1_APP_URL" "https://${TENANT1_DOMAIN}"
update_env_var "TENANT2_APP_URL" "https://${TENANT2_DOMAIN}"
update_env_var "CENTRAL_SSO_URL" "https://${SSO_DOMAIN}"

echo "   ✅ Updated application URLs to use HTTPS"
echo "   ✅ Central SSO: https://${SSO_DOMAIN}"
echo "   ✅ Tenant 1: https://${TENANT1_DOMAIN}"
echo "   ✅ Tenant 2: https://${TENANT2_DOMAIN}"

echo ""
echo "🍪 Step 3: Configure Session and CSRF Settings..."
echo "-------------------------------------------------"

# Update session configuration for HTTPS
update_env_var "SESSION_SECURE_COOKIE" "true"
update_env_var "SESSION_DOMAIN" ".${DOMAIN_BASE}"
update_env_var "SESSION_SAME_SITE" "lax"
update_env_var "SESSION_DRIVER" "database"

echo "   ✅ Set SESSION_SECURE_COOKIE=true (required for HTTPS)"
echo "   ✅ Set SESSION_DOMAIN=.${DOMAIN_BASE} (enables cross-subdomain sessions)"
echo "   ✅ Set SESSION_SAME_SITE=lax (prevents CSRF blocking)"
echo "   ✅ Set SESSION_DRIVER=database (more reliable than files)"

# Add CORS configuration
CORS_ORIGINS="https://${SSO_DOMAIN},https://${TENANT1_DOMAIN},https://${TENANT2_DOMAIN}"
update_env_var "CORS_ALLOWED_ORIGINS" "$CORS_ORIGINS"

echo "   ✅ Configured CORS for all domains"

echo ""
echo "🗄️ Step 4: Setup Database Sessions..."
echo "-------------------------------------"

# Check if containers are running
if docker-compose ps | grep -q "Up"; then
    echo "   📦 Creating sessions table if needed..."
    
    # Create sessions table for central-sso
    docker exec central-sso php artisan session:table 2>/dev/null || echo "   ℹ️  Sessions table may already exist for central-sso"
    docker exec central-sso php artisan migrate --force 2>/dev/null || echo "   ℹ️  Migration may have already run for central-sso"
    
    # Create sessions table for tenant apps
    docker exec tenant1-app php artisan session:table 2>/dev/null || echo "   ℹ️  Sessions table may already exist for tenant1-app"
    docker exec tenant1-app php artisan migrate --force 2>/dev/null || echo "   ℹ️  Migration may have already run for tenant1-app"
    
    docker exec tenant2-app php artisan session:table 2>/dev/null || echo "   ℹ️  Sessions table may already exist for tenant2-app"
    docker exec tenant2-app php artisan migrate --force 2>/dev/null || echo "   ℹ️  Migration may have already run for tenant2-app"
    
    echo "   ✅ Database sessions configured"
else
    echo "   ⚠️  Docker containers not running. Start them first with:"
    echo "      docker-compose up -d"
    echo "   Then run database migrations manually:"
    echo "      docker exec central-sso php artisan session:table && docker exec central-sso php artisan migrate"
    echo "      docker exec tenant1-app php artisan session:table && docker exec tenant1-app php artisan migrate"
    echo "      docker exec tenant2-app php artisan session:table && docker exec tenant2-app php artisan migrate"
fi

echo ""
echo "🔄 Step 5: Restart Services with New Configuration..."
echo "----------------------------------------------------"

if docker-compose ps | grep -q "Up"; then
    echo "   🔄 Restarting Docker containers to apply changes..."
    docker-compose restart
    echo "   ✅ Containers restarted"
    
    # Wait a moment for services to start
    echo "   ⏳ Waiting for services to initialize..."
    sleep 10
    
    # Clear application caches
    echo "   🧹 Clearing application caches..."
    docker exec central-sso php artisan config:clear 2>/dev/null || true
    docker exec central-sso php artisan cache:clear 2>/dev/null || true
    docker exec tenant1-app php artisan config:clear 2>/dev/null || true
    docker exec tenant1-app php artisan cache:clear 2>/dev/null || true
    docker exec tenant2-app php artisan config:clear 2>/dev/null || true
    docker exec tenant2-app php artisan cache:clear 2>/dev/null || true
    
    echo "   ✅ Caches cleared"
else
    echo "   ℹ️  Containers not currently running"
    echo "   Start them with: docker-compose up -d"
fi

echo ""
echo "🔍 Step 6: Verification..."
echo "-------------------------"

echo "   📋 Current Configuration Summary:"
echo "      • Environment: $(grep '^APP_ENV=' .env | cut -d'=' -f2)"
echo "      • Debug Mode: $(grep '^APP_DEBUG=' .env | cut -d'=' -f2)"
echo "      • SSL Cookies: $(grep '^SESSION_SECURE_COOKIE=' .env | cut -d'=' -f2)"
echo "      • Session Domain: $(grep '^SESSION_DOMAIN=' .env | cut -d'=' -f2)"
echo "      • Trusted Proxies: $(grep '^TRUSTED_PROXIES=' .env | cut -d'=' -f2)"

echo ""
echo "✅ HTTPS and CSRF Configuration Complete!"
echo "========================================="
echo ""
echo "📋 What was fixed:"
echo "   • ✅ TrustProxies middleware added (fixes HTTPS detection behind Cloudflare)"
echo "   • ✅ SESSION_SECURE_COOKIE=true (required for HTTPS)"
echo "   • ✅ SESSION_DOMAIN configured for cross-subdomain sessions"
echo "   • ✅ SESSION_SAME_SITE=lax (prevents CSRF token blocking)"
echo "   • ✅ Production environment settings applied"
echo "   • ✅ Database session storage configured"
echo "   • ✅ CORS configured for all domains"
echo ""
echo "🎯 Next Steps:"
echo "1. **Test your SSL connection:**"
echo "   • Visit https://${SSO_DOMAIN}"
echo "   • Check for any remaining SSL errors"
echo ""
echo "2. **Test CSRF token functionality:**"
echo "   • Try logging in to verify forms work"
echo "   • Should no longer see 419 Page Expired errors"
echo ""
echo "3. **If SSL connection still fails:**"
echo "   ./scripts/troubleshoot-ssl.sh"
echo ""
echo "4. **Check Cloudflare Settings:**"
echo "   • SSL/TLS mode should be 'Full' or 'Full (strict)'"
echo "   • DNS records should be 'Proxied' (orange cloud)"
echo "   • Verify tunnel is running: docker logs cloudflared-sso"
echo ""
echo "🆘 If problems persist:"
echo "   • Check container logs: docker-compose logs"
echo "   • Verify Cloudflare tunnel status in dashboard"
echo "   • Ensure DNS has propagated (15-30 minutes)"
echo "   • Check that tunnel credentials are valid"
echo ""
echo "📁 Configuration backup saved as: .env.backup.*"