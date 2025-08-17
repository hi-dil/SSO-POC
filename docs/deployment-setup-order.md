# Deployment Setup Order Guide

## Overview

This guide explains the recommended order for setting up Cloudflare Tunnel deployment and CI/CD pipeline for your multi-tenant SSO system. Following this sequence will minimize complexity and reduce troubleshooting time.

## 🎯 Recommended Setup Order

The correct approach is: **Manual Deployment First → CI/CD Pipeline Second**

### Why This Order?

Setting up the deployment infrastructure first allows you to test and validate your Cloudflare Tunnel configuration manually before automating it. This approach reduces complexity by ensuring your basic deployment works before adding CI/CD automation layers.

## Phase 1: Manual Deployment Setup ⭐ (Start Here)

### Prerequisites (5 minutes)

Before starting, ensure you have:

1. **Cloudflare Account Setup**
   - Domain `hi-dil.com` added to Cloudflare
   - Nameservers pointed to Cloudflare
   - Domain showing as "Active" in Cloudflare dashboard

2. **API Access**
   - Cloudflare API token with Zone:Edit permissions
   - Account email address

3. **Local Environment**
   - Docker and Docker Compose installed
   - Git repository cloned locally
   - Terminal access to project directory

### Step 1: Basic Cloudflare Tunnel Setup (30 minutes)

#### Environment Setup
```bash
# Navigate to your project directory
cd /path/to/sso-poc-claude3

# Set required environment variables
export CLOUDFLARE_API_TOKEN="your-cloudflare-api-token"
export CLOUDFLARE_EMAIL="your-cloudflare-email@example.com"
```

#### Automated Setup
```bash
# Make the setup script executable
chmod +x scripts/setup-cloudflare-tunnel-docker.sh

# Run the Docker-only setup
./scripts/setup-cloudflare-tunnel-docker.sh
```

#### Manual Setup Alternative
If you prefer to understand each step:

```bash
# 1. Create Cloudflare tunnel
mkdir -p cloudflare
docker run --rm \
    -v "$(pwd)/cloudflare:/output" \
    -e CLOUDFLARE_API_TOKEN="$CLOUDFLARE_API_TOKEN" \
    cloudflare/cloudflared:latest \
    tunnel create sso-poc-tunnel

# 2. Extract tunnel ID
TUNNEL_ID=$(cat cloudflare/tunnel-credentials.json | grep -o '"TunnelID":"[^"]*"' | cut -d'"' -f4)
echo $TUNNEL_ID > cloudflare/tunnel-id.txt

# 3. Create DNS records via API
ZONE_ID=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones?name=hi-dil.com" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" | \
    grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

for SUBDOMAIN in "sso.poc" "tenant-one.poc" "tenant-two.poc"; do
    curl -X POST "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
        -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
        -H "Content-Type: application/json" \
        --data "{
            \"type\": \"CNAME\",
            \"name\": \"$SUBDOMAIN\",
            \"content\": \"$TUNNEL_ID.cfargotunnel.com\",
            \"proxied\": true
        }"
done

# 4. Deploy with Docker Compose
docker-compose -f docker-compose.yml -f docker-compose.cloudflare.yml up -d
```

### Step 2: Verify Deployment (15 minutes)

#### Basic Connectivity Test
```bash
# Test that all domains respond
echo "Testing domain connectivity..."
curl -I https://sso.poc.hi-dil.com
curl -I https://tenant-one.poc.hi-dil.com
curl -I https://tenant-two.poc.hi-dil.com

# Check for proper HTTP response codes (200 or 302)
echo "Testing health endpoints..."
curl https://sso.poc.hi-dil.com/health
curl https://tenant-one.poc.hi-dil.com/health
curl https://tenant-two.poc.hi-dil.com/health
```

#### Container Status Check
```bash
# Verify all containers are running
docker-compose ps

# Check tunnel status
docker logs cloudflared-tunnel --tail 20

# Check tunnel metrics
curl http://localhost:9090/metrics
```

#### DNS Propagation Check
```bash
# Test DNS resolution globally
dig sso.poc.hi-dil.com
dig tenant-one.poc.hi-dil.com
dig tenant-two.poc.hi-dil.com

# Check from external DNS resolver
nslookup sso.poc.hi-dil.com 8.8.8.8
```

### Step 3: Validate SSO Functionality (15 minutes)

#### Manual SSO Testing
```bash
# Run comprehensive SSO flow test
./scripts/test-sso-flow.sh production
```

#### Browser Testing
1. **Open Central SSO**: Navigate to `https://sso.poc.hi-dil.com`
2. **Test Login**: Use test credentials:
   - Email: `superadmin@sso.com`
   - Password: `password`
3. **Test Tenant Access**: Navigate to tenant applications:
   - `https://tenant-one.poc.hi-dil.com`
   - `https://tenant-two.poc.hi-dil.com`
4. **Verify Cross-Tenant Access**: Ensure superadmin can access both tenants

#### Database Operations
```bash
# Run database migrations (if needed)
docker exec central-sso php artisan migrate --force

# Seed test data (if needed)
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder
```

### Troubleshooting Manual Setup

#### Common Issues and Solutions

**1. DNS Not Resolving**
```bash
# Check if DNS records were created
curl -X GET "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/dns_records" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"

# Verify tunnel ID is correct
cat cloudflare/tunnel-id.txt
```

**2. 502 Bad Gateway Errors**
```bash
# Check if applications are running
docker-compose ps | grep -E "(central-sso|tenant)"

# Check application logs
docker-compose logs central-sso
docker-compose logs tenant1-app

# Check tunnel connectivity
docker exec cloudflared-tunnel nslookup central-sso
```

**3. SSL Certificate Issues**
```bash
# Check SSL certificate status
curl -vI https://sso.poc.hi-dil.com 2>&1 | grep -E "(certificate|SSL)"

# Verify Cloudflare proxy is enabled
# Orange cloud should be enabled in Cloudflare DNS settings
```

**4. Authentication Issues**
```bash
# Check database connectivity
docker exec central-sso php artisan tinker
# In tinker: DB::connection()->getPdo();

# Verify user data
docker exec central-sso php artisan user:list
```

## Phase 2: CI/CD Pipeline Setup (After Manual Works)

Once your manual deployment is working correctly, proceed to automate it with CI/CD.

### Step 1: Configure GitHub Repository (10 minutes)

#### Required Secrets Setup
Navigate to GitHub repository → Settings → Secrets and variables → Actions

Add these secrets:

```
# Cloudflare Configuration
CLOUDFLARE_API_TOKEN          # Your Cloudflare API token
CLOUDFLARE_EMAIL              # Your Cloudflare account email

# Database Configuration  
DB_USERNAME                   # Database username (sso_user)
DB_PASSWORD                   # Secure database password
MYSQL_ROOT_PASSWORD          # MySQL root password

# Application Secrets
JWT_SECRET                   # JWT signing secret (32+ characters)
REDIS_PASSWORD              # Redis password
TENANT1_API_KEY             # Tenant 1 API key
TENANT2_API_KEY             # Tenant 2 API key
TENANT1_HMAC_SECRET         # HMAC secret for tenant 1
TENANT2_HMAC_SECRET         # HMAC secret for tenant 2

# Email Configuration (Optional)
MAIL_HOST                   # SMTP host
MAIL_USERNAME               # SMTP username
MAIL_PASSWORD               # SMTP password
MAIL_FROM_ADDRESS           # From email address

# Monitoring (Optional)
GRAFANA_PASSWORD            # Grafana admin password
SLACK_WEBHOOK_URL           # Slack webhook for notifications
```

#### Verify Secrets
```bash
# Create a test workflow to verify secrets are accessible
# The existing CI/CD pipeline will automatically test this
```

### Step 2: Test Staging Deployment (30 minutes)

#### Create Staging Branch
```bash
# Create and push staging branch
git checkout -b staging
git push origin staging
```

#### Monitor Deployment
1. **GitHub Actions**: Navigate to repository → Actions tab
2. **Watch Pipeline**: Monitor "Multi-Tenant SSO CI/CD Pipeline" workflow
3. **Check Logs**: Review each step for any errors

#### Verify Staging Environment
```bash
# Once deployment completes, test staging URLs
curl https://staging-sso.poc.hi-dil.com/health
curl https://staging-tenant-one.poc.hi-dil.com/health
curl https://staging-tenant-two.poc.hi-dil.com/health

# Test staging SSO flow
./scripts/test-sso-flow.sh staging
```

### Step 3: Test Production Deployment (30 minutes)

#### Deploy to Production
```bash
# Merge staging to main for production deployment
git checkout main
git merge staging
git push origin main
```

#### Manual Approval
1. **GitHub Actions**: Go to Actions tab
2. **Find Workflow**: Locate the running workflow
3. **Approve Deployment**: Click "Review deployments" → "production" → "Approve and deploy"

#### Monitor Blue-Green Deployment
The CI/CD pipeline will:
1. Backup the production database
2. Deploy to the inactive color (blue/green)
3. Run health checks
4. Switch traffic to new deployment
5. Scale down old deployment

#### Verify Production
```bash
# Test production endpoints
curl https://sso.poc.hi-dil.com/health
curl https://tenant-one.poc.hi-dil.com/health
curl https://tenant-two.poc.hi-dil.com/health

# Test production SSO flow
./scripts/test-sso-flow.sh production

# Test blue-green switching (manual)
./scripts/blue-green-switch.sh green
./scripts/blue-green-switch.sh blue
```

## Success Indicators

### Manual Setup Complete When:
- ✅ All three domains (sso, tenant-one, tenant-two) resolve and respond
- ✅ Can login to Central SSO via web interface
- ✅ Can access tenant applications without errors
- ✅ SSO authentication flow works between applications
- ✅ Cloudflare tunnel shows healthy status in metrics
- ✅ SSL certificates are properly issued and valid
- ✅ Database operations work correctly

### CI/CD Setup Complete When:
- ✅ Staging deployment works automatically on staging branch push
- ✅ Production deployment requires manual approval and completes successfully
- ✅ Blue-green deployment switches traffic without downtime
- ✅ Health checks pass after deployment
- ✅ Rollback procedures work when tested
- ✅ Team receives deployment notifications via Slack/email
- ✅ All tests pass in the CI/CD pipeline

## Quick Start Checklist (1-2 Hours Total)

### Hour 1: Manual Deployment
- [ ] **Prerequisites** (5 min): Verify Cloudflare account, API token, Docker
- [ ] **Setup** (30 min): Run `setup-cloudflare-tunnel-docker.sh`
- [ ] **Test** (15 min): Verify all domains work and SSO flows
- [ ] **Debug** (10 min): Fix any issues found

### Hour 2: CI/CD Pipeline  
- [ ] **Secrets** (15 min): Add all required GitHub secrets
- [ ] **Staging** (20 min): Test staging branch deployment
- [ ] **Production** (20 min): Test production deployment with approval
- [ ] **Validation** (5 min): Verify everything works end-to-end

## Benefits of This Approach

### Manual First Advantages:
1. **🔍 Understanding**: Learn how Cloudflare Tunnel works
2. **🐛 Quick Debugging**: Fix infrastructure issues without CI/CD complexity
3. **⚡ Fast Iteration**: Test configuration changes immediately
4. **📚 Learning**: Understand the deployment process thoroughly
5. **🛡️ Risk Reduction**: Validate configuration before automation
6. **🧪 Testing**: Ensure all components work together

### CI/CD Second Advantages:
1. **🤖 Automation**: Automate the proven manual process
2. **🔄 Repeatability**: Ensure consistent deployments every time
3. **👥 Team Collaboration**: Enable multiple developers to deploy safely
4. **📊 Tracking**: Maintain deployment history and enable rollbacks
5. **🚀 Scaling**: Support multiple environments (staging, production)
6. **🔒 Security**: Automated security scans and approval workflows

## Common Pitfalls to Avoid

### Don't Start with CI/CD Because:
- **Complex Debugging**: Hard to troubleshoot automation + infrastructure issues simultaneously
- **Multiple Variables**: Too many things can go wrong at once
- **Slow Feedback Loop**: CI/CD takes longer to test configuration changes
- **Configuration Complexity**: May waste time on secrets/environment setup before knowing if basic setup works
- **Learning Curve**: Harder to understand what should happen vs. what is happening

### Don't Skip Manual Setup Because:
- **Missing Foundation**: Won't understand what the automation should accomplish
- **No Baseline**: Can't distinguish between automation issues and infrastructure problems
- **Emergency Preparedness**: Need manual deployment skills for emergency situations
- **Troubleshooting Skills**: Manual knowledge essential for debugging CI/CD issues

## Troubleshooting Guide

### Manual Setup Issues

**Cloudflare API Errors**
```bash
# Test API token permissions
curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"

# Check zone access
curl -X GET "https://api.cloudflare.com/client/v4/zones?name=hi-dil.com" \
    -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN"
```

**Docker Issues**
```bash
# Check Docker daemon
docker version

# Check Docker Compose
docker-compose version

# Check available resources
docker system df
docker system prune -f  # If low on space
```

**Network Connectivity**
```bash
# Test from inside containers
docker exec cloudflared-tunnel ping central-sso
docker exec central-sso ping mariadb

# Check internal DNS resolution
docker exec cloudflared-tunnel nslookup central-sso
```

### CI/CD Pipeline Issues

**GitHub Actions Failing**
```bash
# Check workflow syntax
# Go to Actions → Failed workflow → View raw logs

# Test secrets access
# Add temporary echo statements in workflow (remove after testing)
```

**Deployment Timeouts**
```bash
# Check resource usage during deployment
docker stats

# Monitor deployment progress
docker-compose logs -f --tail 100
```

**Blue-Green Switching Issues**
```bash
# Check current deployment color
./scripts/blue-green-switch.sh --status

# Manual traffic switch
./scripts/blue-green-switch.sh blue
./scripts/blue-green-switch.sh green
```

## Next Steps

After completing both phases:

1. **Set up Monitoring**: Implement Prometheus and Grafana monitoring (see `docs/prometheus-grafana-monitoring.md`)
2. **Configure Alerts**: Set up Slack notifications and alert rules
3. **Team Training**: Train team members on deployment procedures
4. **Documentation**: Create operational runbooks for your specific environment
5. **Optimization**: Fine-tune performance based on actual usage patterns

## Related Documentation

- **Cloudflare Tunnel Setup**: `docs/cloudflare-tunnel-deployment.md`
- **Docker-Only Setup**: `docs/cloudflare-docker-only-setup.md`
- **CI/CD Pipeline**: `docs/cicd-deployment-guide.md`
- **Application Configuration**: `docs/cloudflare-application-config.md`
- **Monitoring Setup**: `docs/prometheus-grafana-monitoring.md`

Following this order ensures a smooth, predictable deployment process with minimal downtime and maximum understanding of your infrastructure.