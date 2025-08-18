#!/bin/bash

#################################################################################
# Cloudflare Tunnel Setup Script for SSO POC
# 
# This script automates the setup of Cloudflare Tunnel for the multi-tenant
# SSO system with the following domains:
# - sso.poc.hi-dil.com (Central SSO)
# - tenant-one.poc.hi-dil.com (Tenant 1)
# - tenant-two.poc.hi-dil.com (Tenant 2)
#
# Prerequisites:
# 1. Cloudflare account with hi-dil.com domain added
# 2. cloudflared CLI installed and authenticated
# 3. Docker and Docker Compose available
#
# Usage: ./scripts/setup-cloudflare-tunnel.sh
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

# Check prerequisites
check_prerequisites() {
    print_header "Checking Prerequisites"
    
    # Check if cloudflared is installed
    if ! command_exists cloudflared; then
        print_error "cloudflared is not installed. Please install it first:"
        echo "  macOS: brew install cloudflared"
        echo "  Ubuntu/Debian: wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb && sudo dpkg -i cloudflared-linux-amd64.deb"
        exit 1
    fi
    print_success "cloudflared is installed"
    
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
    
    # Check if user is authenticated with Cloudflare
    if [ ! -f "$HOME/.cloudflared/cert.pem" ]; then
        print_error "Not authenticated with Cloudflare. Please run: cloudflared tunnel login"
        exit 1
    fi
    print_success "Cloudflare authentication found"
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

# Create or check tunnel
setup_tunnel() {
    print_header "Setting Up Cloudflare Tunnel"
    
    # Check if tunnel already exists
    if cloudflared tunnel list | grep -q "$TUNNEL_NAME"; then
        print_info "Tunnel '$TUNNEL_NAME' already exists"
        TUNNEL_ID=$(cloudflared tunnel list | grep "$TUNNEL_NAME" | awk '{print $1}')
    else
        print_info "Creating new tunnel: $TUNNEL_NAME"
        cloudflared tunnel create "$TUNNEL_NAME"
        TUNNEL_ID=$(cloudflared tunnel list | grep "$TUNNEL_NAME" | awk '{print $1}')
        print_success "Tunnel created with ID: $TUNNEL_ID"
    fi
    
    # Copy tunnel credentials
    CREDENTIALS_SOURCE="$HOME/.cloudflared/$TUNNEL_ID.json"
    CREDENTIALS_DEST="$CLOUDFLARE_DIR/tunnel-credentials.json"
    
    if [ -f "$CREDENTIALS_SOURCE" ]; then
        cp "$CREDENTIALS_SOURCE" "$CREDENTIALS_DEST"
        chmod 600 "$CREDENTIALS_DEST"
        print_success "Tunnel credentials copied"
    else
        print_error "Tunnel credentials not found at $CREDENTIALS_SOURCE"
        exit 1
    fi
    
    # Update config.yml with correct tunnel name if needed
    if [ -f "$CLOUDFLARE_DIR/config.yml" ]; then
        sed -i.bak "s/tunnel:.*/tunnel: $TUNNEL_NAME/" "$CLOUDFLARE_DIR/config.yml"
        print_success "Updated tunnel configuration"
    fi
    
    echo "$TUNNEL_ID" > "$CLOUDFLARE_DIR/tunnel-id.txt"
}

# Setup DNS records
setup_dns() {
    print_header "Setting Up DNS Records"
    
    # Get tunnel ID
    if [ -f "$CLOUDFLARE_DIR/tunnel-id.txt" ]; then
        TUNNEL_ID=$(cat "$CLOUDFLARE_DIR/tunnel-id.txt")
    else
        TUNNEL_ID=$(cloudflared tunnel list | grep "$TUNNEL_NAME" | awk '{print $1}')
    fi
    
    # Create DNS records
    print_info "Creating DNS records for $DOMAIN..."
    
    # Central SSO
    print_info "Setting up DNS for $SSO_SUBDOMAIN.$DOMAIN"
    if cloudflared tunnel route dns "$TUNNEL_NAME" "$SSO_SUBDOMAIN.$DOMAIN"; then
        print_success "DNS record created for $SSO_SUBDOMAIN.$DOMAIN"
    else
        print_warning "DNS record for $SSO_SUBDOMAIN.$DOMAIN may already exist"
    fi
    
    # Tenant 1
    print_info "Setting up DNS for $TENANT1_SUBDOMAIN.$DOMAIN"
    if cloudflared tunnel route dns "$TUNNEL_NAME" "$TENANT1_SUBDOMAIN.$DOMAIN"; then
        print_success "DNS record created for $TENANT1_SUBDOMAIN.$DOMAIN"
    else
        print_warning "DNS record for $TENANT1_SUBDOMAIN.$DOMAIN may already exist"
    fi
    
    # Tenant 2
    print_info "Setting up DNS for $TENANT2_SUBDOMAIN.$DOMAIN"
    if cloudflared tunnel route dns "$TUNNEL_NAME" "$TENANT2_SUBDOMAIN.$DOMAIN"; then
        print_success "DNS record created for $TENANT2_SUBDOMAIN.$DOMAIN"
    else
        print_warning "DNS record for $TENANT2_SUBDOMAIN.$DOMAIN may already exist"
    fi
}

# Setup environment file
setup_environment() {
    print_header "Setting Up Environment Configuration"
    
    ENV_FILE="$PROJECT_ROOT/.env.cloudflare"
    
    if [ ! -f "$ENV_FILE" ]; then
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
TUNNEL_ID=$(cat "$CLOUDFLARE_DIR/tunnel-id.txt" 2>/dev/null || echo "")

# Redis Configuration (if using Redis)
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
    
    # Check tunnel status
    print_info "Checking tunnel status..."
    TUNNEL_STATUS=$(cloudflared tunnel info "$TUNNEL_NAME" 2>/dev/null || echo "error")
    if [[ "$TUNNEL_STATUS" != "error" ]]; then
        print_success "Tunnel is configured properly"
    else
        print_warning "Tunnel configuration may have issues"
    fi
}

# Generate deployment summary
generate_summary() {
    print_header "Deployment Summary"
    
    echo -e "${GREEN}🎉 Cloudflare Tunnel setup completed successfully!${NC}"
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
    echo "  • Environment:   ./.env.cloudflare"
    echo "  • Docker config: ./docker-compose.cloudflare.yml"
    echo ""
    echo "🔧 Useful commands:"
    echo "  • View logs: docker-compose logs -f cloudflared"
    echo "  • Restart tunnel: docker-compose restart cloudflared"
    echo "  • Check status: cloudflared tunnel info $TUNNEL_NAME"
    echo "  • Stop services: docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml down"
    echo ""
    echo "📖 For troubleshooting, see: docs/cloudflare-tunnel-deployment.md"
    
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
        echo "  2. Check cloudflared authentication: cloudflared tunnel login"
        echo "  3. Review logs: docker-compose logs"
        echo "  4. See documentation: docs/cloudflare-tunnel-deployment.md"
    fi
}

# Main execution
main() {
    trap cleanup EXIT
    
    print_header "Cloudflare Tunnel Setup for SSO POC"
    print_info "Domain: hi-dil.com"
    print_info "Tunnel: $TUNNEL_NAME"
    echo ""
    
    check_prerequisites
    create_directories
    setup_tunnel
    setup_dns
    setup_environment
    deploy_services
    perform_health_checks
    generate_summary
}

# Run main function
main "$@"