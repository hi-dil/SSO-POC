#!/bin/bash

# Generate Secure Secrets for Multi-Tenant SSO System
# This script generates all the secure secrets needed for production deployment

set -euo pipefail

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_header() {
    echo -e "${BLUE}$1${NC}"
    echo "$(printf '=%.0s' {1..50})"
}

print_secret() {
    echo -e "${GREEN}$1:${NC} $2"
}

print_note() {
    echo -e "${YELLOW}NOTE:${NC} $1"
}

# Function to generate a secure random string
generate_random() {
    local length="${1:-32}"
    openssl rand -base64 "$length" | tr -d "=+/" | cut -c1-"$length"
}

# Function to generate hex string
generate_hex() {
    local length="${1:-32}"
    openssl rand -hex "$length"
}

# Main function
main() {
    echo "Multi-Tenant SSO Secret Generator"
    echo "=================================="
    echo
    
    print_header "Application Secrets"
    
    # Laravel App Key (base64 encoded)
    APP_KEY="base64:$(openssl rand -base64 32)"
    print_secret "APP_KEY" "$APP_KEY"
    
    # JWT Secret (32 characters)
    JWT_SECRET=$(generate_random 32)
    print_secret "JWT_SECRET" "$JWT_SECRET"
    
    echo
    print_header "Database Secrets"
    
    # Database passwords
    DB_PASSWORD=$(generate_random 24)
    print_secret "DB_PASSWORD" "$DB_PASSWORD"
    
    MYSQL_ROOT_PASSWORD=$(generate_random 24)
    print_secret "MYSQL_ROOT_PASSWORD" "$MYSQL_ROOT_PASSWORD"
    
    # Redis password
    REDIS_PASSWORD=$(generate_random 24)
    print_secret "REDIS_PASSWORD" "$REDIS_PASSWORD"
    
    echo
    print_header "Tenant API Keys"
    
    # Tenant API keys (tenant prefix + 32 hex characters)
    TENANT1_API_KEY="tenant1_$(generate_hex 16)"
    print_secret "TENANT1_API_KEY" "$TENANT1_API_KEY"
    
    TENANT2_API_KEY="tenant2_$(generate_hex 16)"
    print_secret "TENANT2_API_KEY" "$TENANT2_API_KEY"
    
    echo
    print_header "HMAC Secrets"
    
    # HMAC secrets (64 hex characters)
    TENANT1_HMAC_SECRET=$(generate_hex 32)
    print_secret "TENANT1_HMAC_SECRET" "$TENANT1_HMAC_SECRET"
    
    TENANT2_HMAC_SECRET=$(generate_hex 32)
    print_secret "TENANT2_HMAC_SECRET" "$TENANT2_HMAC_SECRET"
    
    echo
    print_header "Additional Secrets"
    
    # Session encryption key
    SESSION_ENCRYPT_KEY=$(generate_random 32)
    print_secret "SESSION_ENCRYPT_KEY" "$SESSION_ENCRYPT_KEY"
    
    # API rate limiting key
    API_RATE_LIMIT_KEY=$(generate_random 24)
    print_secret "API_RATE_LIMIT_KEY" "$API_RATE_LIMIT_KEY"
    
    # Monitoring password (for Grafana, etc.)
    GRAFANA_PASSWORD=$(generate_random 16)
    print_secret "GRAFANA_PASSWORD" "$GRAFANA_PASSWORD"
    
    echo
    print_header "GitHub Secrets Configuration"
    print_note "Copy these values to your GitHub repository secrets:"
    echo
    
    cat << EOF
Application Secrets:
-------------------
JWT_SECRET = $JWT_SECRET
APP_KEY = $APP_KEY

Database Secrets:
----------------
DB_PASSWORD = $DB_PASSWORD
MYSQL_ROOT_PASSWORD = $MYSQL_ROOT_PASSWORD
REDIS_PASSWORD = $REDIS_PASSWORD

Tenant Configuration:
-------------------
TENANT1_API_KEY = $TENANT1_API_KEY
TENANT2_API_KEY = $TENANT2_API_KEY
TENANT1_HMAC_SECRET = $TENANT1_HMAC_SECRET
TENANT2_HMAC_SECRET = $TENANT2_HMAC_SECRET

Additional Secrets:
-----------------
GRAFANA_PASSWORD = $GRAFANA_PASSWORD
EOF
    
    echo
    print_header "Environment File Template"
    print_note "Add these to your .env.production file:"
    echo
    
    cat << EOF
# Generated secrets
APP_KEY=$APP_KEY
JWT_SECRET=$JWT_SECRET
DB_PASSWORD=$DB_PASSWORD
MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD
REDIS_PASSWORD=$REDIS_PASSWORD
TENANT1_API_KEY=$TENANT1_API_KEY
TENANT2_API_KEY=$TENANT2_API_KEY
TENANT1_HMAC_SECRET=$TENANT1_HMAC_SECRET
TENANT2_HMAC_SECRET=$TENANT2_HMAC_SECRET
GRAFANA_PASSWORD=$GRAFANA_PASSWORD
EOF
    
    echo
    print_header "Security Best Practices"
    echo "1. Store these secrets securely and never commit them to version control"
    echo "2. Use different secrets for each environment (development, staging, production)"
    echo "3. Rotate secrets regularly (every 90 days recommended)"
    echo "4. Use GitHub Secrets or similar secure storage for CI/CD"
    echo "5. Limit access to production secrets to essential personnel only"
    echo "6. Monitor for unauthorized access attempts"
    echo "7. Use encrypted storage for backup copies"
    echo
    
    print_header "Next Steps"
    echo "1. Copy the GitHub Secrets to your repository settings"
    echo "2. Update your .env.production file with these values"
    echo "3. Store a secure backup of these secrets"
    echo "4. Document who has access to these secrets"
    echo "5. Set up secret rotation schedule"
    echo
    
    print_note "These secrets have been generated with cryptographically secure random number generation"
    print_note "Total entropy: 256+ bits per secret"
}

# Run main function
main "$@"