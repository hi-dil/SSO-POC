# API Documentation

## Central SSO API Endpoints

Base URL: `http://localhost:8000`

### Interactive API Documentation

- **Swagger UI**: `http://localhost:8000/api/documentation`
- **Quick Access**: `http://localhost:8000/docs` (redirects to Swagger UI)
- **JSON Schema**: `http://localhost:8000/api/api-docs.json`

*Note: API documentation access requires `swagger.access` permission*

## Test Users

The following test users are available with password: **password**

| Email | Tenant Access | Role in Central SSO | Description |
|-------|---------------|---------------------|-------------|
| superadmin@sso.com | tenant1, tenant2 | Super Admin | Full system access with all permissions |
| admin@tenant1.com | tenant1 | Admin | Tenant 1 administrator |
| user@tenant1.com | tenant1 | User | Regular user for Tenant 1 |
| admin@tenant2.com | tenant2 | Admin | Tenant 2 administrator |
| user@tenant2.com | tenant2 | User | Regular user for Tenant 2 |

### Central SSO Roles and Permissions

| Role | Permissions | Description |
|------|-------------|-------------|
| Super Admin | All 19 permissions | Complete system access |
| Admin | All except system.* | General administration |
| Manager | users.*, roles.view, tenants.view | User and basic tenant management |
| User | Limited view permissions | Standard user access |
| Viewer | Read-only permissions | Monitoring and reporting |

## Available Tenants

| Tenant ID | Slug | Name | Domain |
|-----------|------|------|--------|
| tenant1 | tenant1 | Tenant 1 Application | localhost:8001 |
| tenant2 | tenant2 | Tenant 2 Application | localhost:8002 |

### Authentication Endpoints

#### POST `/api/auth/login`
Authenticate user with credentials.

**Request:**
```json
{
  "email": "user@tenant1.com",
  "password": "password",
  "tenant_slug": "tenant1"
}
```

**Response (Success):**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "tenants": ["tenant1", "tenant2"],
    "current_tenant": "tenant1"
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Invalid credentials",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

#### POST `/api/auth/register`
Register new user account.

**Request:**
```json
{
  "name": "John Doe",
  "email": "newuser@tenant1.com",
  "password": "password",
  "password_confirmation": "password",
  "tenant_slug": "tenant1"
}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "tenants": ["tenant1"]
  }
}
```

#### GET `/api/auth/user`
Get current authenticated user information.

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "current_tenant": "tenant1",
    "tenants": ["tenant1", "tenant2"]
  }
}
```

#### POST `/api/auth/validate`
Validate JWT token.

**Request:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "tenant_slug": "tenant1"
}
```

**Response (Success):**
```json
{
  "valid": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "tenants": ["tenant1", "tenant2"]
  }
}
```

**Response (Invalid Token for Tenant):**
```json
{
  "valid": false,
  "message": "Token not valid for this tenant"
}
```

**Response (Invalid Token):**
```json
{
  "valid": false,
  "message": "Token is invalid"
}
```

#### POST `/api/auth/refresh`
Refresh expired JWT token.

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "expires_in": 3600
}
```

#### POST `/api/auth/logout`
Logout user (invalidate token).

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

### Role Management Endpoints

*Requires appropriate permissions for each endpoint*

#### GET `/api/roles`
List all roles with their permissions.

**Required Permission:** `roles.view`

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Super Admin",
      "slug": "super-admin",
      "description": "Complete system access",
      "is_system": true,
      "permissions": [
        {
          "id": 1,
          "name": "View Users",
          "slug": "users.view",
          "category": "users"
        }
      ]
    }
  ]
}
```

#### POST `/api/roles`
Create a new custom role.

**Required Permission:** `roles.create`

**Request:**
```json
{
  "name": "Content Manager",
  "description": "Manages content and users",
  "permissions": ["users.view", "users.edit", "tenants.view"]
}
```

**Response:**
```json
{
  "data": {
    "id": 6,
    "name": "Content Manager",
    "slug": "content-manager",
    "description": "Manages content and users",
    "is_system": false,
    "permissions": [...]
  }
}
```

#### PUT `/api/roles/{id}`
Update an existing role.

**Required Permission:** `roles.edit`

**Request:**
```json
{
  "name": "Updated Role Name",
  "description": "Updated description",
  "permissions": ["users.view", "roles.view"]
}
```

#### DELETE `/api/roles/{id}`
Delete a custom role (system roles protected).

**Required Permission:** `roles.delete`

**Response:**
```json
{
  "message": "Role deleted successfully"
}
```

#### GET `/api/permissions`
List all available permissions.

**Required Permission:** `roles.view`

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "View Users",
      "slug": "users.view",
      "category": "users",
      "description": "Can view user lists and details",
      "is_system": true
    }
  ]
}
```

#### GET `/api/permissions/categories`
Get permission categories.

**Response:**
```json
{
  "data": ["users", "roles", "tenants", "system", "api", "developer"]
}
```

#### GET `/api/users/{id}/roles`
Get roles assigned to a user.

**Required Permission:** `roles.view`

**Response:**
```json
{
  "data": [
    {
      "role": {
        "id": 1,
        "name": "Admin",
        "slug": "admin"
      },
      "tenant_id": null,
      "assigned_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### POST `/api/users/{id}/roles`
Assign a role to a user.

**Required Permission:** `roles.assign`

**Request:**
```json
{
  "role_slug": "manager",
  "tenant_id": 1  // Optional: for tenant-specific assignment
}
```

**Response:**
```json
{
  "message": "Role assigned successfully"
}
```

#### DELETE `/api/users/{id}/roles`
Remove a role from a user.

**Required Permission:** `roles.assign`

**Request:**
```json
{
  "role_slug": "manager",
  "tenant_id": 1  // Optional: for tenant-specific removal
}
```

### Tenant Management Endpoints

#### GET `/api/tenants`
Get list of available tenants.

**Required Permission:** `tenants.view`

**Headers:**
```
Authorization: Bearer {jwt_token}
```

**Response:**
```json
{
  "success": true,
  "tenants": [
    {
      "id": 1,
      "name": "Tenant One",
      "slug": "tenant1",
      "domain": "localhost:8001",
      "is_active": true,
      "users_count": 5,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### POST `/api/tenants`
Create new tenant.

**Required Permission:** `tenants.create`

**Request:**
```json
{
  "name": "New Tenant",
  "slug": "new-tenant",
  "domain": "localhost:8003",
  "description": "Description of the new tenant",
  "max_users": 100,
  "is_active": true
}
```

#### PUT `/api/tenants/{id}`
Update tenant information.

**Required Permission:** `tenants.edit`

#### DELETE `/api/tenants/{id}`
Delete a tenant (only if no users assigned).

**Required Permission:** `tenants.delete`

#### GET `/api/tenants/{id}/users`
Get users for specific tenant.

**Required Permission:** `tenants.view`

### Web Routes (SSO Flow)

#### GET `/auth/{tenant_slug}`
SSO login page for specific tenant.

**Parameters:**
- `tenant_slug` - The tenant identifier
- `callback_url` (query) - URL to redirect after successful auth

**Example:**
```
GET /auth/tenant1?callback_url=http://tenant1.localhost:8001/auth/callback
```

#### GET `/register`
Central registration page with tenant selection.

#### GET `/dashboard`
Admin dashboard for tenant management (admin only).

## Error Responses

### Common Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 400 | Bad Request | Invalid request format or missing parameters |
| 401 | Unauthorized | Invalid or expired token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 500 | Internal Server Error | Server error |

### Error Response Format

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

## Development Tools

### Laravel Telescope

Laravel Telescope is available for debugging and monitoring in the development environment.

**Access URL:** `http://localhost:8000/telescope`
**Required Permission:** `telescope.access`

**Features:**
- Request monitoring
- Database query inspection
- Exception tracking
- Job monitoring
- Cache operations
- Mail preview

### API Documentation (Swagger/OpenAPI)

Interactive API documentation powered by Swagger/OpenAPI 3.0.

**Access URLs:**
- **Swagger UI**: `http://localhost:8000/api/documentation`
- **Quick Access**: `http://localhost:8000/docs`
- **JSON Schema**: `http://localhost:8000/api/api-docs.json`

**Required Permission:** `swagger.access`

**Features:**
- Interactive API testing
- Request/response schemas
- Authentication examples
- Rate limiting information
- Error code documentation

### Docker Environment

The application runs in Docker containers:

```bash
# Start all services
docker compose up -d

# Check running containers
docker ps

# View logs
docker compose logs central-sso
docker compose logs mariadb

# Execute commands in containers
docker exec central-sso php artisan migrate
docker exec mariadb mysql -u sso_user -psso_password sso_main
```

**Container Services:**
- **central-sso**: Main SSO application (port 8000)
- **tenant1-app**: Tenant 1 application (port 8001) 
- **tenant2-app**: Tenant 2 application (port 8002)
- **mariadb**: Database server (port 3306)

### Database

The application uses MariaDB via Docker Compose:
- **Database**: MariaDB running in Docker container
- **Connection**: Via Docker network (`mariadb` host)
- **Database Name**: `sso_main`
- **Migrations**: Run with `docker exec central-sso php artisan migrate`
- **Seeding**: Run with `docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder`

#### Direct Database Access
```bash
# Connect to MariaDB
docker exec -it mariadb mysql -u sso_user -psso_password sso_main

# Check users
SELECT email, name, is_admin FROM users;

# Check tenant relationships
SELECT u.email, t.id as tenant_id FROM users u 
JOIN tenant_users tu ON u.id = tu.user_id 
JOIN tenants t ON tu.tenant_id = t.id;
```

## Rate Limiting

Authentication endpoints are rate limited to prevent abuse:

- Login attempts: 5 per minute per IP
- Registration: 3 per minute per IP
- Token validation: 60 per minute per IP

Rate limit headers included in responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995260
```