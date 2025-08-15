# API Documentation

## Central SSO API Endpoints

Base URL: `http://localhost:8000`

## Test Users

The following test users are available in the database:

| Email | Password | Tenant | Role | Description |
|-------|----------|--------|------|-------------|
| user@tenant1.com | tenant123 | tenant1 | User | Regular user for Tenant 1 |
| admin@tenant1.com | admin123 | tenant1 | Admin | Administrator for Tenant 1 |
| user@tenant2.com | tenant456 | tenant2 | User | Regular user for Tenant 2 |
| admin@tenant2.com | admin456 | tenant2 | Admin | Administrator for Tenant 2 |
| superadmin@sso.com | super123 | tenant1, tenant2 | Admin | Super admin with access to both tenants |

## Available Tenants

| Tenant ID | Slug | Name | Domain |
|-----------|------|------|--------|
| tenant1 | tenant1 | Tenant 1 | tenant1.local |
| tenant2 | tenant2 | Tenant 2 | tenant2.local |

### Authentication Endpoints

#### POST `/api/auth/login`
Authenticate user with credentials.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123",
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
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
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

### Tenant Management Endpoints

#### GET `/api/tenants`
Get list of available tenants (admin only).

**Headers:**
```
Authorization: Bearer {admin_jwt_token}
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
      "domain": "tenant1.localhost",
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

#### POST `/api/tenants`
Create new tenant (admin only).

**Request:**
```json
{
  "name": "New Tenant",
  "slug": "new-tenant",
  "domain": "new-tenant.localhost"
}
```

**Response:**
```json
{
  "success": true,
  "tenant": {
    "id": 2,
    "name": "New Tenant",
    "slug": "new-tenant",
    "domain": "new-tenant.localhost",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

#### GET `/api/tenants/{slug}/users`
Get users for specific tenant (admin only).

**Response:**
```json
{
  "success": true,
  "users": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "joined_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

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

**Features:**
- Request monitoring
- Database query inspection
- Exception tracking
- Job monitoring
- Cache operations
- Mail preview

### Database

The application uses SQLite for development:
- Database file: `/central-sso/database/database.sqlite`
- Migrations: Run with `php artisan migrate`
- Seeding: Run with `php artisan db:seed --class=TestDataSeeder`

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