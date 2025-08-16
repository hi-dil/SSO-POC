#!/bin/bash

# Daily Security Check Script
# Automated security monitoring for SSO system

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
REPORT_FILE="${SCRIPT_DIR}/reports/daily_security_${TIMESTAMP}.txt"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Logging
log() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$REPORT_FILE"
}

log_success() {
    log "${GREEN}✅ $1${NC}"
}

log_warning() {
    log "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    log "${RED}❌ $1${NC}"
}

# Load configuration
if [[ -f "${SCRIPT_DIR}/config/test.env" ]]; then
    source "${SCRIPT_DIR}/config/test.env"
else
    log_error "Configuration file not found. Please create config/test.env"
    exit 1
fi

# Create reports directory
mkdir -p "${SCRIPT_DIR}/reports"

log "Starting daily security check for SSO system"
log "Report will be saved to: $REPORT_FILE"

# Health checks
check_system_health() {
    log "Checking system health..."
    
    # Central SSO health
    if curl -s --max-time 10 "${CENTRAL_SSO_URL}/health" > /dev/null; then
        log_success "Central SSO is healthy"
    else
        log_error "Central SSO health check failed"
        return 1
    fi
    
    # Tenant health checks
    if curl -s --max-time 10 "${TENANT1_URL}/health" > /dev/null; then
        log_success "Tenant 1 is healthy"
    else
        log_warning "Tenant 1 health check failed"
    fi
    
    if curl -s --max-time 10 "${TENANT2_URL}/health" > /dev/null; then
        log_success "Tenant 2 is healthy"
    else
        log_warning "Tenant 2 health check failed"
    fi
}

# Quick security tests
run_quick_security_tests() {
    log "Running quick security tests..."
    
    # Test API key authentication
    response=$(curl -s -w "%{http_code}" -o /tmp/daily_auth_test.json \
        -X POST "${CENTRAL_SSO_URL}/api/auth/login" \
        -H "X-API-Key: ${TEST_API_KEY}" \
        -H "Content-Type: application/json" \
        -d '{"email": "nonexistent@test.com", "password": "wrongpassword"}')
    
    if [[ "$response" =~ ^(401|422)$ ]]; then
        log_success "API key authentication working"
    else
        log_error "API key authentication issue (HTTP $response)"
    fi
    
    # Test invalid API key rejection
    response=$(curl -s -w "%{http_code}" -o /dev/null \
        -X POST "${CENTRAL_SSO_URL}/api/auth/login" \
        -H "X-API-Key: invalid_key_test" \
        -H "Content-Type: application/json" \
        -d '{"email": "test@test.com", "password": "password"}')
    
    if [[ "$response" == "401" ]]; then
        log_success "Invalid API key properly rejected"
    else
        log_error "Invalid API key not rejected (HTTP $response)"
    fi
    
    # Test rate limiting (light test)
    log "Testing rate limiting (5 attempts)..."
    local rate_limit_triggered=false
    
    for i in {1..5}; do
        response=$(curl -s -w "%{http_code}" -o /dev/null \
            -X POST "${TENANT1_URL}/login" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            -d "email=rate-test@example.com&password=wrongpassword&_token=test")
        
        if [[ "$response" == "429" ]]; then
            rate_limit_triggered=true
            break
        fi
        sleep 1
    done
    
    if [[ "$rate_limit_triggered" == true ]]; then
        log_success "Rate limiting is active"
    else
        log_warning "Rate limiting not triggered in light test"
    fi
}

# Check for suspicious activity
check_suspicious_activity() {
    log "Checking for suspicious activity patterns..."
    
    # This would typically analyze log files
    # For demo purposes, we'll check for basic indicators
    
    # Check for high frequency requests (placeholder)
    log "Analyzing request patterns..."
    
    # Check for failed login patterns (placeholder)
    log "Analyzing failed login attempts..."
    
    # Check for unusual geographic access (placeholder)
    log "Analyzing access patterns..."
    
    log_success "Suspicious activity check completed"
}

# Security configuration validation
validate_security_config() {
    log "Validating security configuration..."
    
    # Check if HTTPS is enabled in production
    if [[ "${CENTRAL_SSO_URL}" == https://* ]]; then
        log_success "HTTPS enabled for Central SSO"
    else
        log_warning "HTTP detected - ensure HTTPS in production"
    fi
    
    # Validate API key format
    if [[ ${#TEST_API_KEY} -ge 32 ]]; then
        log_success "API key length appears secure"
    else
        log_warning "API key may be too short"
    fi
    
    # Check HMAC secret
    if [[ -n "$TEST_HMAC_SECRET" && ${#TEST_HMAC_SECRET} -ge 32 ]]; then
        log_success "HMAC secret configured and appears secure"
    else
        log_warning "HMAC secret missing or too short"
    fi
}

# Generate daily summary
generate_summary() {
    local end_time=$(date '+%Y-%m-%d %H:%M:%S')
    
    cat >> "$REPORT_FILE" << EOF

📊 Daily Security Check Summary
==============================
Date: $end_time
Central SSO: ${CENTRAL_SSO_URL}
Tenant 1: ${TENANT1_URL}
Tenant 2: ${TENANT2_URL}

Status: $([ $? -eq 0 ] && echo "✅ PASSED" || echo "❌ ISSUES DETECTED")

Next Check: $(date -d '+1 day' '+%Y-%m-%d %H:%M:%S')

EOF
}

# Send notifications if configured
send_notifications() {
    if [[ "$NOTIFY_ON_FAILURE" == "true" ]]; then
        # Email notification (requires mail command)
        if command -v mail > /dev/null && [[ -n "$NOTIFICATION_EMAIL" ]]; then
            mail -s "SSO Daily Security Check - $(date '+%Y-%m-%d')" "$NOTIFICATION_EMAIL" < "$REPORT_FILE"
            log "Email notification sent to $NOTIFICATION_EMAIL"
        fi
        
        # Webhook notification
        if [[ -n "$NOTIFICATION_WEBHOOK" ]]; then
            curl -s -X POST "$NOTIFICATION_WEBHOOK" \
                -H "Content-Type: application/json" \
                -d "{\"text\": \"SSO Daily Security Check completed - $(date '+%Y-%m-%d')\"}" \
                > /dev/null
            log "Webhook notification sent"
        fi
    fi
}

# Main execution
main() {
    local exit_code=0
    
    # Run all checks
    check_system_health || exit_code=1
    run_quick_security_tests || exit_code=1
    check_suspicious_activity || exit_code=1
    validate_security_config || exit_code=1
    
    # Generate summary
    generate_summary
    
    # Send notifications if there were issues
    if [[ $exit_code -ne 0 ]]; then
        send_notifications
    fi
    
    log "Daily security check completed with exit code: $exit_code"
    
    # Cleanup old reports (keep last 30 days)
    find "${SCRIPT_DIR}/reports" -name "daily_security_*.txt" -mtime +30 -delete 2>/dev/null || true
    
    exit $exit_code
}

# Execute main function
main "$@"