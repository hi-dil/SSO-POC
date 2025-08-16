#!/bin/bash

# SSO System Backup Script
# Performs full backup of database, configuration, and SSL certificates

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="${SCRIPT_DIR}/.."
BACKUP_DIR="${PROJECT_DIR}/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="sso_backup_${TIMESTAMP}"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_NAME}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

# Create backup directory
create_backup_directory() {
    log "Creating backup directory: $BACKUP_PATH"
    mkdir -p "$BACKUP_PATH"
    mkdir -p "$BACKUP_PATH/database"
    mkdir -p "$BACKUP_PATH/ssl"
    mkdir -p "$BACKUP_PATH/config"
    mkdir -p "$BACKUP_PATH/logs"
}

# Backup database
backup_database() {
    log "Backing up MariaDB databases..."
    
    # Get database connection info
    DB_HOST=${DB_HOST:-localhost}
    DB_USER=${DB_USERNAME:-sso_user}
    DB_PASS=${DB_PASSWORD:-sso_password}
    
    # Backup main SSO database
    log "Backing up central SSO database..."
    docker exec mariadb mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
        --single-transaction --routines --triggers sso_main \
        > "$BACKUP_PATH/database/sso_main.sql"
    
    # Backup tenant databases
    log "Backing up tenant databases..."
    docker exec mariadb mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
        --single-transaction --routines --triggers tenant1_app \
        > "$BACKUP_PATH/database/tenant1_app.sql"
        
    docker exec mariadb mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
        --single-transaction --routines --triggers tenant2_app \
        > "$BACKUP_PATH/database/tenant2_app.sql"
    
    # Create database structure dump
    docker exec mariadb mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
        --no-data --routines --triggers sso_main \
        > "$BACKUP_PATH/database/sso_main_structure.sql"
    
    log "Database backup completed"
}

# Backup SSL certificates and keys
backup_ssl() {
    log "Backing up SSL certificates and keys..."
    
    if [ -d "$PROJECT_DIR/ssl" ]; then
        cp -r "$PROJECT_DIR/ssl" "$BACKUP_PATH/"
        log "SSL certificates backed up"
    else
        warn "SSL directory not found, skipping SSL backup"
    fi
}

# Backup configuration files
backup_config() {
    log "Backing up configuration files..."
    
    # Environment files
    find "$PROJECT_DIR" -name ".env*" -type f | while read file; do
        relative_path=$(realpath --relative-to="$PROJECT_DIR" "$file")
        target_dir="$BACKUP_PATH/config/$(dirname "$relative_path")"
        mkdir -p "$target_dir"
        cp "$file" "$target_dir/"
    done
    
    # Docker compose files
    cp "$PROJECT_DIR"/docker-compose*.yml "$BACKUP_PATH/config/" 2>/dev/null || true
    
    # Configuration files
    find "$PROJECT_DIR" -name "*.cnf" -o -name "*.conf" | while read file; do
        if [[ ! "$file" =~ vendor/ ]]; then
            relative_path=$(realpath --relative-to="$PROJECT_DIR" "$file")
            target_dir="$BACKUP_PATH/config/$(dirname "$relative_path")"
            mkdir -p "$target_dir"
            cp "$file" "$target_dir/"
        fi
    done
    
    # Laravel configuration directories
    for app in central-sso tenant1-app tenant2-app; do
        if [ -d "$PROJECT_DIR/$app/config" ]; then
            mkdir -p "$BACKUP_PATH/config/$app"
            cp -r "$PROJECT_DIR/$app/config" "$BACKUP_PATH/config/$app/"
        fi
    done
    
    log "Configuration files backed up"
}

# Backup application logs
backup_logs() {
    log "Backing up application logs..."
    
    # Laravel logs
    for app in central-sso tenant1-app tenant2-app; do
        log_dir="$PROJECT_DIR/$app/storage/logs"
        if [ -d "$log_dir" ]; then
            mkdir -p "$BACKUP_PATH/logs/$app"
            cp -r "$log_dir"/* "$BACKUP_PATH/logs/$app/" 2>/dev/null || true
        fi
    done
    
    # System logs if available
    if [ -d "$PROJECT_DIR/logs" ]; then
        cp -r "$PROJECT_DIR/logs" "$BACKUP_PATH/"
    fi
    
    log "Logs backed up"
}

# Create backup metadata
create_metadata() {
    log "Creating backup metadata..."
    
    cat > "$BACKUP_PATH/metadata.json" <<EOF
{
    "backup_name": "$BACKUP_NAME",
    "timestamp": "$TIMESTAMP",
    "date": "$(date -Iseconds)",
    "system_info": {
        "hostname": "$(hostname)",
        "os": "$(uname -s)",
        "kernel": "$(uname -r)",
        "architecture": "$(uname -m)"
    },
    "docker_info": {
        "docker_version": "$(docker --version 2>/dev/null || echo 'not available')",
        "compose_version": "$(docker-compose --version 2>/dev/null || echo 'not available')"
    },
    "backup_contents": {
        "database": true,
        "ssl_certificates": $([ -d "$PROJECT_DIR/ssl" ] && echo true || echo false),
        "configuration": true,
        "logs": true
    },
    "backup_size": "$(du -sh "$BACKUP_PATH" | cut -f1)",
    "file_count": $(find "$BACKUP_PATH" -type f | wc -l)
}
EOF

    log "Metadata created"
}

# Compress backup
compress_backup() {
    log "Compressing backup..."
    
    cd "$BACKUP_DIR"
    tar -czf "${BACKUP_NAME}.tar.gz" "$BACKUP_NAME"
    
    # Verify compression
    if [ -f "${BACKUP_NAME}.tar.gz" ]; then
        log "Backup compressed successfully: ${BACKUP_NAME}.tar.gz"
        log "Compressed size: $(du -sh "${BACKUP_NAME}.tar.gz" | cut -f1)"
        
        # Remove uncompressed directory
        rm -rf "$BACKUP_NAME"
    else
        error "Failed to compress backup"
        exit 1
    fi
}

# Cleanup old backups
cleanup_old_backups() {
    local retention_days=${BACKUP_RETENTION_DAYS:-30}
    
    log "Cleaning up backups older than $retention_days days..."
    
    find "$BACKUP_DIR" -name "sso_backup_*.tar.gz" -mtime +$retention_days -delete
    
    log "Old backup cleanup completed"
}

# Verify backup integrity
verify_backup() {
    log "Verifying backup integrity..."
    
    # Test compressed archive
    if tar -tzf "$BACKUP_DIR/${BACKUP_NAME}.tar.gz" > /dev/null 2>&1; then
        log "✓ Backup archive integrity verified"
    else
        error "✗ Backup archive integrity check failed"
        exit 1
    fi
    
    # Check database dumps
    cd "$BACKUP_DIR"
    tar -xzf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}/database/" 2>/dev/null
    
    for db_file in "${BACKUP_NAME}/database/"*.sql; do
        if [ -f "$db_file" ]; then
            if grep -q "Dump completed on" "$db_file"; then
                log "✓ Database dump verified: $(basename "$db_file")"
            else
                warn "⚠ Database dump may be incomplete: $(basename "$db_file")"
            fi
        fi
    done
    
    # Cleanup verification files
    rm -rf "${BACKUP_NAME}"
    
    log "Backup verification completed"
}

# Send backup notification (if configured)
send_notification() {
    if [ -n "$BACKUP_NOTIFICATION_URL" ]; then
        log "Sending backup notification..."
        
        curl -X POST "$BACKUP_NOTIFICATION_URL" \
            -H "Content-Type: application/json" \
            -d "{
                \"message\": \"SSO System backup completed successfully\",
                \"backup_name\": \"$BACKUP_NAME\",
                \"timestamp\": \"$TIMESTAMP\",
                \"size\": \"$(du -sh "$BACKUP_DIR/${BACKUP_NAME}.tar.gz" | cut -f1)\"
            }" 2>/dev/null || warn "Failed to send notification"
    fi
}

# Print backup summary
print_summary() {
    local backup_file="$BACKUP_DIR/${BACKUP_NAME}.tar.gz"
    local backup_size=$(du -sh "$backup_file" | cut -f1)
    
    info "Backup Summary:"
    echo "  Backup Name: $BACKUP_NAME"
    echo "  Timestamp: $TIMESTAMP"
    echo "  Location: $backup_file"
    echo "  Size: $backup_size"
    echo "  Contents:"
    echo "    ✓ Database (sso_main, tenant1_app, tenant2_app)"
    echo "    ✓ SSL Certificates"
    echo "    ✓ Configuration Files"
    echo "    ✓ Application Logs"
    echo
    
    info "Backup completed successfully!"
    echo
    info "To restore from this backup:"
    echo "  1. Extract: tar -xzf ${BACKUP_NAME}.tar.gz"
    echo "  2. Restore database: mysql < ${BACKUP_NAME}/database/sso_main.sql"
    echo "  3. Restore SSL: cp -r ${BACKUP_NAME}/ssl/* ./ssl/"
    echo "  4. Restore config: cp ${BACKUP_NAME}/config/.env* ./"
}

# Handle script arguments
case "${1:-}" in
    "--help"|"-h")
        echo "Usage: $0 [--verify-only] [--cleanup-only] [--help]"
        echo
        echo "Options:"
        echo "  --verify-only    Only verify existing backups"
        echo "  --cleanup-only   Only cleanup old backups"
        echo "  --help          Show this help message"
        echo
        echo "Environment Variables:"
        echo "  BACKUP_RETENTION_DAYS    Days to keep backups (default: 30)"
        echo "  BACKUP_NOTIFICATION_URL  Webhook URL for notifications"
        exit 0
        ;;
    "--verify-only")
        log "Verifying existing backups..."
        for backup in "$BACKUP_DIR"/sso_backup_*.tar.gz; do
            if [ -f "$backup" ]; then
                BACKUP_NAME=$(basename "$backup" .tar.gz)
                verify_backup
            fi
        done
        exit 0
        ;;
    "--cleanup-only")
        cleanup_old_backups
        exit 0
        ;;
esac

# Main backup process
main() {
    info "Starting SSO system backup process..."
    
    # Check if Docker is running
    if ! docker ps > /dev/null 2>&1; then
        error "Docker is not running or not accessible"
        exit 1
    fi
    
    # Check if MariaDB container is running
    if ! docker ps | grep -q mariadb; then
        error "MariaDB container is not running"
        exit 1
    fi
    
    create_backup_directory
    backup_database
    backup_ssl
    backup_config
    backup_logs
    create_metadata
    compress_backup
    verify_backup
    cleanup_old_backups
    send_notification
    print_summary
    
    log "Backup process completed successfully!"
}

# Execute main function
main "$@"