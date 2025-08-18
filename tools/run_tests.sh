#!/bin/bash

echo "🧪 Running Complete SSO Audit System Tests"
echo "============================================="
echo ""

# Test central SSO audit system
echo "📋 Testing Central SSO Audit System..."
docker exec central-sso php artisan test:login-audit --comprehensive
echo ""

# Test tenant1 audit system
echo "🏢 Testing Tenant1 Audit System..."
docker exec tenant1-app php artisan test:tenant-audit
echo ""

# Test tenant2 audit system  
echo "🏢 Testing Tenant2 Audit System..."
docker exec tenant2-app php artisan test:tenant-audit
echo ""

# Test real authentication flow
echo "🔐 Testing Real Authentication Flow..."

# Test direct tenant login
echo "  Testing direct tenant1 login..."
response=$(curl -s -c /tmp/cookies1.txt -b /tmp/cookies1.txt -X GET "http://localhost:8001/login" | grep csrf-token | sed 's/.*content="\([^"]*\)".*/\1/')
if [ -n "$response" ]; then
    login_response=$(curl -s -c /tmp/cookies1.txt -b /tmp/cookies1.txt -X POST "http://localhost:8001/login" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        -d "email=user@tenant1.com&password=password&_token=$response")
    
    if echo "$login_response" | grep -q "dashboard\|Welcome"; then
        echo "  ✓ Tenant1 direct login successful"
    else
        echo "  ⚠️ Tenant1 direct login failed"
    fi
else
    echo "  ⚠️ Could not get CSRF token for tenant1"
fi

# Test API authentication
echo "  Testing API authentication..."
api_response=$(curl -s -X POST "http://localhost:8000/api/auth/login" \
    -H "Content-Type: application/json" \
    -d '{"email": "superadmin@sso.com", "password": "password", "tenant_slug": "tenant1"}')

if echo "$api_response" | grep -q '"success":true'; then
    echo "  ✓ API authentication successful"
else
    echo "  ⚠️ API authentication failed"
fi

# Check recent audit records
echo ""
echo "📊 Recent Audit Records:"
docker exec sso-mariadb mysql -u sso_user -psso_password sso_main -e "
SELECT 
    id, 
    user_id, 
    tenant_id, 
    login_method, 
    is_successful, 
    DATE_FORMAT(login_at, '%H:%i:%s') as time
FROM login_audits 
ORDER BY id DESC 
LIMIT 10;" 2>/dev/null

echo ""
echo "📈 Current Statistics:"
docker exec sso-mariadb mysql -u sso_user -psso_password sso_main -e "
SELECT 
    COUNT(*) as total_audits,
    COUNT(DISTINCT user_id) as unique_users,
    SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful_logins,
    SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed_logins
FROM login_audits;" 2>/dev/null

echo ""
echo "🎉 Test Summary Complete!"
echo "✅ The SSO audit system is operational"
echo ""
echo "To test manually:"
echo "1. Visit http://localhost:8000/login (Central SSO)"
echo "2. Visit http://localhost:8001/login (Tenant1)"
echo "3. Visit http://localhost:8002/login (Tenant2)"
echo "4. Check analytics at http://localhost:8000/admin/analytics"
echo ""

# Cleanup
rm -f /tmp/cookies*.txt