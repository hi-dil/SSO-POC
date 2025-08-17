# Getting Started

Welcome to the multi-tenant SSO system! This section provides everything you need to get up and running quickly.

## 📖 Documentation in This Section

### **[Quick Start Guide](quick-start.md)**
⏱️ **5-minute setup** - Get the system running with minimal configuration
- Prerequisites checklist
- One-command setup
- Basic testing

### **[Local Development Setup](local-development.md)**
🔧 **Complete development setup** - Detailed configuration for development work
- Environment configuration
- Database setup and seeding
- Testing and debugging tools
- Common development workflows

## 🎯 Learning Path

1. **Start Here**: [Quick Start Guide](quick-start.md) for immediate results
2. **Then**: [Local Development Setup](local-development.md) for development work
3. **Next**: [Architecture Overview](../architecture/README.md) to understand the system
4. **Finally**: [Deployment Guide](../deployment/README.md) for production

## 🔑 Test Credentials

All test users use password: **password**

| Email | Tenant Access | Role |
|-------|---------------|------|
| `superadmin@sso.com` | Both Tenants | Admin |
| `admin@tenant1.com` | Tenant 1 | Admin |
| `user@tenant1.com` | Tenant 1 | User |
| `admin@tenant2.com` | Tenant 2 | Admin |
| `user@tenant2.com` | Tenant 2 | User |

## 🌐 Default URLs

After setup, access these URLs:

- **Central SSO**: http://localhost:8000
- **Tenant 1**: http://localhost:8001
- **Tenant 2**: http://localhost:8002
- **Database**: localhost:3307 (MariaDB)

## 🆘 Need Help?

- **Common Issues**: [Troubleshooting Guide](../reference/troubleshooting.md)
- **Configuration**: [Configuration Reference](../reference/configuration.md)
- **Security**: [Security Guide](../guides/security.md)

## 📋 Prerequisites Checklist

Before starting, ensure you have:

- [ ] **Docker** and **Docker Compose** installed
- [ ] **Git** for version control
- [ ] **Text editor/IDE** for development
- [ ] **8GB+ RAM** available for containers
- [ ] **Ports 8000-8002, 3307** available

## What's Next?

After completing the getting started guides:

1. **Understand the System**: Read the [Architecture Overview](../architecture/README.md)
2. **Production Deployment**: Follow the [Deployment Guide](../deployment/README.md)
3. **Customization**: Check out the [Guides](../guides/) for specific tasks
4. **API Integration**: See the [API Reference](../reference/api.md)