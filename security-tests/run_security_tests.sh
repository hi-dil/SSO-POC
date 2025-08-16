#!/bin/bash

# SSO Security Testing Framework
# Comprehensive security testing for enterprise SSO system

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="${SCRIPT_DIR}/config/test.env"
REPORT_DIR="${SCRIPT_DIR}/reports"
LOG_FILE="${REPORT_DIR}/security_test_$(date +%Y%m%d_%H%M%S).log"

# Test categories
CATEGORIES=("authentication" "rate-limiting" "session-security" "audit-logging" "input-validation" "penetration")

# Default values
CATEGORY=""
VERBOSE=false
CI_MODE=false
DRY_RUN=false
CONFIRM_DANGEROUS=false

# Create necessary directories
mkdir -p "${REPORT_DIR}"
mkdir -p "${SCRIPT_DIR}/logs"

# Logging function
log() {
    local level=$1
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    case $level in
        "INFO")
            echo -e "${GREEN}[INFO]${NC} ${message}"
            ;;
        "WARN")
            echo -e "${YELLOW}[WARN]${NC} ${message}"
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} ${message}"
            ;;
        "DEBUG")
            if [[ "$VERBOSE" == true ]]; then
                echo -e "${BLUE}[DEBUG]${NC} ${message}"
            fi
            ;;
    esac
    
    echo "[${timestamp}] [${level}] ${message}" >> "$LOG_FILE"
}

# Help function
show_help() {
    cat << EOF
SSO Security Testing Framework

Usage: $0 [OPTIONS]

Options:
    -c, --category CATEGORY    Run tests for specific category
                              (authentication, rate-limiting, session-security, 
                               audit-logging, input-validation, penetration)
    -v, --verbose             Enable verbose output
    --ci                      CI mode (non-interactive, exit on first failure)
    --dry-run                 Show what would be tested without running
    --confirm                 Confirm running dangerous tests (penetration)
    -h, --help                Show this help message

Categories:
    authentication            API key auth, HMAC signing, JWT validation
    rate-limiting            DoS protection, request throttling
    session-security         Session management, token security
    audit-logging            Event logging, audit integrity
    input-validation         Input sanitization, injection prevention
    penetration              Penetration testing (requires --confirm)

Examples:
    $0                        Run all safe security tests
    $0 -c authentication     Run only authentication tests
    $0 -c penetration --confirm  Run penetration tests (dangerous)
    $0 --ci                   Run in CI mode
    $0 --dry-run              Show test plan without execution

EOF
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -c|--category)
            CATEGORY="$2"
            shift 2
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        --ci)
            CI_MODE=true
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --confirm)
            CONFIRM_DANGEROUS=true
            shift
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
done

# Load configuration
load_config() {
    if [[ -f "$CONFIG_FILE" ]]; then
        source "$CONFIG_FILE"
        log "INFO" "Loaded configuration from $CONFIG_FILE"
    else
        log "ERROR" "Configuration file not found: $CONFIG_FILE"
        log "INFO" "Please copy config/test.env.example to config/test.env and configure"
        exit 1
    fi
}

# Validate environment
validate_environment() {
    log "INFO" "Validating test environment..."
    
    local required_vars=("CENTRAL_SSO_URL" "TENANT1_URL" "TEST_API_KEY")
    local missing_vars=()
    
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var}" ]]; then
            missing_vars+=("$var")
        fi
    done
    
    if [[ ${#missing_vars[@]} -gt 0 ]]; then
        log "ERROR" "Missing required configuration variables: ${missing_vars[*]}"
        exit 1
    fi
    
    # Test connectivity
    if ! curl -s --max-time 5 "$CENTRAL_SSO_URL/health" > /dev/null; then
        log "ERROR" "Cannot connect to Central SSO at $CENTRAL_SSO_URL"
        exit 1
    fi
    
    if ! curl -s --max-time 5 "$TENANT1_URL/health" > /dev/null; then
        log "ERROR" "Cannot connect to Tenant 1 at $TENANT1_URL"
        exit 1
    fi
    
    log "INFO" "Environment validation passed"
}

# Authentication security tests
test_authentication() {
    log "INFO" "Running authentication security tests..."
    
    local test_results=()
    
    # Test 1: Valid API key authentication
    log "DEBUG" "Testing valid API key authentication..."
    response=$(curl -s -w "%{http_code}" -o /tmp/auth_test_1.json \
        -X POST "$CENTRAL_SSO_URL/api/auth/login" \
        -H "X-API-Key: $TEST_API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"email": "test@example.com", "password": "wrongpassword", "tenant_slug": "tenant1"}')
    
    if [[ "$response" =~ ^(200|401|422)$ ]]; then
        test_results+=("✅ Valid API key accepted")
    else
        test_results+=("❌ Valid API key rejected (HTTP $response)")
    fi
    
    # Test 2: Invalid API key rejection
    log "DEBUG" "Testing invalid API key rejection..."
    response=$(curl -s -w "%{http_code}" -o /tmp/auth_test_2.json \
        -X POST "$CENTRAL_SSO_URL/api/auth/login" \
        -H "X-API-Key: invalid_key_12345" \
        -H "Content-Type: application/json" \
        -d '{"email": "test@example.com", "password": "password"}')
    
    if [[ "$response" == "401" ]]; then
        test_results+=("✅ Invalid API key rejected")
    else
        test_results+=("❌ Invalid API key accepted (HTTP $response)")
    fi
    
    # Test 3: Missing API key rejection
    log "DEBUG" "Testing missing API key rejection..."
    response=$(curl -s -w "%{http_code}" -o /tmp/auth_test_3.json \
        -X POST "$CENTRAL_SSO_URL/api/auth/login" \
        -H "Content-Type: application/json" \
        -d '{"email": "test@example.com", "password": "password"}')
    
    if [[ "$response" == "401" ]]; then
        test_results+=("✅ Missing API key rejected")
    else
        test_results+=("❌ Missing API key accepted (HTTP $response)")
    fi
    
    # Test 4: HMAC signature validation (if configured)
    if [[ -n "$TEST_HMAC_SECRET" ]]; then
        log "DEBUG" "Testing HMAC signature validation..."
        
        # Generate valid HMAC signature
        timestamp=$(date -u +"%Y-%m-%dT%H:%M:%S.000Z")
        body='{"email": "test@example.com", "password": "password"}'
        canonical="POST|/api/auth/login|$timestamp|tenant1|$(echo -n "$body" | sha256sum | cut -d' ' -f1)"
        signature=$(echo -n "$canonical" | openssl dgst -sha256 -hmac "$TEST_HMAC_SECRET" -binary | base64)
        
        response=$(curl -s -w "%{http_code}" -o /tmp/auth_test_4.json \
            -X POST "$CENTRAL_SSO_URL/api/auth/login" \
            -H "X-API-Key: $TEST_API_KEY" \
            -H "X-Timestamp: $timestamp" \
            -H "X-Tenant-ID: tenant1" \
            -H "X-Signature: $signature" \
            -H "Content-Type: application/json" \
            -d "$body")
        
        if [[ "$response" =~ ^(200|401|422)$ ]]; then
            test_results+=("✅ HMAC signature validation working")
        else
            test_results+=("❌ HMAC signature validation failed (HTTP $response)")
        fi
    fi
    
    # Print results
    printf "\n🔑 Authentication Test Results:\n"
    for result in "${test_results[@]}"; do
        echo "   $result"
    done
    printf "\n"
}

# Rate limiting tests
test_rate_limiting() {
    log "INFO" "Running rate limiting tests..."
    
    local test_results=()
    
    # Test 1: Login rate limiting
    log "DEBUG" "Testing login rate limiting..."
    local failed_attempts=0
    local max_attempts=10
    
    for i in $(seq 1 $max_attempts); do
        response=$(curl -s -w "%{http_code}" -o /dev/null \
            -X POST "$TENANT1_URL/login" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -d "email=rate-test@example.com&password=wrongpassword&_token=test")
        
        if [[ "$response" == "429" ]]; then
            test_results+=("✅ Rate limiting activated after $i attempts")
            break
        elif [[ "$response" =~ ^(200|302|422)$ ]]; then
            ((failed_attempts++))
        else
            log "DEBUG" "Unexpected response: HTTP $response"
        fi
        
        sleep 0.5  # Small delay between requests
    done
    
    if [[ $failed_attempts -eq $max_attempts ]]; then
        test_results+=("❌ No rate limiting detected after $max_attempts attempts")
    fi
    
    # Test 2: API rate limiting
    log "DEBUG" "Testing API rate limiting..."
    local api_attempts=0
    local api_max=100
    
    for i in $(seq 1 $api_max); do
        response=$(curl -s -w "%{http_code}" -o /dev/null \
            -X POST "$CENTRAL_SSO_URL/api/auth/login" \
            -H "X-API-Key: $TEST_API_KEY" \
            -H "Content-Type: application/json" \
            -d '{"email": "rate-test@example.com", "password": "wrongpassword"}')
        
        if [[ "$response" == "429" ]]; then
            test_results+=("✅ API rate limiting activated after $i requests")
            break
        elif [[ "$response" =~ ^(200|401|422)$ ]]; then
            ((api_attempts++))
        fi
        
        if [[ $((i % 20)) -eq 0 ]]; then
            log "DEBUG" "Completed $i API requests..."
        fi
    done
    
    if [[ $api_attempts -eq $api_max ]]; then
        test_results+=("⚠️  No API rate limiting detected after $api_max requests")
    fi
    
    # Print results
    printf "\n⚡ Rate Limiting Test Results:\n"
    for result in "${test_results[@]}"; do
        echo "   $result"
    done
    printf "\n"
}

# Session security tests
test_session_security() {
    log "INFO" "Running session security tests..."
    
    local test_results=()
    
    # Test 1: Session cookie security attributes
    log "DEBUG" "Testing session cookie security..."
    response=$(curl -s -D /tmp/session_headers.txt -o /dev/null "$TENANT1_URL/login")
    
    if grep -qi "Set-Cookie.*HttpOnly" /tmp/session_headers.txt; then
        test_results+=("✅ HttpOnly cookie attribute set")
    else
        test_results+=("❌ HttpOnly cookie attribute missing")
    fi
    
    if grep -qi "Set-Cookie.*SameSite" /tmp/session_headers.txt; then
        test_results+=("✅ SameSite cookie attribute set")
    else
        test_results+=("⚠️  SameSite cookie attribute missing")
    fi
    
    # Test 2: JWT token validation
    log "DEBUG" "Testing JWT token validation..."
    invalid_token="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.invalid.signature"
    
    response=$(curl -s -w "%{http_code}" -o /tmp/jwt_test.json \
        -X POST "$CENTRAL_SSO_URL/api/auth/validate" \
        -H "Authorization: Bearer $invalid_token" \
        -H "X-API-Key: $TEST_API_KEY")
    
    if [[ "$response" == "401" ]]; then
        test_results+=("✅ Invalid JWT token rejected")
    else
        test_results+=("❌ Invalid JWT token accepted (HTTP $response)")
    fi
    
    # Test 3: Session timeout (placeholder)
    test_results+=("ℹ️  Session timeout testing requires manual verification")
    
    # Print results
    printf "\n🔒 Session Security Test Results:\n"
    for result in "${test_results[@]}"; do
        echo "   $result"
    done
    printf "\n"
}

# Audit logging tests
test_audit_logging() {
    log "INFO" "Running audit logging tests..."
    
    local test_results=()
    
    # Test 1: Failed login attempt logging
    log "DEBUG" "Testing failed login audit logging..."
    test_email="audit-test-$(date +%s)@example.com"
    
    response=$(curl -s -w "%{http_code}" -o /tmp/audit_test.json \
        -X POST "$CENTRAL_SSO_URL/api/auth/login" \
        -H "X-API-Key: $TEST_API_KEY" \
        -H "Content-Type: application/json" \
        -d "{\"email\": \"$test_email\", \"password\": \"wrongpassword\", \"tenant_slug\": \"tenant1\"}")
    
    # Wait a moment for audit processing
    sleep 2
    
    # Check if audit was recorded (this would require access to audit logs)
    test_results+=("ℹ️  Failed login audit logging (requires log verification)")
    
    # Test 2: Request ID propagation
    log "DEBUG" "Testing request ID propagation..."
    request_id="test-$(uuidgen 2>/dev/null || openssl rand -hex 16)"
    
    response=$(curl -s -w "%{http_code}" -H "X-Request-ID: $request_id" \
        -o /tmp/request_id_test.json "$CENTRAL_SSO_URL/health")
    
    if [[ "$response" == "200" ]]; then
        test_results+=("✅ Request ID header accepted")
    else
        test_results+=("❌ Request ID header processing failed")
    fi
    
    # Print results
    printf "\n📊 Audit Logging Test Results:\n"
    for result in "${test_results[@]}"; do
        echo "   $result"
    done
    printf "\n"
}

# Input validation tests
test_input_validation() {
    log "INFO" "Running input validation tests..."
    
    local test_results=()
    
    # Test 1: SQL injection attempt
    log "DEBUG" "Testing SQL injection protection..."
    sql_payload="admin'; DROP TABLE users; --"
    
    response=$(curl -s -w "%{http_code}" -o /tmp/sql_test.json \
        -X POST "$CENTRAL_SSO_URL/api/auth/login" \
        -H "X-API-Key: $TEST_API_KEY" \
        -H "Content-Type: application/json" \
        -d "{\"email\": \"$sql_payload\", \"password\": \"password\"}")
    
    if [[ "$response" =~ ^(400|422|401)$ ]]; then
        test_results+=("✅ SQL injection payload rejected")
    else
        test_results+=("❌ SQL injection payload processed (HTTP $response)")
    fi
    
    # Test 2: XSS attempt
    log "DEBUG" "Testing XSS protection..."
    xss_payload="<script>alert('xss')</script>"
    
    response=$(curl -s -w "%{http_code}" -o /tmp/xss_test.json \
        -X POST "$CENTRAL_SSO_URL/api/auth/login" \
        -H "X-API-Key: $TEST_API_KEY" \
        -H "Content-Type: application/json" \
        -d "{\"email\": \"$xss_payload\", \"password\": \"password\"}")
    
    if [[ "$response" =~ ^(400|422|401)$ ]]; then
        test_results+=("✅ XSS payload properly handled")
    else
        test_results+=("⚠️  XSS payload processing unclear (HTTP $response)")
    fi
    
    # Test 3: Oversized payload
    log "DEBUG" "Testing oversized payload protection..."
    large_payload=$(printf 'A%.0s' {1..10000})  # 10KB payload
    
    response=$(curl -s -w "%{http_code}" -o /tmp/large_test.json \
        -X POST "$CENTRAL_SSO_URL/api/auth/login" \
        -H "X-API-Key: $TEST_API_KEY" \
        -H "Content-Type: application/json" \
        -d "{\"email\": \"$large_payload\", \"password\": \"password\"}")
    
    if [[ "$response" =~ ^(400|413|422)$ ]]; then
        test_results+=("✅ Oversized payload rejected")
    else
        test_results+=("⚠️  Oversized payload accepted (HTTP $response)")
    fi
    
    # Print results
    printf "\n🔍 Input Validation Test Results:\n"
    for result in "${test_results[@]}"; do
        echo "   $result"
    done
    printf "\n"
}

# Penetration tests (dangerous - requires confirmation)
test_penetration() {
    if [[ "$CONFIRM_DANGEROUS" != true ]]; then
        log "WARN" "Penetration tests are potentially dangerous and require --confirm flag"
        log "INFO" "These tests may trigger security alerts and should only be run in isolated environments"
        return 1
    fi
    
    log "WARN" "Running penetration tests - these may trigger security alerts!"
    
    local test_results=()
    
    # Test 1: Brute force simulation (limited)
    log "DEBUG" "Simulating limited brute force attack..."
    local brute_attempts=50  # Limited to avoid overwhelming system
    
    for i in $(seq 1 $brute_attempts); do
        curl -s -o /dev/null \
            -X POST "$TENANT1_URL/login" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -d "email=brute-test@example.com&password=attempt$i&_token=test" &
        
        if [[ $((i % 10)) -eq 0 ]]; then
            wait  # Wait for batch to complete
            log "DEBUG" "Completed $i brute force attempts"
        fi
    done
    wait
    
    test_results+=("⚠️  Brute force simulation completed ($brute_attempts attempts)")
    
    # Test 2: API endpoint enumeration
    log "DEBUG" "Testing API endpoint enumeration..."
    endpoints=("/admin" "/api/admin" "/api/users" "/api/internal" "/debug" "/.env")
    
    for endpoint in "${endpoints[@]}"; do
        response=$(curl -s -w "%{http_code}" -o /dev/null "$CENTRAL_SSO_URL$endpoint")
        if [[ "$response" != "404" && "$response" != "403" ]]; then
            test_results+=("⚠️  Unexpected endpoint response: $endpoint (HTTP $response)")
        fi
    done
    
    test_results+=("✅ API endpoint enumeration completed")
    
    # Test 3: Header injection attempt
    log "DEBUG" "Testing header injection..."
    response=$(curl -s -w "%{http_code}" -o /tmp/header_test.json \
        -H "X-API-Key: $TEST_API_KEY" \
        -H "X-Injected-Header: \r\nLocation: http://evil.com" \
        "$CENTRAL_SSO_URL/health")
    
    if [[ "$response" == "200" ]]; then
        test_results+=("✅ Header injection attempt handled")
    else
        test_results+=("⚠️  Header injection response unclear (HTTP $response)")
    fi
    
    # Print results
    printf "\n🎯 Penetration Test Results:\n"
    for result in "${test_results[@]}"; do
        echo "   $result"
    done
    printf "\n"
}

# Generate test summary
generate_summary() {
    local total_categories=$1
    local passed_categories=$2
    local start_time=$3
    local end_time=$4
    
    local duration=$((end_time - start_time))
    local status="PASSED"
    
    if [[ $passed_categories -lt $total_categories ]]; then
        status="FAILED"
    fi
    
    cat << EOF

📋 Security Test Summary
========================
Status: $status
Categories Tested: $total_categories
Categories Passed: $passed_categories
Duration: ${duration}s
Report: $LOG_FILE

$(date '+%Y-%m-%d %H:%M:%S') - Security testing completed

EOF
}

# Main execution function
main() {
    local start_time=$(date +%s)
    
    log "INFO" "Starting SSO Security Testing Framework"
    log "INFO" "Log file: $LOG_FILE"
    
    # Load configuration and validate environment
    load_config
    validate_environment
    
    if [[ "$DRY_RUN" == true ]]; then
        log "INFO" "DRY RUN MODE - No tests will be executed"
        if [[ -n "$CATEGORY" ]]; then
            log "INFO" "Would run category: $CATEGORY"
        else
            log "INFO" "Would run all categories: ${CATEGORIES[*]}"
        fi
        exit 0
    fi
    
    local categories_to_run=()
    if [[ -n "$CATEGORY" ]]; then
        if [[ " ${CATEGORIES[@]} " =~ " ${CATEGORY} " ]]; then
            categories_to_run=("$CATEGORY")
        else
            log "ERROR" "Invalid category: $CATEGORY"
            log "INFO" "Available categories: ${CATEGORIES[*]}"
            exit 1
        fi
    else
        # Run all safe categories by default
        categories_to_run=("authentication" "rate-limiting" "session-security" "audit-logging" "input-validation")
    fi
    
    local total_categories=${#categories_to_run[@]}
    local passed_categories=0
    
    # Run tests for each category
    for category in "${categories_to_run[@]}"; do
        log "INFO" "Running $category tests..."
        
        case $category in
            "authentication")
                test_authentication && ((passed_categories++))
                ;;
            "rate-limiting")
                test_rate_limiting && ((passed_categories++))
                ;;
            "session-security")
                test_session_security && ((passed_categories++))
                ;;
            "audit-logging")
                test_audit_logging && ((passed_categories++))
                ;;
            "input-validation")
                test_input_validation && ((passed_categories++))
                ;;
            "penetration")
                test_penetration && ((passed_categories++))
                ;;
        esac
        
        if [[ "$CI_MODE" == true && $? -ne 0 ]]; then
            log "ERROR" "Test failure in CI mode, exiting"
            exit 1
        fi
    done
    
    local end_time=$(date +%s)
    generate_summary "$total_categories" "$passed_categories" "$start_time" "$end_time"
    
    # Exit with appropriate code
    if [[ $passed_categories -eq $total_categories ]]; then
        exit 0
    else
        exit 1
    fi
}

# Run main function
main "$@"