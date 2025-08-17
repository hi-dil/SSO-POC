#!/bin/bash

# Home Server Setup Script for Multi-Tenant SSO System
# This script automates the complete setup of the SSO system on a home server

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
SETUP_LOG="/tmp/sso-home-server-setup.log"

# Default values
DEFAULT_DOMAIN="your-domain.com"
DEFAULT_EMAIL="admin@your-domain.com"
DEFAULT_USER="deploy"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$SETUP_LOG"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$SETUP_LOG"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$SETUP_LOG"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$SETUP_LOG"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_error "This script should not be run as root. Please run as a regular user with sudo access."
        exit 1
    fi
}

# Function to check system requirements
check_requirements() {
    print_status "Checking system requirements..."
    
    # Check OS
    if [[ ! -f /etc/os-release ]]; then
        print_error "Cannot determine OS version"
        exit 1
    fi
    
    . /etc/os-release
    if [[ "$ID" != "ubuntu" ]] && [[ "$ID" != "debian" ]]; then
        print_warning "This script is designed for Ubuntu/Debian. Your OS: $ID"
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
    
    # Check memory
    TOTAL_MEM=$(free -m | awk 'NR==2{printf "%.0f", $2}')
    if [[ $TOTAL_MEM -lt 4096 ]]; then
        print_warning "Minimum 4GB RAM recommended. Current: ${TOTAL_MEM}MB"
    fi
    
    # Check disk space
    DISK_SPACE=$(df / | awk 'NR==2 {printf "%.0f", $4/1024/1024}')
    if [[ $DISK_SPACE -lt 50 ]]; then
        print_warning "Minimum 50GB free space recommended. Current: ${DISK_SPACE}GB"
    fi
    
    print_success "System requirements check completed"
}

# Function to install Docker
install_docker() {
    if command_exists docker; then
        print_status "Docker already installed: $(docker --version)"
        return 0
    fi
    
    print_status "Installing Docker..."
    
    # Update package index
    sudo apt-get update
    
    # Install required packages
    sudo apt-get install -y \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg \
        lsb-release
    
    # Add Docker's official GPG key
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    
    # Set up stable repository
    echo \
        "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu \
        $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    
    # Install Docker Engine
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io
    
    # Add user to docker group
    sudo usermod -aG docker "$USER"
    
    print_success "Docker installed successfully"
}

# Function to install Docker Compose
install_docker_compose() {
    if command_exists docker-compose; then
        print_status "Docker Compose already installed: $(docker-compose --version)"
        return 0
    fi
    
    print_status "Installing Docker Compose..."
    
    # Get latest version
    COMPOSE_VERSION=$(curl -s https://api.github.com/repos/docker/compose/releases/latest | grep 'tag_name' | cut -d'"' -f4)
    
    # Download and install
    sudo curl -L "https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    
    print_success "Docker Compose installed successfully"
}

# Function to setup firewall
setup_firewall() {
    print_status "Setting up UFW firewall..."
    
    # Install UFW if not present
    if ! command_exists ufw; then
        sudo apt-get install -y ufw
    fi
    
    # Configure UFW
    sudo ufw --force reset
    sudo ufw default deny incoming
    sudo ufw default allow outgoing
    
    # Allow SSH
    sudo ufw allow ssh
    
    # Allow Docker
    sudo ufw allow 2376/tcp comment 'Docker daemon'
    
    # Allow monitoring ports (internal only)
    sudo ufw allow from 172.16.0.0/12 to any port 9090 comment 'Prometheus metrics'
    sudo ufw allow from 172.16.0.0/12 to any port 9100 comment 'Node exporter'
    
    # Enable UFW
    sudo ufw --force enable
    
    print_success "Firewall configured successfully"
}

# Function to create deployment user
create_deploy_user() {
    local username="${1:-$DEFAULT_USER}"
    
    if id "$username" &>/dev/null; then
        print_status "User $username already exists"
        return 0
    fi
    
    print_status "Creating deployment user: $username"
    
    # Create user
    sudo adduser --disabled-password --gecos "" "$username"
    
    # Add to groups
    sudo usermod -aG docker "$username"
    sudo usermod -aG sudo "$username"
    
    # Create SSH directory
    sudo -u "$username" mkdir -p "/home/$username/.ssh"
    sudo -u "$username" chmod 700 "/home/$username/.ssh"
    
    print_success "Deployment user $username created successfully"
}

# Function to generate SSH keys
generate_ssh_keys() {
    local username="${1:-$DEFAULT_USER}"
    local key_path="/home/$username/.ssh/github_actions_deploy"
    
    print_status "Generating SSH keys for GitHub Actions..."
    
    if [[ -f "$key_path" ]]; then
        print_status "SSH keys already exist"
        return 0
    fi
    
    # Generate SSH key pair
    sudo -u "$username" ssh-keygen -t ed25519 -f "$key_path" -N "" -C "github-actions-deploy"
    
    # Add public key to authorized_keys
    sudo -u "$username" cat "${key_path}.pub" >> "/home/$username/.ssh/authorized_keys"
    sudo -u "$username" chmod 600 "/home/$username/.ssh/authorized_keys"
    
    print_success "SSH keys generated successfully"
    print_status "Public key location: ${key_path}.pub"
    print_status "Private key location: $key_path"
    
    print_warning "IMPORTANT: Copy the private key to GitHub Secrets as SSH_PRIVATE_KEY"
    echo "Private key content:"
    echo "===================="
    sudo cat "$key_path"
    echo "===================="
}

# Function to setup project directory
setup_project_directory() {
    local project_dir="${1:-/opt/sso-production}"
    
    print_status "Setting up project directory: $project_dir"
    
    # Create directory
    sudo mkdir -p "$project_dir"
    sudo chown "$USER:$USER" "$project_dir"
    
    # Create subdirectories
    mkdir -p "$project_dir"/{logs,data,backups,cloudflare}
    mkdir -p "$project_dir/logs"/{central-sso,tenant1-app,tenant2-app,mariadb,redis,cloudflared}
    mkdir -p "$project_dir/data"/{mariadb,redis}
    
    # Set permissions
    chmod -R 755 "$project_dir"/{logs,data,backups}
    
    print_success "Project directory setup completed"
}

# Function to create cloudflare tunnel
create_cloudflare_tunnel() {
    print_status "Setting up Cloudflare Tunnel..."
    
    # Check if configuration exists
    if [[ -f "cloudflare/tunnel-credentials.json" ]]; then
        print_status "Cloudflare tunnel already configured"
        return 0
    fi
    
    # Get Cloudflare credentials
    if [[ -z "${CLOUDFLARE_API_TOKEN:-}" ]]; then
        read -p "Enter Cloudflare API Token: " -s CLOUDFLARE_API_TOKEN
        echo
    fi
    
    if [[ -z "${TUNNEL_NAME:-}" ]]; then
        TUNNEL_NAME="sso-home-server"
    fi
    
    # Create tunnel
    print_status "Creating Cloudflare tunnel: $TUNNEL_NAME"
    
    docker run --rm \
        -v "$(pwd)/cloudflare:/output" \
        -e CLOUDFLARE_API_TOKEN="$CLOUDFLARE_API_TOKEN" \
        cloudflare/cloudflared:latest \
        tunnel create "$TUNNEL_NAME"
    
    # Get tunnel ID
    TUNNEL_ID=$(grep -o '"TunnelID":"[^"]*' cloudflare/*.json | cut -d'"' -f4 | head -1)
    
    if [[ -z "$TUNNEL_ID" ]]; then
        print_error "Failed to create Cloudflare tunnel"
        exit 1
    fi
    
    print_success "Cloudflare tunnel created: $TUNNEL_ID"
    
    # Create tunnel configuration
    create_tunnel_config "$TUNNEL_ID"
}

# Function to create tunnel configuration
create_tunnel_config() {
    local tunnel_id="$1"
    local domain="${DOMAIN:-$DEFAULT_DOMAIN}"
    
    print_status "Creating tunnel configuration..."
    
    cat > cloudflare/config.yml << EOF
tunnel: $tunnel_id
credentials-file: /etc/cloudflared/tunnel-credentials.json

ingress:
  # Central SSO Server
  - hostname: sso.$domain
    service: http://central-sso:8000
    originRequest:
      httpHostHeader: sso.$domain
      
  # Tenant 1 Application
  - hostname: tenant-one.$domain
    service: http://tenant1-app:8000
    originRequest:
      httpHostHeader: tenant-one.$domain
      
  # Tenant 2 Application
  - hostname: tenant-two.$domain
    service: http://tenant2-app:8000
    originRequest:
      httpHostHeader: tenant-two.$domain
      
  # Catch-all rule (required)
  - service: http_status:404

# Metrics configuration
metrics: 0.0.0.0:9090
EOF
    
    print_success "Tunnel configuration created"
}

# Function to create DNS records
create_dns_records() {
    local domain="${DOMAIN:-$DEFAULT_DOMAIN}"
    
    if [[ -z "${CLOUDFLARE_API_TOKEN:-}" ]] || [[ -z "${CLOUDFLARE_ZONE_ID:-}" ]]; then
        print_warning "Missing Cloudflare credentials. Skipping DNS record creation."
        print_warning "Create these DNS records manually:"
        print_warning "  sso.$domain -> TUNNEL_ID.cfargotunnel.com"
        print_warning "  tenant-one.$domain -> TUNNEL_ID.cfargotunnel.com"
        print_warning "  tenant-two.$domain -> TUNNEL_ID.cfargotunnel.com"
        return 0
    fi
    
    print_status "Creating DNS records..."
    
    # Get tunnel ID
    TUNNEL_ID=$(grep -o '"TunnelID":"[^"]*' cloudflare/*.json | cut -d'"' -f4 | head -1)
    
    # Create DNS records
    for subdomain in sso tenant-one tenant-two; do
        print_status "Creating DNS record for $subdomain.$domain"
        
        curl -s -X POST "https://api.cloudflare.com/client/v4/zones/$CLOUDFLARE_ZONE_ID/dns_records" \
             -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
             -H "Content-Type: application/json" \
             --data "{
               \"type\": \"CNAME\",
               \"name\": \"$subdomain.$domain\",
               \"content\": \"$TUNNEL_ID.cfargotunnel.com\",
               \"ttl\": 1
             }" | jq -r '.success'
    done
    
    print_success "DNS records created successfully"
}

# Function to create environment file
create_environment_file() {
    local env_file=".env.production"
    
    print_status "Creating production environment file..."
    
    if [[ -f "$env_file" ]]; then
        print_status "Environment file already exists"
        return 0
    fi
    
    # Copy from template
    cp ".env.production.example" "$env_file"
    
    # Generate secure secrets
    local app_key="base64:$(openssl rand -base64 32)"
    local jwt_secret=$(openssl rand -base64 32)
    local db_password=$(openssl rand -base64 24)
    local redis_password=$(openssl rand -base64 24)
    local mysql_root_password=$(openssl rand -base64 24)
    local tenant1_api_key="tenant1_$(openssl rand -hex 16)"
    local tenant2_api_key="tenant2_$(openssl rand -hex 16)"
    local tenant1_hmac_secret=$(openssl rand -hex 32)
    local tenant2_hmac_secret=$(openssl rand -hex 32)
    
    # Update environment file
    sed -i "s/APP_KEY=base64:GENERATED_KEY_REPLACE_ME/APP_KEY=$app_key/" "$env_file"
    sed -i "s/JWT_SECRET=REPLACE_WITH_32_CHARACTER_SECRET/JWT_SECRET=$jwt_secret/" "$env_file"
    sed -i "s/DB_PASSWORD=REPLACE_WITH_SECURE_PASSWORD/DB_PASSWORD=$db_password/" "$env_file"
    sed -i "s/REDIS_PASSWORD=REPLACE_WITH_SECURE_PASSWORD/REDIS_PASSWORD=$redis_password/" "$env_file"
    sed -i "s/TENANT1_API_KEY=tenant1_REPLACE_WITH_32_CHAR_API_KEY/TENANT1_API_KEY=$tenant1_api_key/" "$env_file"
    sed -i "s/TENANT2_API_KEY=tenant2_REPLACE_WITH_32_CHAR_API_KEY/TENANT2_API_KEY=$tenant2_api_key/" "$env_file"
    sed -i "s/TENANT1_HMAC_SECRET=REPLACE_WITH_64_CHARACTER_HMAC_SECRET_FOR_TENANT1/TENANT1_HMAC_SECRET=$tenant1_hmac_secret/" "$env_file"
    sed -i "s/TENANT2_HMAC_SECRET=REPLACE_WITH_64_CHARACTER_HMAC_SECRET_FOR_TENANT2/TENANT2_HMAC_SECRET=$tenant2_hmac_secret/" "$env_file"
    
    # Update domain
    if [[ -n "${DOMAIN:-}" ]]; then
        sed -i "s/your-domain.com/$DOMAIN/g" "$env_file"
    fi
    
    # Set secure permissions
    chmod 600 "$env_file"
    
    print_success "Environment file created with secure secrets"
    print_warning "IMPORTANT: Store these secrets securely in GitHub Secrets for CI/CD:"
    echo "JWT_SECRET=$jwt_secret"
    echo "DB_PASSWORD=$db_password"
    echo "REDIS_PASSWORD=$redis_password"
    echo "MYSQL_ROOT_PASSWORD=$mysql_root_password"
    echo "TENANT1_API_KEY=$tenant1_api_key"
    echo "TENANT2_API_KEY=$tenant2_api_key"
    echo "TENANT1_HMAC_SECRET=$tenant1_hmac_secret"
    echo "TENANT2_HMAC_SECRET=$tenant2_hmac_secret"
}

# Function to setup backup scripts
setup_backup_scripts() {
    print_status "Setting up backup scripts..."
    
    # Create backup script
    cat > scripts/backup-production.sh << 'EOF'
#!/bin/bash
set -euo pipefail

BACKUP_DIR="/opt/sso-production/backups"
DATE=$(date +%Y%m%d_%H%M%S)
COMPOSE_FILE="docker-compose.production.yml"

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Database backup
echo "Creating database backup..."
docker-compose -f "$COMPOSE_FILE" exec -T mariadb \
  mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --all-databases > \
  "$BACKUP_DIR/sso_backup_$DATE.sql"

# Compress backup
gzip "$BACKUP_DIR/sso_backup_$DATE.sql"

# Configuration backup
echo "Creating configuration backup..."
tar -czf "$BACKUP_DIR/config_backup_$DATE.tar.gz" \
  .env.production cloudflare/ infrastructure/ docker-compose.production.yml

# Clean old backups (keep 7 days)
find "$BACKUP_DIR" -name "*.gz" -mtime +7 -delete

echo "Backup completed: sso_backup_$DATE.sql.gz"
EOF
    
    chmod +x scripts/backup-production.sh
    
    # Add to crontab
    (crontab -l 2>/dev/null; echo "0 2 * * * /opt/sso-production/scripts/backup-production.sh") | crontab -
    
    print_success "Backup scripts configured"
}

# Function to setup log rotation
setup_log_rotation() {
    print_status "Setting up log rotation..."
    
    sudo tee /etc/logrotate.d/sso-production > /dev/null << 'EOF'
/opt/sso-production/logs/*/*.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    copytruncate
    su root root
}
EOF
    
    print_success "Log rotation configured"
}

# Function to install system updates
setup_auto_updates() {
    print_status "Setting up automatic security updates..."
    
    # Install unattended-upgrades
    sudo apt-get install -y unattended-upgrades
    
    # Configure automatic updates
    sudo dpkg-reconfigure -plow unattended-upgrades
    
    print_success "Automatic security updates configured"
}

# Function to test installation
test_installation() {
    print_status "Testing installation..."
    
    # Test Docker
    if ! docker run --rm hello-world > /dev/null 2>&1; then
        print_error "Docker test failed"
        return 1
    fi
    
    # Test Docker Compose
    if ! docker-compose --version > /dev/null 2>&1; then
        print_error "Docker Compose test failed"
        return 1
    fi
    
    # Test file permissions
    if [[ ! -r ".env.production" ]]; then
        print_error "Environment file not readable"
        return 1
    fi
    
    print_success "All tests passed"
}

# Function to display next steps
display_next_steps() {
    print_success "Home server setup completed successfully!"
    echo
    echo "Next steps:"
    echo "1. Configure GitHub Secrets with the generated secrets above"
    echo "2. Update your domain name in .env.production if needed"
    echo "3. Push your code to trigger CI/CD deployment"
    echo "4. Monitor the deployment in GitHub Actions"
    echo "5. Test your SSO system at https://sso.${DOMAIN:-$DEFAULT_DOMAIN}"
    echo
    echo "Important files created:"
    echo "- .env.production (production environment configuration)"
    echo "- cloudflare/config.yml (tunnel configuration)"
    echo "- cloudflare/tunnel-credentials.json (tunnel credentials)"
    echo "- ~/.ssh/github_actions_deploy (SSH key for deployment)"
    echo
    echo "Monitoring URLs:"
    echo "- Cloudflare tunnel metrics: http://localhost:9090/metrics"
    echo "- System metrics: http://localhost:9100/metrics (if node-exporter enabled)"
    echo
    echo "Log files:"
    echo "- Setup log: $SETUP_LOG"
    echo "- Application logs: /opt/sso-production/logs/"
    echo
    echo "For troubleshooting, check:"
    echo "- Docker logs: docker-compose -f docker-compose.production.yml logs"
    echo "- System logs: journalctl -u docker"
    echo "- Firewall status: sudo ufw status"
}

# Main execution function
main() {
    echo "Multi-Tenant SSO Home Server Setup"
    echo "=================================="
    echo
    
    # Initialize log
    echo "Setup started at $(date)" > "$SETUP_LOG"
    
    # Check if running as root
    check_root
    
    # Get user input
    read -p "Enter your domain name [$DEFAULT_DOMAIN]: " DOMAIN
    DOMAIN=${DOMAIN:-$DEFAULT_DOMAIN}
    
    read -p "Enter your email [$DEFAULT_EMAIL]: " EMAIL
    EMAIL=${EMAIL:-$DEFAULT_EMAIL}
    
    read -p "Enter deployment username [$DEFAULT_USER]: " DEPLOY_USER
    DEPLOY_USER=${DEPLOY_USER:-$DEFAULT_USER}
    
    # Optional Cloudflare credentials
    read -p "Enter Cloudflare API Token (or press Enter to skip): " -s CLOUDFLARE_API_TOKEN
    echo
    if [[ -n "$CLOUDFLARE_API_TOKEN" ]]; then
        read -p "Enter Cloudflare Zone ID: " CLOUDFLARE_ZONE_ID
    fi
    
    echo
    print_status "Starting setup with configuration:"
    print_status "Domain: $DOMAIN"
    print_status "Email: $EMAIL"
    print_status "Deploy User: $DEPLOY_USER"
    echo
    
    # Run setup steps
    check_requirements
    install_docker
    install_docker_compose
    setup_firewall
    create_deploy_user "$DEPLOY_USER"
    generate_ssh_keys "$DEPLOY_USER"
    setup_project_directory
    create_environment_file
    
    # Cloudflare setup (if credentials provided)
    if [[ -n "${CLOUDFLARE_API_TOKEN:-}" ]]; then
        create_cloudflare_tunnel
        create_dns_records
    else
        print_warning "Skipping Cloudflare setup - no API token provided"
    fi
    
    setup_backup_scripts
    setup_log_rotation
    setup_auto_updates
    test_installation
    display_next_steps
    
    print_success "Setup completed successfully!"
}

# Handle script interruption
trap 'print_error "Setup interrupted"; exit 1' INT TERM

# Run main function if script is executed directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi