# Infrastructure Configuration

This directory contains all infrastructure-related configurations for the multi-tenant SSO system.

## Directory Structure

```
infrastructure/
├── cloudflare/          # Cloudflare Tunnel configurations
│   ├── config.yml       # Tunnel configuration file
│   └── tunnel-credentials.json  # Tunnel credentials (generated)
├── database/            # Database initialization and configuration
│   └── mariadb/
│       └── init/        # Database initialization scripts
└── docker/              # Docker-related configurations
    ├── compose/         # Docker Compose override files
    │   ├── docker-compose.cloudflare.yml   # Cloudflare integration
    │   ├── docker-compose.production.yml   # Production configuration
    │   ├── docker-compose.staging.yml      # Staging environment
    │   └── docker-compose.secure.yml       # Security enhancements
    └── config/          # Service configuration files
        └── mariadb-secure.cnf  # MariaDB security configuration
```

## Usage

### Docker Compose Files

Use these override files with the main `docker-compose.yml`:

```bash
# Cloudflare Tunnel deployment
docker-compose -f docker-compose.yml -f infrastructure/docker/compose/docker-compose.cloudflare.yml up -d

# Production deployment
docker-compose -f docker-compose.yml -f infrastructure/docker/compose/docker-compose.production.yml up -d

# Staging deployment
docker-compose -f docker-compose.yml -f infrastructure/docker/compose/docker-compose.staging.yml up -d

# Secure deployment with enhanced security
docker-compose -f docker-compose.yml -f infrastructure/docker/compose/docker-compose.secure.yml up -d
```

### Cloudflare Configuration

The `cloudflare/` directory contains:
- `config.yml` - Tunnel configuration mapping subdomains to services
- `tunnel-credentials.json` - Auto-generated tunnel credentials (created by setup scripts)

### Database Configuration

The `database/` directory contains initialization scripts that:
- Create multiple databases for different tenants
- Set up initial user permissions
- Configure database schema

### Docker Configuration

The `docker/config/` directory contains service-specific configuration files:
- `mariadb-secure.cnf` - Enhanced security settings for MariaDB

## Setup Scripts

Use the setup scripts from the `scripts/` directory to automate infrastructure deployment:

```bash
# Setup Cloudflare Tunnel (Docker-only approach)
./scripts/setup-cloudflare-tunnel-docker.sh

# Setup Cloudflare Tunnel (traditional approach)
./scripts/setup-cloudflare-tunnel.sh
```

## Security Notes

- Never commit `tunnel-credentials.json` to version control
- Use environment variables for sensitive configuration
- Review security configurations before production deployment
- Regularly update Docker images and dependencies

## Related Documentation

- [Cloudflare Tunnel Deployment Guide](../docs/cloudflare-tunnel-deployment.md)
- [CI/CD Pipeline Guide](../docs/cicd-deployment-guide.md)
- [Deployment Setup Order](../docs/deployment-setup-order.md)