# Configuration Templates

This directory contains environment configuration templates for different deployment scenarios.

## Template Files

### `templates/`
Environment configuration templates for various deployment scenarios:

- **`.env.docker`** - Default Docker Compose development setup
- **`.env.example`** - Comprehensive reference with all available variables
- **`.env.production.example`** - Production deployment template
- **`.env.home-server.example`** - Home server deployment template
- **`.env.cloudflare.example`** - Cloudflare tunnel deployment template

## Usage

1. **Copy the appropriate template:**
   ```bash
   cp config/templates/.env.docker .env
   ```

2. **Customize for your environment:**
   - Update application keys and secrets
   - Configure database credentials
   - Set appropriate URLs and domains
   - Adjust security settings

3. **Generate required secrets:**
   ```bash
   # Generate secure random keys
   php artisan key:generate
   php artisan jwt:secret
   
   # Or use the generate-secrets script
   ./tools/scripts/generate-secrets.sh
   ```

## Template Descriptions

### Development (`config/templates/.env.docker`)
- Pre-configured for Docker Compose development
- Uses localhost domains
- Debug mode enabled
- Development-friendly settings

### Production (`config/templates/.env.production.example`)
- Production-ready configuration
- Security hardened settings
- SSL/HTTPS enabled
- Performance optimized

### Home Server (`config/templates/.env.home-server.example`)
- Suitable for home lab deployments
- Cloudflare tunnel integration
- Self-hosted configuration

### Cloudflare (`config/templates/.env.cloudflare.example`)
- Cloudflare-specific settings
- Tunnel configuration
- SSL and security optimizations

## Security Notes

- **Never commit `.env` files** to version control
- **Use strong, unique secrets** for production
- **Rotate keys regularly** in production environments
- **Validate configuration** before deployment

## Quick Start

For a quick development setup:

```bash
# Copy default template
cp config/templates/.env.docker .env

# Start services
docker compose up -d

# Run migrations
docker exec central-sso php artisan migrate

# Seed test data
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder
```

See the main [README.md](../README.md) for complete setup instructions.