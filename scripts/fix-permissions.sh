#!/bin/bash

# =============================================================================
# SSO Permission Fix Script
# =============================================================================
# Fixes Docker bind mount permission issues for Laravel applications
# Run this script from the PROJECT ROOT DIRECTORY on the HOST machine

set -e

echo "🔧 SSO Permission Fix Script"
echo "================================="

# Check if we're in the right directory
if [ ! -f "docker-compose.yml" ]; then
    echo "❌ Error: docker-compose.yml not found."
    echo "   Please run this script from the project root directory."
    exit 1
fi

echo "📁 Checking project structure..."

# Check for Laravel applications
APPS=()
for app in central-sso tenant1-app tenant2-app; do
    if [ -d "$app" ]; then
        APPS+=("$app")
        echo "   ✅ Found $app"
    else
        echo "   ⚠️  Warning: $app directory not found"
    fi
done

if [ ${#APPS[@]} -eq 0 ]; then
    echo "❌ Error: No Laravel applications found."
    echo "   Expected directories: central-sso, tenant1-app, tenant2-app"
    exit 1
fi

echo ""
echo "🔒 Fixing permissions for Docker bind mounts..."
echo "   This fixes the common 500 error caused by permission issues"
echo ""

# Function to fix permissions for an app
fix_app_permissions() {
    local app=$1
    echo "📂 Processing $app..."
    
    # Create required directories if they don't exist
    echo "   Creating storage directories..."
    mkdir -p "$app/storage/app/public"
    mkdir -p "$app/storage/framework/cache/data"
    mkdir -p "$app/storage/framework/sessions"
    mkdir -p "$app/storage/framework/testing" 
    mkdir -p "$app/storage/framework/views"
    mkdir -p "$app/storage/logs"
    mkdir -p "$app/bootstrap/cache"
    
    # Create log file if it doesn't exist
    touch "$app/storage/logs/laravel.log"
    
    echo "   Setting ownership to www-data (UID 33)..."
    # Set ownership to www-data (UID 33, GID 33)
    sudo chown -R 33:33 "$app/storage"
    sudo chown -R 33:33 "$app/bootstrap/cache"
    
    echo "   Setting permissions..."
    # Set proper permissions
    sudo chmod -R 775 "$app/storage"
    sudo chmod -R 775 "$app/bootstrap/cache"
    
    # Ensure log file is writable
    sudo chmod 664 "$app/storage/logs/laravel.log"
    
    echo "   ✅ Fixed permissions for $app"
}

# Fix permissions for each app
for app in "${APPS[@]}"; do
    fix_app_permissions "$app"
done

echo ""
echo "🐳 Restarting Docker containers..."

# Check if containers are running
if docker-compose ps -q > /dev/null 2>&1; then
    docker-compose restart
    echo "   ✅ Containers restarted"
else
    echo "   ℹ️  No running containers found. Start with: docker-compose up -d"
fi

echo ""
echo "🔍 Verifying the fix..."

# Test if we can write to storage directories
for app in "${APPS[@]}"; do
    if [ -w "$app/storage/logs/laravel.log" ]; then
        echo "   ✅ $app storage is writable"
    else
        echo "   ❌ $app storage may still have permission issues"
    fi
done

echo ""
echo "✅ Permission fix complete!"
echo ""
echo "📋 What was fixed:"
echo "   • Set ownership of storage directories to www-data (UID 33)"
echo "   • Set proper permissions (775) for Laravel to write files"
echo "   • Created missing storage directory structure"
echo "   • Made log files writable by the web server"
echo ""
echo "🎯 Next steps:"
echo "   1. Test your application by visiting the URL"
echo "   2. If you still see 500 errors, check logs with:"
echo "      docker exec central-sso tail -f /var/www/html/storage/logs/laravel.log"
echo "   3. Check container status with: docker-compose ps"
echo ""
echo "💡 Why this was needed:"
echo "   Docker bind mounts preserve host file ownership. Laravel needs"
echo "   www-data (UID 33) to write to storage directories, but your host"
echo "   files were owned by your user account."
echo ""
echo "🔗 For more help, see:"
echo "   docs/guides/simple-home-deployment.md (Troubleshooting section)"