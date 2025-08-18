#!/bin/bash

#################################################################################
# Blue-Green Deployment Switch Script
# 
# This script handles switching traffic between blue and green deployments
# for zero-downtime deployments in production
#
# Usage: ./scripts/blue-green-switch.sh [blue|green]
#################################################################################

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
HAPROXY_CONFIG="/usr/local/etc/haproxy/haproxy.cfg"
HAPROXY_SOCKET="/var/run/haproxy/admin.sock"
CLOUDFLARE_CONFIG="./cloudflare/config.yml"

# Directories
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

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

# Usage information
usage() {
    echo "Usage: $0 [blue|green]"
    echo ""
    echo "This script switches traffic between blue and green deployments"
    echo ""
    echo "Arguments:"
    echo "  blue    Switch traffic to blue deployment"
    echo "  green   Switch traffic to green deployment"
    echo ""
    echo "Examples:"
    echo "  $0 blue    # Switch to blue deployment"
    echo "  $0 green   # Switch to green deployment"
    exit 1
}

# Validate deployment color
validate_color() {
    local color="$1"
    if [[ "$color" != "blue" && "$color" != "green" ]]; then
        print_error "Invalid deployment color: $color"
        print_error "Must be either 'blue' or 'green'"
        usage
    fi
}

# Check if deployment is healthy
check_deployment_health() {
    local color="$1"
    local services=("central-sso-$color" "tenant1-app-$color" "tenant2-app-$color")
    local all_healthy=true
    
    print_info "Checking health of $color deployment..."
    
    for service in "${services[@]}"; do
        print_info "Checking $service..."
        
        # Check if container is running
        if ! docker ps --filter "name=$service" --filter "status=running" | grep -q "$service"; then
            print_error "$service is not running"
            all_healthy=false
            continue
        fi
        
        # Check health status
        health_status=$(docker inspect "$service" --format='{{.State.Health.Status}}' 2>/dev/null || echo "unknown")
        
        if [[ "$health_status" == "healthy" ]]; then
            print_success "$service is healthy"
        elif [[ "$health_status" == "starting" ]]; then
            print_warning "$service is still starting up"
            
            # Wait for service to become healthy (up to 2 minutes)
            local timeout=120
            local elapsed=0
            
            while [[ "$health_status" == "starting" && $elapsed -lt $timeout ]]; do
                sleep 5
                elapsed=$((elapsed + 5))
                health_status=$(docker inspect "$service" --format='{{.State.Health.Status}}' 2>/dev/null || echo "unknown")
                print_info "$service status: $health_status (${elapsed}s elapsed)"
            done
            
            if [[ "$health_status" == "healthy" ]]; then
                print_success "$service became healthy after ${elapsed}s"
            else
                print_error "$service failed to become healthy within ${timeout}s"
                all_healthy=false
            fi
        else
            print_error "$service is not healthy (status: $health_status)"
            all_healthy=false
        fi
    done
    
    if [[ "$all_healthy" == "true" ]]; then
        print_success "All $color deployment services are healthy"
        return 0
    else
        print_error "One or more $color deployment services are not healthy"
        return 1
    fi
}

# Update Cloudflare tunnel configuration
update_cloudflare_config() {
    local color="$1"
    
    print_info "Updating Cloudflare tunnel configuration for $color deployment..."
    
    # Create backup of current config
    cp "$CLOUDFLARE_CONFIG" "$CLOUDFLARE_CONFIG.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Update ingress rules to point to the new color
    sed -i.tmp "s/central-sso-[^:]*:/central-sso-$color:/g" "$CLOUDFLARE_CONFIG"
    sed -i.tmp "s/tenant1-app-[^:]*:/tenant1-app-$color:/g" "$CLOUDFLARE_CONFIG"
    sed -i.tmp "s/tenant2-app-[^:]*:/tenant2-app-$color:/g" "$CLOUDFLARE_CONFIG"
    
    # Remove temporary file
    rm -f "$CLOUDFLARE_CONFIG.tmp"
    
    # Restart cloudflared to pick up new configuration
    if docker ps --filter "name=cloudflared-production-tunnel" --filter "status=running" | grep -q "cloudflared"; then
        print_info "Restarting Cloudflare tunnel..."
        docker restart cloudflared-production-tunnel
        
        # Wait for tunnel to restart
        sleep 10
        
        # Verify tunnel is healthy
        if docker ps --filter "name=cloudflared-production-tunnel" --filter "status=running" | grep -q "cloudflared"; then
            print_success "Cloudflare tunnel restarted successfully"
        else
            print_error "Cloudflare tunnel failed to restart"
            # Restore backup
            cp "$CLOUDFLARE_CONFIG.backup.$(date +%Y%m%d_%H%M%S)" "$CLOUDFLARE_CONFIG"
            return 1
        fi
    else
        print_warning "Cloudflare tunnel container not found or not running"
    fi
    
    print_success "Cloudflare configuration updated for $color deployment"
}

# Test traffic switching
test_traffic_switch() {
    local color="$1"
    local domains=("sso.poc.hi-dil.com" "tenant-one.poc.hi-dil.com" "tenant-two.poc.hi-dil.com")
    
    print_info "Testing traffic switch to $color deployment..."
    
    # Wait for DNS propagation
    sleep 30
    
    for domain in "${domains[@]}"; do
        print_info "Testing https://$domain/health"
        
        # Test health endpoint
        if curl -f -s --max-time 15 "https://$domain/health" > /dev/null; then
            print_success "$domain is responding correctly"
        else
            print_error "$domain is not responding correctly"
            return 1
        fi
        
        # Additional test with retry
        for i in {1..3}; do
            if curl -f -s --max-time 10 "https://$domain/" > /dev/null; then
                print_success "$domain main page test $i/3 passed"
                break
            else
                if [[ $i -eq 3 ]]; then
                    print_error "$domain main page test failed after 3 attempts"
                    return 1
                else
                    print_warning "$domain main page test $i/3 failed, retrying..."
                    sleep 5
                fi
            fi
        done
    done
    
    print_success "All traffic switch tests passed for $color deployment"
}

# Scale down old deployment
scale_down_old_deployment() {
    local new_color="$1"
    local old_color=$([ "$new_color" = "blue" ] && echo "green" || echo "blue")
    
    print_info "Scaling down $old_color deployment..."
    
    # Scale down old deployment containers
    local old_services=("central-sso-$old_color" "tenant1-app-$old_color" "tenant2-app-$old_color")
    
    for service in "${old_services[@]}"; do
        if docker ps --filter "name=$service" --filter "status=running" | grep -q "$service"; then
            print_info "Stopping $service..."
            docker stop "$service" || print_warning "Failed to stop $service"
        else
            print_info "$service is already stopped"
        fi
    done
    
    print_success "Old $old_color deployment scaled down"
}

# Rollback function
rollback() {
    local failed_color="$1"
    local rollback_color=$([ "$failed_color" = "blue" ] && echo "green" || echo "blue")
    
    print_error "Deployment to $failed_color failed, initiating rollback to $rollback_color..."
    
    # Restore Cloudflare configuration
    if [[ -f "$CLOUDFLARE_CONFIG.backup."* ]]; then
        latest_backup=$(ls -t "$CLOUDFLARE_CONFIG.backup."* | head -1)
        cp "$latest_backup" "$CLOUDFLARE_CONFIG"
        docker restart cloudflared-production-tunnel
        print_info "Cloudflare configuration restored from backup"
    fi
    
    # Ensure rollback deployment is running
    local rollback_services=("central-sso-$rollback_color" "tenant1-app-$rollback_color" "tenant2-app-$rollback_color")
    
    for service in "${rollback_services[@]}"; do
        if ! docker ps --filter "name=$service" --filter "status=running" | grep -q "$service"; then
            print_info "Starting $service for rollback..."
            docker start "$service" || print_error "Failed to start $service"
        fi
    done
    
    # Wait for services to be healthy
    sleep 30
    
    if check_deployment_health "$rollback_color"; then
        print_success "Rollback to $rollback_color completed successfully"
    else
        print_error "Rollback to $rollback_color failed - manual intervention required"
        exit 1
    fi
}

# Get current active deployment
get_current_deployment() {
    # Check which deployment is currently configured in Cloudflare
    if grep -q "central-sso-blue:" "$CLOUDFLARE_CONFIG"; then
        echo "blue"
    elif grep -q "central-sso-green:" "$CLOUDFLARE_CONFIG"; then
        echo "green"
    else
        echo "unknown"
    fi
}

# Main deployment switch function
switch_deployment() {
    local new_color="$1"
    local current_color=$(get_current_deployment)
    
    print_header "Blue-Green Deployment Switch to $new_color"
    
    if [[ "$current_color" == "$new_color" ]]; then
        print_warning "Already using $new_color deployment"
        exit 0
    fi
    
    print_info "Current deployment: $current_color"
    print_info "Switching to: $new_color"
    
    # Pre-flight checks
    print_info "Performing pre-flight checks..."
    
    # Check if new deployment is healthy
    if ! check_deployment_health "$new_color"; then
        print_error "Target $new_color deployment is not healthy"
        exit 1
    fi
    
    # Update traffic routing
    if ! update_cloudflare_config "$new_color"; then
        print_error "Failed to update Cloudflare configuration"
        exit 1
    fi
    
    # Test the switch
    if ! test_traffic_switch "$new_color"; then
        print_error "Traffic switch test failed"
        rollback "$new_color"
        exit 1
    fi
    
    # Scale down old deployment
    if [[ "$current_color" != "unknown" ]]; then
        scale_down_old_deployment "$new_color"
    fi
    
    # Final verification
    print_info "Performing final verification..."
    sleep 15
    
    if test_traffic_switch "$new_color"; then
        print_success "Deployment switch to $new_color completed successfully!"
        
        # Clean up old backups (keep last 5)
        find "$(dirname "$CLOUDFLARE_CONFIG")" -name "config.yml.backup.*" -type f | sort -r | tail -n +6 | xargs -r rm
        
        print_info "Deployment Summary:"
        print_info "• Active deployment: $new_color"
        print_info "• Previous deployment: $current_color (scaled down)"
        print_info "• Traffic: Switched to $new_color"
        print_info "• Status: ✅ Successful"
    else
        print_error "Final verification failed"
        rollback "$new_color"
        exit 1
    fi
}

# Main execution
main() {
    if [[ $# -ne 1 ]]; then
        usage
    fi
    
    local target_color="$1"
    validate_color "$target_color"
    
    # Trap for cleanup on failure
    trap 'print_error "Script interrupted or failed"; exit 1' ERR INT TERM
    
    switch_deployment "$target_color"
}

# Run main function
main "$@"