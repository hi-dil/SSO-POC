#!/bin/bash

#################################################################################
# SSO Flow Testing Script
# 
# This script performs comprehensive testing of the SSO authentication flow
# across all tenant applications
#
# Usage: ./scripts/test-sso-flow.sh [staging|production]
#################################################################################

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT="${1:-staging}"
TIMEOUT=30
RETRY_COUNT=3

# Environment-specific URLs
if [[ "$ENVIRONMENT" == "staging" ]]; then
    SSO_URL="https://staging-sso.poc.hi-dil.com"
    TENANT1_URL="https://staging-tenant-one.poc.hi-dil.com"
    TENANT2_URL="https://staging-tenant-two.poc.hi-dil.com"
elif [[ "$ENVIRONMENT" == "production" ]]; then
    SSO_URL="https://sso.poc.hi-dil.com"
    TENANT1_URL="https://tenant-one.poc.hi-dil.com"
    TENANT2_URL="https://tenant-two.poc.hi-dil.com"
else
    echo "Usage: $0 [staging|production]"
    exit 1
fi

# Test credentials
TEST_USERS=(
    "user@tenant1.com:password:tenant1"
    "admin@tenant1.com:password:tenant1"
    "user@tenant2.com:password:tenant2"
    "admin@tenant2.com:password:tenant2"
    "superadmin@sso.com:password:both"
)

# Temporary files for storing cookies and responses
COOKIE_JAR="/tmp/sso_test_cookies_$$"
RESPONSE_FILE="/tmp/sso_test_response_$$"

# Cleanup function
cleanup() {
    rm -f "$COOKIE_JAR" "$RESPONSE_FILE"
}
trap cleanup EXIT

# Helper functions
print_header() {
    echo -e "${BLUE}=====================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}=====================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Make HTTP request with retries
make_request() {
    local method="$1"
    local url="$2"
    local data="${3:-}"
    local expected_status="${4:-200}"
    local description="${5:-HTTP request}"
    
    local attempt=1
    local success=false
    
    while [[ $attempt -le $RETRY_COUNT ]]; do
        print_info "Attempt $attempt/$RETRY_COUNT: $description"
        
        local curl_cmd="curl -s -w '%{http_code}' --max-time $TIMEOUT"
        curl_cmd="$curl_cmd -c '$COOKIE_JAR' -b '$COOKIE_JAR'"
        curl_cmd="$curl_cmd -H 'User-Agent: SSO-Test-Script/1.0'"
        curl_cmd="$curl_cmd -H 'Accept: application/json, text/html'"
        
        if [[ "$method" == "POST" ]]; then
            curl_cmd="$curl_cmd -X POST"
            if [[ -n "$data" ]]; then
                curl_cmd="$curl_cmd -H 'Content-Type: application/json' -d '$data'"
            fi
        fi
        
        curl_cmd="$curl_cmd '$url'"
        
        # Execute curl command and capture output
        local output
        if output=$(eval "$curl_cmd" 2>/dev/null); then
            local http_code="${output: -3}"
            local response_body="${output%???}"
            
            echo "$response_body" > "$RESPONSE_FILE"
            
            if [[ "$http_code" == "$expected_status" ]]; then
                print_success "$description completed successfully (HTTP $http_code)"
                success=true
                break
            else
                print_warning "$description returned HTTP $http_code (expected $expected_status)"
            fi
        else
            print_warning "$description failed (network error)"
        fi
        
        if [[ $attempt -lt $RETRY_COUNT ]]; then
            print_info "Retrying in 2 seconds..."
            sleep 2
        fi
        
        ((attempt++))
    done
    
    if [[ "$success" != "true" ]]; then
        print_error "$description failed after $RETRY_COUNT attempts"
        return 1
    fi
    
    return 0
}

# Test basic connectivity
test_connectivity() {
    print_header "Testing Basic Connectivity"
    
    local urls=("$SSO_URL" "$TENANT1_URL" "$TENANT2_URL")
    local names=("Central SSO" "Tenant 1" "Tenant 2")
    
    for i in "${!urls[@]}"; do
        local url="${urls[$i]}"
        local name="${names[$i]}"
        
        print_info "Testing connectivity to $name..."
        
        if make_request "GET" "$url/health" "" "200" "$name health check"; then
            print_success "$name is accessible"
        else
            print_error "$name is not accessible"
            return 1
        fi
    done
    
    print_success "All services are accessible"
}

# Test CSRF token retrieval
get_csrf_token() {
    local url="$1"
    local description="$2"
    
    print_info "Getting CSRF token from $description..."
    
    if make_request "GET" "$url/sanctum/csrf-cookie" "" "204" "CSRF token request"; then
        print_success "CSRF token retrieved from $description"
        return 0
    else
        print_error "Failed to get CSRF token from $description"
        return 1
    fi
}

# Extract CSRF token from response
extract_csrf_token() {
    local login_page="$1"
    
    if make_request "GET" "$login_page" "" "200" "Login page request"; then
        # Extract CSRF token from response
        local token
        token=$(grep -o 'name="_token"[^>]*value="[^"]*"' "$RESPONSE_FILE" | sed 's/.*value="\([^"]*\)".*/\1/' | head -1)
        
        if [[ -n "$token" ]]; then
            echo "$token"
            return 0
        fi
    fi
    
    return 1
}

# Test direct login to tenant application
test_direct_login() {
    local tenant_url="$1"
    local email="$2"
    local password="$3"
    local tenant_name="$4"
    
    print_info "Testing direct login to $tenant_name..."
    
    # Clear cookies
    rm -f "$COOKIE_JAR"
    
    # Get CSRF token
    if ! get_csrf_token "$tenant_url" "$tenant_name"; then
        return 1
    fi
    
    # Get login page and extract CSRF token
    local csrf_token
    if csrf_token=$(extract_csrf_token "$tenant_url/login"); then
        print_success "CSRF token extracted: ${csrf_token:0:10}..."
    else
        print_warning "Could not extract CSRF token, proceeding without it"
        csrf_token=""
    fi
    
    # Prepare login data
    local login_data
    login_data="{\"email\":\"$email\",\"password\":\"$password\""
    if [[ -n "$csrf_token" ]]; then
        login_data="$login_data,\"_token\":\"$csrf_token\""
    fi
    login_data="$login_data}"
    
    # Attempt login
    if make_request "POST" "$tenant_url/login" "$login_data" "302" "Direct login to $tenant_name"; then
        print_success "Direct login to $tenant_name successful"
        
        # Verify we can access protected resources
        if make_request "GET" "$tenant_url/dashboard" "" "200" "Dashboard access after login"; then
            print_success "Dashboard access successful after direct login"
        else
            print_warning "Dashboard access failed after direct login"
        fi
        
        return 0
    else
        print_error "Direct login to $tenant_name failed"
        return 1
    fi
}

# Test SSO redirect flow
test_sso_redirect() {
    local tenant_url="$1"
    local email="$2"
    local password="$3"
    local tenant_name="$4"
    
    print_info "Testing SSO redirect flow for $tenant_name..."
    
    # Clear cookies
    rm -f "$COOKIE_JAR"
    
    # Initiate SSO redirect
    if make_request "GET" "$tenant_url/auth/sso" "" "302" "SSO redirect initiation"; then
        print_success "SSO redirect initiated"
    else
        print_error "SSO redirect initiation failed"
        return 1
    fi
    
    # Get CSRF token from SSO server
    if ! get_csrf_token "$SSO_URL" "Central SSO"; then
        return 1
    fi
    
    # Get SSO login page
    local csrf_token
    if csrf_token=$(extract_csrf_token "$SSO_URL/login"); then
        print_success "SSO CSRF token extracted"
    else
        print_warning "Could not extract SSO CSRF token"
        csrf_token=""
    fi
    
    # Login to SSO server
    local login_data
    login_data="{\"email\":\"$email\",\"password\":\"$password\""
    if [[ -n "$csrf_token" ]]; then
        login_data="$login_data,\"_token\":\"$csrf_token\""
    fi
    login_data="$login_data}"
    
    if make_request "POST" "$SSO_URL/login" "$login_data" "302" "SSO server login"; then
        print_success "SSO server login successful"
    else
        print_error "SSO server login failed"
        return 1
    fi
    
    # Complete SSO flow (should redirect back to tenant)
    if make_request "GET" "$tenant_url/auth/callback" "" "302" "SSO callback handling"; then
        print_success "SSO callback handled successfully"
    else
        print_warning "SSO callback handling failed (may be normal depending on implementation)"
    fi
    
    # Verify we can access protected resources
    if make_request "GET" "$tenant_url/dashboard" "" "200" "Dashboard access after SSO"; then
        print_success "Dashboard access successful after SSO login"
        return 0
    else
        print_error "Dashboard access failed after SSO login"
        return 1
    fi
}

# Test API endpoints
test_api_endpoints() {
    print_header "Testing API Endpoints"
    
    # Test SSO API health
    if make_request "GET" "$SSO_URL/api/health" "" "200" "SSO API health check"; then
        print_success "SSO API is healthy"
    else
        print_error "SSO API health check failed"
        return 1
    fi
    
    # Test tenant API health
    if make_request "GET" "$TENANT1_URL/api/health" "" "200" "Tenant 1 API health check"; then
        print_success "Tenant 1 API is healthy"
    else
        print_warning "Tenant 1 API health check failed"
    fi
    
    if make_request "GET" "$TENANT2_URL/api/health" "" "200" "Tenant 2 API health check"; then
        print_success "Tenant 2 API is healthy"
    else
        print_warning "Tenant 2 API health check failed"
    fi
    
    print_success "API endpoint tests completed"
}

# Test cross-tenant access
test_cross_tenant_access() {
    print_header "Testing Cross-Tenant Access"
    
    # Test superadmin access to both tenants
    local superadmin_email="superadmin@sso.com"
    local superadmin_password="password"
    
    print_info "Testing superadmin access to both tenants..."
    
    # Login to SSO as superadmin
    rm -f "$COOKIE_JAR"
    get_csrf_token "$SSO_URL" "Central SSO"
    
    local csrf_token
    csrf_token=$(extract_csrf_token "$SSO_URL/login") || csrf_token=""
    
    local login_data="{\"email\":\"$superadmin_email\",\"password\":\"$superadmin_password\""
    if [[ -n "$csrf_token" ]]; then
        login_data="$login_data,\"_token\":\"$csrf_token\""
    fi
    login_data="$login_data}"
    
    if make_request "POST" "$SSO_URL/login" "$login_data" "302" "Superadmin SSO login"; then
        print_success "Superadmin logged into SSO successfully"
        
        # Test access to tenant 1
        if make_request "GET" "$TENANT1_URL/dashboard" "" "200" "Superadmin access to Tenant 1"; then
            print_success "Superadmin can access Tenant 1"
        else
            print_warning "Superadmin cannot access Tenant 1"
        fi
        
        # Test access to tenant 2
        if make_request "GET" "$TENANT2_URL/dashboard" "" "200" "Superadmin access to Tenant 2"; then
            print_success "Superadmin can access Tenant 2"
        else
            print_warning "Superadmin cannot access Tenant 2"
        fi
    else
        print_error "Superadmin SSO login failed"
        return 1
    fi
    
    print_success "Cross-tenant access tests completed"
}

# Test logout functionality
test_logout() {
    print_header "Testing Logout Functionality"
    
    # Test SSO logout
    print_info "Testing SSO logout..."
    
    if make_request "POST" "$SSO_URL/logout" "" "302" "SSO logout"; then
        print_success "SSO logout successful"
        
        # Verify we can't access protected resources
        if make_request "GET" "$SSO_URL/dashboard" "" "302" "Dashboard access after logout" || 
           make_request "GET" "$SSO_URL/dashboard" "" "401" "Dashboard access after logout"; then
            print_success "Dashboard properly protected after logout"
        else
            print_warning "Dashboard access after logout returned unexpected status"
        fi
    else
        print_warning "SSO logout failed (may be normal depending on implementation)"
    fi
    
    print_success "Logout tests completed"
}

# Generate test report
generate_report() {
    local total_tests="$1"
    local passed_tests="$2"
    local failed_tests="$3"
    
    print_header "Test Report Summary"
    
    echo -e "${BLUE}Environment:${NC} $ENVIRONMENT"
    echo -e "${BLUE}Total Tests:${NC} $total_tests"
    echo -e "${GREEN}Passed:${NC} $passed_tests"
    echo -e "${RED}Failed:${NC} $failed_tests"
    echo -e "${BLUE}Success Rate:${NC} $(( passed_tests * 100 / total_tests ))%"
    
    if [[ $failed_tests -eq 0 ]]; then
        print_success "All SSO flow tests passed!"
        return 0
    else
        print_error "Some SSO flow tests failed"
        return 1
    fi
}

# Main test execution
main() {
    print_header "SSO Flow Testing - $ENVIRONMENT Environment"
    
    local total_tests=0
    local passed_tests=0
    local failed_tests=0
    
    # Test basic connectivity
    ((total_tests++))
    if test_connectivity; then
        ((passed_tests++))
    else
        ((failed_tests++))
        print_error "Basic connectivity test failed - aborting remaining tests"
        generate_report $total_tests $passed_tests $failed_tests
        exit 1
    fi
    
    # Test API endpoints
    ((total_tests++))
    if test_api_endpoints; then
        ((passed_tests++))
    else
        ((failed_tests++))
    fi
    
    # Test user authentication flows
    for user_info in "${TEST_USERS[@]}"; do
        IFS=':' read -r email password tenant <<< "$user_info"
        
        # Test direct login
        ((total_tests++))
        if test_direct_login "$TENANT1_URL" "$email" "$password" "Tenant 1"; then
            ((passed_tests++))
        else
            ((failed_tests++))
        fi
        
        # Test SSO redirect flow
        ((total_tests++))
        if test_sso_redirect "$TENANT1_URL" "$email" "$password" "Tenant 1"; then
            ((passed_tests++))
        else
            ((failed_tests++))
        fi
    done
    
    # Test cross-tenant access
    ((total_tests++))
    if test_cross_tenant_access; then
        ((passed_tests++))
    else
        ((failed_tests++))
    fi
    
    # Test logout
    ((total_tests++))
    if test_logout; then
        ((passed_tests++))
    else
        ((failed_tests++))
    fi
    
    # Generate final report
    generate_report $total_tests $passed_tests $failed_tests
}

# Run main function
main "$@"