#!/bin/bash

# SSL Certificate Generation Script for SSO System
# This script generates self-signed certificates for development and testing.
# For production, use certificates from a trusted CA.

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SSL_DIR="${SCRIPT_DIR}/../ssl"
CENTRAL_SSO_DIR="${SCRIPT_DIR}/../central-sso/ssl"
TENANT1_DIR="${SCRIPT_DIR}/../tenant1-app/ssl"
TENANT2_DIR="${SCRIPT_DIR}/../tenant2-app/ssl"

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

# Create SSL directories
create_directories() {
    log "Creating SSL directories..."
    mkdir -p "$SSL_DIR"
    mkdir -p "$CENTRAL_SSO_DIR"
    mkdir -p "$TENANT1_DIR"
    mkdir -p "$TENANT2_DIR"
}

# Generate Certificate Authority (CA)
generate_ca() {
    log "Generating Certificate Authority (CA)..."
    
    # CA private key
    openssl genrsa -out "$SSL_DIR/ca-key.pem" 4096
    
    # CA certificate
    openssl req -new -x509 -days 365 -key "$SSL_DIR/ca-key.pem" -sha256 -out "$SSL_DIR/ca.pem" -subj "/C=US/ST=CA/L=San Francisco/O=SSO System/OU=Certificate Authority/CN=SSO-CA"
    
    log "CA certificate generated successfully"
}

# Generate server certificate for a service
generate_server_cert() {
    local service_name="$1"
    local domain="$2"
    local cert_dir="$3"
    
    log "Generating server certificate for $service_name ($domain)..."
    
    # Generate private key
    openssl genrsa -out "$cert_dir/server-key.pem" 4096
    
    # Generate certificate signing request
    openssl req -subj "/C=US/ST=CA/L=San Francisco/O=SSO System/OU=$service_name/CN=$domain" -sha256 -new -key "$cert_dir/server-key.pem" -out "$cert_dir/server.csr"
    
    # Create extensions file for SAN
    cat > "$cert_dir/server-extfile.cnf" <<EOF
subjectAltName = DNS:$domain,DNS:localhost,DNS:*.localhost,IP:127.0.0.1,IP:::1
extendedKeyUsage = serverAuth
EOF
    
    # Generate server certificate signed by CA
    openssl x509 -req -days 365 -in "$cert_dir/server.csr" -CA "$SSL_DIR/ca.pem" -CAkey "$SSL_DIR/ca-key.pem" -out "$cert_dir/server-cert.pem" -extfile "$cert_dir/server-extfile.cnf" -CAcreateserial
    
    # Copy CA certificate to service directory
    cp "$SSL_DIR/ca.pem" "$cert_dir/ca.pem"
    
    # Clean up
    rm "$cert_dir/server.csr" "$cert_dir/server-extfile.cnf"
    
    # Set permissions
    chmod 600 "$cert_dir/server-key.pem"
    chmod 644 "$cert_dir/server-cert.pem" "$cert_dir/ca.pem"
    
    log "Server certificate for $service_name generated successfully"
}

# Generate client certificate for tenant authentication
generate_client_cert() {
    local tenant_name="$1"
    local cert_dir="$2"
    
    log "Generating client certificate for $tenant_name..."
    
    # Generate private key
    openssl genrsa -out "$cert_dir/client-key.pem" 4096
    
    # Generate certificate signing request
    openssl req -subj "/C=US/ST=CA/L=San Francisco/O=SSO System/OU=$tenant_name/CN=$tenant_name-client" -new -key "$cert_dir/client-key.pem" -out "$cert_dir/client.csr"
    
    # Create extensions file
    cat > "$cert_dir/client-extfile.cnf" <<EOF
extendedKeyUsage = clientAuth
EOF
    
    # Generate client certificate signed by CA
    openssl x509 -req -days 365 -in "$cert_dir/client.csr" -CA "$SSL_DIR/ca.pem" -CAkey "$SSL_DIR/ca-key.pem" -out "$cert_dir/client-cert.pem" -extfile "$cert_dir/client-extfile.cnf" -CAcreateserial
    
    # Copy CA certificate to tenant directory
    cp "$SSL_DIR/ca.pem" "$cert_dir/ca.pem"
    
    # Clean up
    rm "$cert_dir/client.csr" "$cert_dir/client-extfile.cnf"
    
    # Set permissions
    chmod 600 "$cert_dir/client-key.pem"
    chmod 644 "$cert_dir/client-cert.pem" "$cert_dir/ca.pem"
    
    log "Client certificate for $tenant_name generated successfully"
}

# Generate Diffie-Hellman parameters
generate_dhparam() {
    log "Generating Diffie-Hellman parameters (this may take a while)..."
    openssl dhparam -out "$SSL_DIR/dhparam.pem" 2048
    log "Diffie-Hellman parameters generated successfully"
}

# Create certificate bundle for validation
create_cert_bundle() {
    log "Creating certificate bundle..."
    cat "$SSL_DIR/ca.pem" > "$SSL_DIR/cert-bundle.pem"
    log "Certificate bundle created successfully"
}

# Generate API keys and secrets
generate_secrets() {
    log "Generating API keys and HMAC secrets..."
    
    # Generate API keys
    TENANT1_API_KEY="tenant1_$(openssl rand -hex 20)"
    TENANT2_API_KEY="tenant2_$(openssl rand -hex 20)"
    MASTER_API_KEY="master_$(openssl rand -hex 24)"
    
    # Generate HMAC secret
    HMAC_SECRET="$(openssl rand -hex 32)"
    
    # Generate JWT secret
    JWT_SECRET="$(openssl rand -base64 32)"
    
    # Create secrets file
    cat > "$SSL_DIR/secrets.env" <<EOF
# Generated SSL Secrets - $(date)
# Add these to your .env files

# API Keys
TENANT1_API_KEY=$TENANT1_API_KEY
TENANT2_API_KEY=$TENANT2_API_KEY
MASTER_API_KEY=$MASTER_API_KEY

# HMAC Secret for Request Signing
HMAC_SECRET=$HMAC_SECRET

# JWT Secret
JWT_SECRET=$JWT_SECRET

# SSL Configuration
SSL_VERIFY=true
SSL_CERT_PATH=/app/ssl
SSL_KEY_PATH=/app/ssl
SSL_CA_BUNDLE=/app/ssl/ca.pem

# Request Signing
VERIFY_REQUEST_SIGNATURES=true
REQUEST_TIMEOUT_MINUTES=5
HMAC_ALGORITHM=sha256
EOF
    
    chmod 600 "$SSL_DIR/secrets.env"
    
    log "Secrets generated and saved to ssl/secrets.env"
    warn "Make sure to add these secrets to your .env files and keep them secure!"
}

# Create Docker Compose override for SSL
create_docker_compose_ssl() {
    log "Creating Docker Compose SSL configuration..."
    
    cat > "${SCRIPT_DIR}/../docker-compose.ssl.yml" <<EOF
# Docker Compose SSL Configuration
# Use: docker-compose -f docker-compose.yml -f docker-compose.ssl.yml up

version: '3.8'

services:
  central-sso:
    volumes:
      - ./ssl:/app/ssl:ro
    environment:
      - SSL_VERIFY=true
      - SSL_CERT_PATH=/app/ssl
      - SSL_KEY_PATH=/app/ssl
    ports:
      - "8443:8000"  # HTTPS port

  tenant1-app:
    volumes:
      - ./ssl:/app/ssl:ro
    environment:
      - SSL_VERIFY=true
      - SSL_CERT_PATH=/app/ssl
      - SSL_KEY_PATH=/app/ssl
      - CENTRAL_SSO_URL=https://central-sso:8000
    ports:
      - "8444:8000"  # HTTPS port

  tenant2-app:
    volumes:
      - ./ssl:/app/ssl:ro
    environment:
      - SSL_VERIFY=true
      - SSL_CERT_PATH=/app/ssl
      - SSL_KEY_PATH=/app/ssl
      - CENTRAL_SSO_URL=https://central-sso:8000
    ports:
      - "8445:8000"  # HTTPS port

  nginx-ssl:
    image: nginx:alpine
    ports:
      - "443:443"
      - "80:80"
    volumes:
      - ./ssl:/etc/ssl/certs:ro
      - ./nginx/ssl.conf:/etc/nginx/nginx.conf:ro
    depends_on:
      - central-sso
      - tenant1-app
      - tenant2-app
EOF
    
    log "Docker Compose SSL configuration created"
}

# Create nginx SSL configuration
create_nginx_ssl_config() {
    log "Creating Nginx SSL configuration..."
    
    mkdir -p "${SCRIPT_DIR}/../nginx"
    
    cat > "${SCRIPT_DIR}/../nginx/ssl.conf" <<EOF
events {
    worker_connections 1024;
}

http {
    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE+AESGCM:ECDHE+CHACHA20:DHE+AESGCM:DHE+CHACHA20:!aNULL:!MD5:!DSS;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Central SSO Server
    server {
        listen 443 ssl http2;
        server_name localhost;
        
        ssl_certificate /etc/ssl/certs/server-cert.pem;
        ssl_certificate_key /etc/ssl/certs/server-key.pem;
        ssl_dhparam /etc/ssl/certs/dhparam.pem;
        
        location / {
            proxy_pass http://central-sso:8000;
            proxy_set_header Host \$host;
            proxy_set_header X-Real-IP \$remote_addr;
            proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto https;
        }
    }
    
    # Redirect HTTP to HTTPS
    server {
        listen 80;
        server_name localhost;
        return 301 https://\$server_name\$request_uri;
    }
}
EOF
    
    log "Nginx SSL configuration created"
}

# Verify certificates
verify_certificates() {
    log "Verifying generated certificates..."
    
    # Verify CA certificate
    if openssl x509 -in "$SSL_DIR/ca.pem" -text -noout > /dev/null 2>&1; then
        log "✓ CA certificate is valid"
    else
        error "✗ CA certificate is invalid"
        exit 1
    fi
    
    # Verify server certificates
    for service in "central-sso" "tenant1-app" "tenant2-app"; do
        if [ "$service" = "central-sso" ]; then
            cert_dir="$CENTRAL_SSO_DIR"
        elif [ "$service" = "tenant1-app" ]; then
            cert_dir="$TENANT1_DIR"
        else
            cert_dir="$TENANT2_DIR"
        fi
        
        if openssl verify -CAfile "$SSL_DIR/ca.pem" "$cert_dir/server-cert.pem" > /dev/null 2>&1; then
            log "✓ $service server certificate is valid"
        else
            error "✗ $service server certificate is invalid"
            exit 1
        fi
    done
    
    log "All certificates verified successfully"
}

# Show certificate information
show_cert_info() {
    info "Certificate Information:"
    echo
    info "CA Certificate:"
    openssl x509 -in "$SSL_DIR/ca.pem" -text -noout | grep -E "(Subject|Not Before|Not After)"
    echo
    
    info "Server Certificates:"
    for service in "central-sso" "tenant1-app" "tenant2-app"; do
        if [ "$service" = "central-sso" ]; then
            cert_dir="$CENTRAL_SSO_DIR"
        elif [ "$service" = "tenant1-app" ]; then
            cert_dir="$TENANT1_DIR"
        else
            cert_dir="$TENANT2_DIR"
        fi
        
        echo "  $service:"
        openssl x509 -in "$cert_dir/server-cert.pem" -text -noout | grep -E "(Subject|Not Before|Not After|DNS:)"
        echo
    done
}

# Print usage instructions
print_usage() {
    info "SSL Certificate Generation Complete!"
    echo
    info "Next Steps:"
    echo "1. Copy the secrets from ssl/secrets.env to your .env files"
    echo "2. For development with SSL: docker-compose -f docker-compose.yml -f docker-compose.ssl.yml up"
    echo "3. For production: Use certificates from a trusted CA instead of self-signed certificates"
    echo
    info "Files generated:"
    echo "  - ssl/ca.pem (Certificate Authority)"
    echo "  - ssl/secrets.env (API keys and secrets)"
    echo "  - central-sso/ssl/ (Central SSO certificates)"
    echo "  - tenant1-app/ssl/ (Tenant 1 certificates)"
    echo "  - tenant2-app/ssl/ (Tenant 2 certificates)"
    echo "  - docker-compose.ssl.yml (SSL Docker configuration)"
    echo "  - nginx/ssl.conf (Nginx SSL configuration)"
    echo
    warn "Keep the CA private key (ssl/ca-key.pem) secure and never commit it to version control!"
}

# Main execution
main() {
    info "Starting SSL certificate generation for SSO system..."
    
    # Check if OpenSSL is available
    if ! command -v openssl &> /dev/null; then
        error "OpenSSL is required but not installed. Please install OpenSSL and try again."
        exit 1
    fi
    
    create_directories
    generate_ca
    generate_dhparam
    
    # Generate server certificates
    generate_server_cert "Central SSO" "localhost" "$CENTRAL_SSO_DIR"
    generate_server_cert "Tenant 1" "localhost" "$TENANT1_DIR"
    generate_server_cert "Tenant 2" "localhost" "$TENANT2_DIR"
    
    # Generate client certificates for mutual TLS
    generate_client_cert "tenant1" "$TENANT1_DIR"
    generate_client_cert "tenant2" "$TENANT2_DIR"
    
    create_cert_bundle
    generate_secrets
    create_docker_compose_ssl
    create_nginx_ssl_config
    
    verify_certificates
    show_cert_info
    print_usage
    
    log "SSL certificate generation completed successfully!"
}

# Handle script arguments
case "${1:-}" in
    "--help"|"-h")
        echo "Usage: $0 [--help]"
        echo "Generate SSL certificates for the SSO system"
        exit 0
        ;;
    *)
        main "$@"
        ;;
esac