# Multi-Tenant SSO Proof of Concept

This documentation covers the architecture, implementation, and usage of a multi-tenant Single Sign-On (SSO) system built with Laravel and Docker.

## Documentation Structure

- [Architecture Overview](./architecture.md) - System design and component relationships
- [Authentication Flow](./authentication-flow.md) - Detailed auth workflows and JWT handling
- [API Documentation](./api-documentation.md) - Central SSO API endpoints and usage
- [Setup Guide](./setup-guide.md) - Local development setup with Docker
- [Tenant Management](./tenant-management.md) - How tenancy works with Stancl package
- [Database Schema](./database-schema.md) - Database structure for multi-tenancy
- [Testing Guide](./testing-guide.md) - How to test the SSO integration

## Quick Start

1. Clone the repository
2. Run `docker-compose up -d`
3. Access:
   - Central SSO: `http://sso.localhost:8000`
   - Tenant 1: `http://tenant1.localhost:8001`
   - Tenant 2: `http://tenant2.localhost:8002`

## Key Features

- **Multi-tenant architecture** with separate databases per tenant
- **Dual authentication methods** (SSO redirect + local forms)
- **JWT-based authentication** for stateless auth
- **Admin-only tenant creation** with user management
- **Cross-tenant user access** for users belonging to multiple tenants
- **Dockerized development environment** for easy setup