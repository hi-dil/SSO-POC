#!/bin/sh

# Health check script for SSO applications
# This script performs comprehensive health checks for production deployments

set -e

# Configuration
APP_URL="http://localhost:8000"
HEALTH_ENDPOINT="/health"
MAX_RETRIES=3
RETRY_INTERVAL=2

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [HEALTH] $1"
}

error() {
    echo "${RED}$(date '+%Y-%m-%d %H:%M:%S') [ERROR] $1${NC}" >&2
}

success() {
    echo "${GREEN}$(date '+%Y-%m-%d %H:%M:%S') [OK] $1${NC}"
}

warning() {
    echo "${YELLOW}$(date '+%Y-%m-%d %H:%M:%S') [WARN] $1${NC}"
}

# Check if application is responding
check_app_response() {
    local url="$1"
    local max_retries="$2"
    local retry_count=0
    
    while [ $retry_count -lt $max_retries ]; do
        if curl -f -s --max-time 10 "$url" > /dev/null 2>&1; then
            return 0
        fi
        
        retry_count=$((retry_count + 1))
        if [ $retry_count -lt $max_retries ]; then
            log "Health check attempt $retry_count failed, retrying in ${RETRY_INTERVAL}s..."
            sleep $RETRY_INTERVAL
        fi
    done
    
    return 1
}

# Check database connectivity
check_database() {
    log "Checking database connectivity..."
    
    if php -r "
        try {
            \$pdo = new PDO(
                'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD'),
                [PDO::ATTR_TIMEOUT => 5]
            );
            \$pdo->query('SELECT 1');
            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAIL: ' . \$e->getMessage();
            exit(1);
        }
    " > /dev/null 2>&1; then
        success "Database connectivity check passed"
        return 0
    else
        error "Database connectivity check failed"
        return 1
    fi
}

# Check Redis connectivity
check_redis() {
    log "Checking Redis connectivity..."
    
    if php -r "
        try {
            \$redis = new Redis();
            \$redis->connect(getenv('REDIS_HOST') ?: 'redis', getenv('REDIS_PORT') ?: 6379, 5);
            if (getenv('REDIS_PASSWORD')) {
                \$redis->auth(getenv('REDIS_PASSWORD'));
            }
            \$redis->ping();
            echo 'OK';
        } catch (Exception \$e) {
            echo 'FAIL: ' . \$e->getMessage();
            exit(1);
        }
    " > /dev/null 2>&1; then
        success "Redis connectivity check passed"
        return 0
    else
        warning "Redis connectivity check failed (non-critical)"
        return 0  # Redis failure is not critical for health check
    fi
}

# Check file permissions
check_permissions() {
    log "Checking file permissions..."
    
    # Check storage directory
    if [ ! -w "/var/www/storage" ]; then
        error "Storage directory is not writable"
        return 1
    fi
    
    # Check bootstrap/cache directory
    if [ ! -w "/var/www/bootstrap/cache" ]; then
        error "Bootstrap cache directory is not writable"
        return 1
    fi
    
    success "File permissions check passed"
    return 0
}

# Check PHP-FPM process
check_php_fpm() {
    log "Checking PHP-FPM process..."
    
    if pgrep php-fpm > /dev/null; then
        success "PHP-FPM process is running"
        return 0
    else
        error "PHP-FPM process is not running"
        return 1
    fi
}

# Check Nginx process
check_nginx() {
    log "Checking Nginx process..."
    
    if pgrep nginx > /dev/null; then
        success "Nginx process is running"
        return 0
    else
        error "Nginx process is not running"
        return 1
    fi
}

# Check disk space
check_disk_space() {
    log "Checking disk space..."
    
    # Check if disk usage is above 90%
    disk_usage=$(df /var/www | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$disk_usage" -gt 90 ]; then
        error "Disk usage is critically high: ${disk_usage}%"
        return 1
    elif [ "$disk_usage" -gt 80 ]; then
        warning "Disk usage is high: ${disk_usage}%"
    else
        success "Disk space check passed: ${disk_usage}% used"
    fi
    
    return 0
}

# Check memory usage
check_memory() {
    log "Checking memory usage..."
    
    # Check available memory
    if [ -f /proc/meminfo ]; then
        mem_available=$(grep MemAvailable /proc/meminfo | awk '{print $2}')
        mem_total=$(grep MemTotal /proc/meminfo | awk '{print $2}')
        
        if [ "$mem_available" -gt 0 ] && [ "$mem_total" -gt 0 ]; then
            mem_usage_percent=$(( (mem_total - mem_available) * 100 / mem_total ))
            
            if [ "$mem_usage_percent" -gt 95 ]; then
                error "Memory usage is critically high: ${mem_usage_percent}%"
                return 1
            elif [ "$mem_usage_percent" -gt 85 ]; then
                warning "Memory usage is high: ${mem_usage_percent}%"
            else
                success "Memory usage check passed: ${mem_usage_percent}% used"
            fi
        fi
    fi
    
    return 0
}

# Check application health endpoint
check_app_health() {
    log "Checking application health endpoint..."
    
    if check_app_response "${APP_URL}${HEALTH_ENDPOINT}" $MAX_RETRIES; then
        success "Application health endpoint responded successfully"
        return 0
    else
        error "Application health endpoint failed to respond"
        return 1
    fi
}

# Check Laravel application status
check_laravel_status() {
    log "Checking Laravel application status..."
    
    # Check if Laravel can boot properly
    if cd /var/www && php artisan about --only=environment > /dev/null 2>&1; then
        success "Laravel application status check passed"
        return 0
    else
        error "Laravel application status check failed"
        return 1
    fi
}

# Main health check function
main() {
    log "Starting comprehensive health check..."
    
    # Track overall health status
    overall_status=0
    
    # Critical checks (failure means unhealthy)
    check_php_fpm || overall_status=1
    check_nginx || overall_status=1
    check_permissions || overall_status=1
    check_database || overall_status=1
    check_laravel_status || overall_status=1
    check_app_health || overall_status=1
    
    # Non-critical checks (warnings only)
    check_redis
    check_disk_space
    check_memory
    
    if [ $overall_status -eq 0 ]; then
        success "All critical health checks passed"
        exit 0
    else
        error "One or more critical health checks failed"
        exit 1
    fi
}

# Run main function
main "$@"