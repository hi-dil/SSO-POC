#!/bin/bash

#################################################################################
# Cloudflare Tunnel Setup Script - Docker-Only Version
# 
# This script sets up Cloudflare Tunnel using Docker containers only.
# It minimizes the need for local cloudflared installation.
#
# Prerequisites:
# 1. Cloudflare account with hi-dil.com domain added
# 2. Docker and Docker Compose available
# 3. Cloudflare API token with Zone:Edit permissions
#
# Usage: ./scripts/setup-cloudflare-tunnel-docker.sh
#################################################################################

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
TUNNEL_NAME="sso-poc-tunnel"
DOMAIN="hi-dil.com"
ZONE_NAME="hi-dil.com"
SSO_SUBDOMAIN="sso.poc"
TENANT1_SUBDOMAIN="tenant-one.poc"
TENANT2_SUBDOMAIN="tenant-two.poc"

# Directories
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CLOUDFLARE_DIR="$PROJECT_ROOT/infrastructure/cloudflare"
LOGS_DIR="$PROJECT_ROOT/logs"

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

# Check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Prompt for user input
prompt_input() {
    local prompt="$1"
    local var_name="$2"
    local is_secret="${3:-false}"
    
    echo -n -e "${BLUE}$prompt: ${NC}"
    if [ "$is_secret" = "true" ]; then
        read -s user_input
        echo ""
    else
        read user_input
    fi
    
    eval "$var_name=\"$user_input\""
}

# Check prerequisites
check_prerequisites() {
    print_header "Checking Prerequisites"
    
    # Check if Docker is available
    if ! command_exists docker; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    print_success "Docker is available"
    
    # Check if Docker Compose is available
    if ! command_exists docker-compose; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    print_success "Docker Compose is available"
    
    # Check for required environment variables or prompt for them
    if [ -z "${CLOUDFLARE_API_TOKEN:-}" ]; then
        print_info "Cloudflare API token is required for DNS management"
        print_info "Create one at: https://dash.cloudflare.com/profile/api-tokens"
        print_info "Required permissions: Zone:Edit for $DOMAIN"
        prompt_input "Enter your Cloudflare API token" CLOUDFLARE_API_TOKEN true
    fi
    
    if [ -z "${CLOUDFLARE_EMAIL:-}" ]; then
        prompt_input "Enter your Cloudflare account email" CLOUDFLARE_EMAIL
    fi
}

# Create directories
create_directories() {
    print_header "Creating Directories"
    
    mkdir -p "$CLOUDFLARE_DIR"
    mkdir -p "$LOGS_DIR/cloudflared"
    mkdir -p "$LOGS_DIR/nginx"
    mkdir -p "$PROJECT_ROOT/backups/mysql"
    
    print_success "Directories created"
}

# Create tunnel using Docker
create_tunnel_docker() {
    print_header "Creating Cloudflare Tunnel via Docker"
    
    # Create a temporary container to run cloudflared commands
    print_info "Creating tunnel using Docker..."
    
    # First, we need to login and get credentials
    # This creates a one-time container for authentication
    print_info "Authenticating with Cloudflare..."
    
    # Create temporary credentials directory
    mkdir -p "$CLOUDFLARE_DIR/temp"
    
    # Use Docker to create tunnel with API token authentication
    cat > "$CLOUDFLARE_DIR/create-tunnel.sh" << 'EOF'
#!/bin/bash
set -e

# Create tunnel
TUNNEL_OUTPUT=$(cloudflared tunnel create "$TUNNEL_NAME" 2>&1)
echo "$TUNNEL_OUTPUT"

# Extract tunnel ID
TUNNEL_ID=$(echo "$TUNNEL_OUTPUT" | grep -oE '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}' | head -1)
echo "TUNNEL_ID=$TUNNEL_ID" > /tmp/tunnel-info.txt

# Copy credentials to output directory
if [ -f "/root/.cloudflared/$TUNNEL_ID.json" ]; then
    cp "/root/.cloudflared/$TUNNEL_ID.json" /output/tunnel-credentials.json
    echo "Credentials copied successfully"
else
    echo "Error: Credentials file not found"
    exit 1
fi
EOF

    chmod +x "$CLOUDFLARE_DIR/create-tunnel.sh"
    
    # Run tunnel creation in Docker
    docker run --rm \
        -v "$CLOUDFLARE_DIR:/output" \
        -v "$CLOUDFLARE_DIR/create-tunnel.sh:/create-tunnel.sh" \
        -e CLOUDFLARE_API_TOKEN="$CLOUDFLARE_API_TOKEN" \
        -e TUNNEL_NAME="$TUNNEL_NAME" \
        cloudflare/cloudflared:latest \
        sh -c "/create-tunnel.sh"
    
    # Extract tunnel ID from the output
    if [ -f "$CLOUDFLARE_DIR/tunnel-info.txt" ]; then
        source "$CLOUDFLARE_DIR/tunnel-info.txt"
        echo "$TUNNEL_ID" > "$CLOUDFLARE_DIR/tunnel-id.txt"
        print_success "Tunnel created with ID: $TUNNEL_ID"
    else
        print_error "Failed to create tunnel or extract tunnel ID"
        exit 1
    fi
    
    # Clean up temporary files
    rm -f "$CLOUDFLARE_DIR/create-tunnel.sh"
    rm -f "$CLOUDFLARE_DIR/tunnel-info.txt"
    rm -rf "$CLOUDFLARE_DIR/temp"
}

# Setup DNS records using Cloudflare API
setup_dns_api() {
    print_header "Setting Up DNS Records via API"
    
    # Get zone ID
    print_info "Getting zone ID for $ZONE_NAME..."
    
    ZONE_ID=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones?name=$ZONE_NAME" \
        -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
        -H "Content-Type: application/json" | \
        grep -oE '"id":"[^"]*"' | head -1 | cut -d'"' -f4)
    
    if [ -z "$ZONE_ID" ]; then
        print_error "Could not find zone ID for $ZONE_NAME"
        print_error "Please ensure the domain is added to your Cloudflare account"
        exit 1
    fi
    
    print_success "Zone ID found: $ZONE_ID"
    
    # Get tunnel ID
    TUNNEL_ID=$(cat "$CLOUDFLARE_DIR/tunnel-id.txt")
    TUNNEL_TARGET="$TUNNEL_ID.cfargotunnel.com"
    
    # Create DNS records for each subdomain
    create_dns_record() {
        local subdomain="$1"
        local full_domain="$subdomain.$DOMAIN"
        
        print_info "Creating DNS record for $full_domain..."
        
        # Check if record already exists
        EXISTING_RECORD=$(curl -s -X GET \
            "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records?name=$full_domain&type=CNAME" \
            -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
            -H "Content-Type: application/json")
        
        if echo "$EXISTING_RECORD" | grep -q '"count":0'; then
            # Create new record
            RESULT=$(curl -s -X POST \
                "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
                -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
                -H "Content-Type: application/json" \
                --data "{
                    \"type\": \"CNAME\",
                    \"name\": \"$subdomain\",
                    \"content\": \"$TUNNEL_TARGET\",
                    \"proxied\": true,
                    \"ttl\": 1
                }")
            
            if echo "$RESULT" | grep -q '"success":true'; then
                print_success "DNS record created for $full_domain"
            else
                print_error "Failed to create DNS record for $full_domain"
                echo "$RESULT" | grep -o '"message":"[^"]*"' || echo "Unknown error"
            fi
        else
            print_warning "DNS record for $full_domain already exists"
        fi
    }
    
    # Create records for all subdomains
    create_dns_record "$SSO_SUBDOMAIN"
    create_dns_record "$TENANT1_SUBDOMAIN"
    create_dns_record "$TENANT2_SUBDOMAIN"
}

# Update tunnel configuration
update_tunnel_config() {
    print_header "Updating Tunnel Configuration"
    
    TUNNEL_ID=$(cat "$CLOUDFLARE_DIR/tunnel-id.txt")
    
    # Update config.yml with the correct tunnel ID
    if [ -f "$CLOUDFLARE_DIR/config.yml" ]; then
        sed -i.bak "s/tunnel:.*/tunnel: $TUNNEL_ID/" "$CLOUDFLARE_DIR/config.yml"
        print_success "Updated tunnel configuration with ID: $TUNNEL_ID"
    else
        print_error "Tunnel configuration file not found"
        exit 1
    fi
}

# Setup environment file
setup_environment() {
    print_header "Setting Up Environment Configuration"
    
    ENV_FILE="$PROJECT_ROOT/.env.cloudflare"
    
    if [ ! -f "$ENV_FILE" ]; then
        TUNNEL_ID=$(cat "$CLOUDFLARE_DIR/tunnel-id.txt")
        
        cat > "$ENV_FILE" << EOF
# Cloudflare Tunnel Configuration for SSO POC
# Generated on $(date)

# Domain Configuration
DOMAIN=hi-dil.com
CENTRAL_SSO_URL=https://sso.poc.hi-dil.com
TENANT1_URL=https://tenant-one.poc.hi-dil.com
TENANT2_URL=https://tenant-two.poc.hi-dil.com

# Tunnel Configuration
TUNNEL_NAME=$TUNNEL_NAME
TUNNEL_ID=$TUNNEL_ID

# Cloudflare API Configuration
CLOUDFLARE_API_TOKEN=$CLOUDFLARE_API_TOKEN
CLOUDFLARE_EMAIL=$CLOUDFLARE_EMAIL
CLOUDFLARE_ZONE_ID=$ZONE_ID

# Redis Configuration
REDIS_PASSWORD=secure_redis_password_change_me

# Session Configuration
SESSION_DOMAIN=.poc.hi-dil.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Security Configuration
SECURE_HEADERS=true
HSTS_MAX_AGE=31536000

# CORS Configuration
CORS_ALLOWED_ORIGINS=https://sso.poc.hi-dil.com,https://tenant-one.poc.hi-dil.com,https://tenant-two.poc.hi-dil.com

# Trusted Proxies (Cloudflare IP Ranges)
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22

# Logging
LOG_LEVEL=warning
LOG_STDERR_FORMATTER=Monolog\\\\Formatter\\\\JsonFormatter

# Database Backup Configuration
BACKUP_RETENTION_DAYS=30
MYSQL_BACKUP_PATH=/backups/mysql
EOF
        print_success "Environment file created: $ENV_FILE"
    else
        print_info "Environment file already exists: $ENV_FILE"
    fi
}

# Deploy services
deploy_services() {
    print_header "Deploying Services"
    
    cd "$PROJECT_ROOT"
    
    # Create Docker network if it doesn't exist
    if ! docker network ls | grep -q "sso-network"; then
        docker network create sso-network
        print_success "Created Docker network: sso-network"
    fi
    
    # Stop existing services if running
    print_info "Stopping existing services..."
    docker-compose down 2>/dev/null || true
    
    # Start services with Cloudflare configuration
    print_info "Starting services with Cloudflare Tunnel..."
    docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml up -d
    
    print_success "Services deployed successfully"
}

# Health checks
perform_health_checks() {
    print_header "Performing Health Checks"
    
    # Wait for services to start
    print_info "Waiting for services to start (30 seconds)..."
    sleep 30
    
    # Check Docker services
    print_info "Checking Docker services..."
    if docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml ps | grep -q "Up"; then
        print_success "Docker services are running"
    else
        print_warning "Some Docker services may not be running properly"
        docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml ps
    fi
    
    # Check tunnel metrics
    print_info "Checking tunnel metrics..."
    if curl -s http://localhost:9090/metrics > /dev/null 2>&1; then
        print_success "Tunnel metrics endpoint is accessible"
    else
        print_warning "Tunnel metrics endpoint is not accessible"
    fi
    
    # Check tunnel status via API
    print_info "Checking tunnel status..."
    TUNNEL_ID=$(cat "$CLOUDFLARE_DIR/tunnel-id.txt")
    TUNNEL_STATUS=$(curl -s -X GET \
        "https://api.cloudflare.com/client/v4/accounts/$(curl -s -X GET "https://api.cloudflare.com/client/v4/user" -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" | grep -oE '"id":"[^"]*"' | head -1 | cut -d'"' -f4)/cfd_tunnel/$TUNNEL_ID" \
        -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
        -H "Content-Type: application/json" 2>/dev/null || echo "error")
    
    if [[ "$TUNNEL_STATUS" != "error" ]] && echo "$TUNNEL_STATUS" | grep -q '"success":true'; then
        print_success "Tunnel is configured properly"
    else
        print_warning "Could not verify tunnel status via API"
    fi
}

# Generate deployment summary
generate_summary() {
    print_header "Deployment Summary"
    
    echo -e "${GREEN}🎉 Cloudflare Tunnel setup completed successfully (Docker-only)!${NC}"
    echo ""
    echo "🌐 Your SSO POC is now accessible at:"
    echo "  • Central SSO:  https://sso.poc.hi-dil.com"
    echo "  • Tenant 1:     https://tenant-one.poc.hi-dil.com"
    echo "  • Tenant 2:     https://tenant-two.poc.hi-dil.com"
    echo ""
    echo "📊 Monitoring endpoints:"
    echo "  • Tunnel metrics: http://localhost:9090/metrics"
    echo "  • Application logs: ./logs/"
    echo ""
    echo "⚙️ Configuration files:"
    echo "  • Tunnel config: ./cloudflare/config.yml"
    echo "  • Credentials:   ./cloudflare/tunnel-credentials.json"
    echo "  • Environment:   ./.env.cloudflare"
    echo "  • Docker config: ./docker-compose.cloudflare.yml"
    echo ""
    echo "🔧 Useful commands:"
    echo "  • View logs: docker-compose logs -f cloudflared"
    echo "  • Restart tunnel: docker-compose restart cloudflared"
    echo "  • Stop services: docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml down"
    echo ""
    echo "🐳 Docker-only management:"
    echo "  • All operations use Docker containers"
    echo "  • No local cloudflared installation required"
    echo "  • API-based DNS management"
    echo ""
    
    # Add warning about DNS propagation
    echo ""
    print_warning "Note: DNS changes may take a few minutes to propagate globally."
    print_warning "If domains are not accessible immediately, wait 5-10 minutes and try again."
}

# Cleanup function
cleanup() {
    if [ $? -ne 0 ]; then
        print_error "Setup failed. Check the error messages above."
        echo ""
        echo "For troubleshooting:"
        echo "  1. Verify Cloudflare account and domain setup"
        echo "  2. Check Cloudflare API token permissions"
        echo "  3. Review logs: docker-compose logs"
        echo "  4. See documentation: docs/cloudflare-tunnel-deployment.md"
    fi
}

# Main execution
main() {
    trap cleanup EXIT
    
    print_header "Cloudflare Tunnel Setup for SSO POC (Docker-Only)"
    print_info "Domain: hi-dil.com"
    print_info "Tunnel: $TUNNEL_NAME"
    print_info "Method: Docker containers + Cloudflare API"
    echo ""
    
    check_prerequisites
    create_directories
    create_tunnel_docker
    setup_dns_api
    update_tunnel_config
    setup_environment
    deploy_services
    perform_health_checks
    generate_summary
}

# Run main function
main "$@"