# Authentication Flow

## System Architecture Overview

```mermaid
graph TB
    subgraph "Central SSO Server"
        SSO[Central SSO<br/>localhost:8000]
        DB[(MariaDB)]
        JWT[JWT Service]
        SSO --> DB
        SSO --> JWT
    end
    
    subgraph "Tenant 1 Application"
        T1[Tenant 1 App<br/>localhost:8001]
    end
    
    subgraph "Tenant 2 Application"
        T2[Tenant 2 App<br/>localhost:8002]
    end
    
    U[User Browser]
    
    U -.->|Login/Register| SSO
    U -.->|Access| T1
    U -.->|Access| T2
    T1 -->|Validate Token| SSO
    T2 -->|Validate Token| SSO
```

## Authentication Flows

### 🔄 Seamless SSO Flow (New Implementation)

```mermaid
sequenceDiagram
    participant U as User
    participant T as Tenant App<br/>(localhost:8001)
    participant SSO as Central SSO<br/>(localhost:8000)
    participant S as Session/Cookies
    
    Note over U,S: Already Authenticated User
    U->>T: 1. Visit tenant app
    U->>T: 2. Click "Login with SSO"
    T->>U: 3. Redirect to SSO Processing Page
    U->>SSO: 4. GET /auth/tenant1?callback_url=...
    SSO->>U: 5. Show processing page (loading state)
    
    Note over U,S: JavaScript Authentication Check
    U->>SSO: 6. AJAX: GET /auth/tenant1/check
    SSO->>S: 7. Check existing session
    S->>SSO: 8. Session valid + user authenticated
    SSO->>SSO: 9. Verify tenant access
    SSO->>SSO: 10. Generate JWT token
    SSO->>U: 11. JSON: {authenticated: true, redirect_to: "..."}
    U->>U: 12. JavaScript auto-redirect
    U->>T: 13. Callback with JWT token
    T->>T: 14. Validate token & authenticate user
    T->>U: 15. Redirect to protected content
```

### 🎯 Processing Page Benefits

- **Seamless UX**: No unnecessary login forms for authenticated users
- **Fast Response**: JavaScript-based checking provides immediate feedback
- **Loading State**: Users see clear progress indication
- **Cross-Origin Compatible**: Works despite browser cookie isolation between ports
- **Graceful Fallback**: Always provides login form when needed
- **Security Maintained**: Proper tenant access validation and JWT generation

### 🔧 Technical Implementation

The processing page (`auth.sso-processing.blade.php`) uses JavaScript to:

1. **Show Loading State**: Immediate visual feedback to user
2. **AJAX Authentication Check**: Call `/auth/{tenant}/check` endpoint
3. **Handle Response States**:
   - `authenticated: true, redirect_to: "..."` → Auto-redirect
   - `authenticated: true, access_denied: true` → Show access denied
   - `authenticated: false` → Show login form
4. **Error Handling**: Network failures gracefully fall back to login form

## Standard Authentication Flow

### Complete Login Flow with Token Validation

```mermaid
sequenceDiagram
    participant U as User
    participant T as Tenant App<br/>(localhost:8001)
    participant SSO as Central SSO<br/>(localhost:8000)
    participant DB as Database
    
    Note over U,DB: User Login Process
    U->>T: 1. Visit tenant app
    U->>T: 2. Click "Login with SSO"
    T->>T: 3. Store return URL in session
    T->>U: 4. Redirect to SSO
    U->>SSO: 5. GET /auth/tenant1?callback_url=...
    SSO->>U: 6. Show login form
    U->>SSO: 7. Submit credentials
    SSO->>DB: 8. Verify user exists
    DB->>SSO: 9. User data + password hash
    SSO->>SSO: 10. Verify password
    SSO->>DB: 11. Check tenant access
    DB->>SSO: 12. Tenant relationships
    SSO->>SSO: 13. Generate JWT with claims:<br/>- user_id<br/>- tenants: ["tenant1"]<br/>- current_tenant: "tenant1"
    SSO->>T: 14. Redirect with token
    
    Note over T,DB: Token Validation
    T->>SSO: 15. POST /api/auth/validate<br/>{token, tenant_slug: "tenant1"}
    SSO->>SSO: 16. Decode JWT
    SSO->>SSO: 17. Verify tenant in token.tenants[]
    SSO->>T: 18. Return validation result
    T->>T: 19. Create session
    T->>U: 20. User logged in
```

### 🏗️ Dual-Session Architecture (Direct Login)

```mermaid
sequenceDiagram
    participant U as User
    participant T as Tenant App
    participant API as SSO API
    participant DB as Database
    participant S as Local Session
    
    Note over U,S: Direct Login to Tenant App
    U->>T: 1. Fill login form on tenant app
    T->>API: 2. POST /api/auth/login<br/>{email, password, tenant_slug}
    API->>DB: 3. Query user by email
    DB->>API: 4. User data
    API->>API: 5. Verify password hash
    API->>DB: 6. Check hasAccessToTenant()
    DB->>API: 7. Tenant relationships
    API->>API: 8. Generate JWT with custom claims
    API->>T: 9. Return {token, user}
    
    Note over T,S: Local Session Creation
    T->>T: 10. Create/update local user record
    T->>S: 11. Create Laravel session
    T->>S: 12. Store JWT token + SSO user data
    T->>U: 13. Set session cookie & redirect
    T->>U: 14. User logged in with local session
```

### 🔄 Dual-Session Benefits

- **🎯 Centralized Authentication**: All credentials validated by central SSO
- **⚡ Local Session Management**: Each tenant manages independent sessions
- **🚀 Performance**: Reduced API calls after initial authentication
- **🔄 User Data Sync**: Local users auto-sync with central SSO on login
- **📊 Audit Trail**: All authentications tracked in central audit system
- **🛡️ Security**: Consistent authentication across all tenant apps
- **🔧 Flexibility**: Tenant-specific session lifetimes and configurations

### API-Based Login Flow (Legacy)

```mermaid
sequenceDiagram
    participant U as User
    participant T as Tenant App
    participant API as SSO API
    participant DB as Database
    
    Note over U,DB: Legacy Flow (API Only)
    U->>T: 1. Fill login form on tenant app
    T->>API: 2. POST /api/auth/login<br/>{email, password, tenant_slug}
    API->>DB: 3. Query user by email
    DB->>API: 4. User data
    API->>API: 5. Verify password hash
    API->>DB: 6. Check hasAccessToTenant()
    DB->>API: 7. Tenant relationships
    API->>API: 8. Generate JWT with custom claims
    API->>T: 9. Return {token, user}
    T->>T: 10. Store token in session
    T->>U: 11. User logged in
```

## Session Management

### 🔄 Dual-Session Data Storage

In the dual-session architecture, each tenant app maintains both:

#### Local Laravel Session
```php
// Standard Laravel session data
session([
    '_token' => 'CSRF_TOKEN',
    'login_web_AUTH_ID' => 123,  // Local user ID
    'login_web_AUTH_PASSWORD_HASH' => 'hash',
    
    // SSO Integration Data
    'jwt_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...',
    'sso_user_data' => [
        'id' => 25,           // Central SSO user ID
        'name' => 'Super Admin',
        'email' => 'superadmin@sso.com',
        'tenants' => ['tenant1', 'tenant2'],
        'current_tenant' => 'tenant1',
        'is_admin' => true
    ]
]);
```

#### Session Data Usage
- **Local User ID**: For Laravel authentication and database relations
- **JWT Token**: For API calls to central SSO (if needed)
- **SSO User Data**: Rich user information from central SSO
- **CSRF Protection**: Standard Laravel CSRF tokens
- **Session Security**: HTTP-only cookies with proper expiration

### Session Lifecycle
```mermaid
flowchart LR
    A[Login] --> B[Central SSO Auth]
    B --> C[Create Local User]
    C --> D[Laravel Session]
    D --> E[Store JWT + SSO Data]
    E --> F[Session Cookie]
    F --> G[Protected Access]
    G --> H[Logout]
    H --> I[Clear All Session Data]
```

## JWT Token Structure

### Current Token Payload Example
```json
{
  "iss": "http://localhost:8000/api/auth/login",
  "iat": 1755262220,
  "exp": 1755265820,
  "nbf": 1755262220,
  "jti": "BYh8R81OjJrzOHH8",
  "sub": "2",
  "prv": "23bd5c8949f600adb39e701c400872db7a5976f7",
  "tenants": ["tenant1", "tenant2"],
  "current_tenant": "tenant1"
}
```

### Token Claims Explanation
- `sub`: User ID from central SSO
- `tenants`: Array of tenant slugs user has access to
- `current_tenant`: The tenant context for this session
- `exp`: Token expiration (1 hour by default)
- `prv`: Provider hash for user model verification

## User Registration Flow

```mermaid
sequenceDiagram
    participant U as User
    participant T as Tenant App
    participant API as SSO API
    participant DB as Database
    
    U->>T: 1. Visit registration page
    U->>T: 2. Submit registration form
    T->>API: 3. POST /api/auth/register<br/>{name, email, password, tenant_slug}
    API->>DB: 4. Check if email exists
    DB->>API: 5. Email availability
    API->>DB: 6. Check if tenant exists
    DB->>API: 7. Tenant data
    API->>DB: 8. Create user record
    API->>DB: 9. Create tenant_users relationship
    API->>API: 10. Generate JWT with tenant claims
    API->>T: 11. Return {token, user}
    T->>T: 12. Auto-login user
    T->>U: 13. User registered & logged in
```

## Token Validation Process

```mermaid
sequenceDiagram
    participant T as Tenant App
    participant API as SSO API
    participant JWT as JWT Service
    
    T->>API: 1. POST /api/auth/validate<br/>{token, tenant_slug}
    API->>JWT: 2. Parse and authenticate token
    JWT->>API: 3. Decoded payload + user
    API->>API: 4. Extract tenants from payload
    API->>API: 5. Check: tenant_slug in tenants[]?
    
    alt Token Valid for Tenant
        API->>T: 6a. {valid: true, user: {...}}
    else Token Invalid for Tenant
        API->>T: 6b. {valid: false,<br/>message: "Token not valid for this tenant"}
    else Token Invalid/Expired
        API->>T: 6c. {valid: false,<br/>message: "Token is invalid"}
    end
```

## Database Schema

### Users Table (MariaDB)
- `id`: Primary key (auto-increment)
- `name`: User's full name
- `email`: Unique email address
- `password`: Bcrypt hashed password
- `is_admin`: Boolean admin flag (0/1)
- `created_at`, `updated_at`: Timestamps

### Tenants Table (MariaDB)
- `id`: String primary key (e.g., "tenant1")
- `data`: JSON field (can be null or empty)
- `created_at`, `updated_at`: Timestamps

### Tenant_Users Table (Pivot)
- `user_id`: Foreign key to users table
- `tenant_id`: Foreign key to tenants table
- `created_at`, `updated_at`: Timestamps

### Database Access
```bash
# Connect to MariaDB
docker exec -it mariadb mysql -u sso_user -psso_password sso_main

# View users
SELECT id, email, name, is_admin FROM users;

# View tenant relationships
SELECT u.email, t.id as tenant_id 
FROM users u 
JOIN tenant_users tu ON u.id = tu.user_id 
JOIN tenants t ON tu.tenant_id = t.id;
```

## Error Scenarios

### Login Errors

```mermaid
flowchart TD
    A[User Submits Login] --> B{Email Exists?}
    B -->|No| C[Error: Invalid credentials]
    B -->|Yes| D{Password Correct?}
    D -->|No| C
    D -->|Yes| E{Has Tenant Access?}
    E -->|No| F[Error: Access denied to tenant]
    E -->|Yes| G{JWT Generation}
    G -->|Failed| H[Error: Could not create token]
    G -->|Success| I[Login Successful]
```

### Token Validation Errors

```mermaid
flowchart TD
    A[Validate Token Request] --> B{Token Present?}
    B -->|No| C[Error: Token required]
    B -->|Yes| D{Token Valid?}
    D -->|No| E[Error: Token is invalid]
    D -->|Yes| F{Tenant in Claims?}
    F -->|No| G[Error: Token not valid for this tenant]
    F -->|Yes| H[Validation Successful]
```

## Security Considerations

1. **Password Security**
   - Passwords hashed using Bcrypt with 12 rounds
   - Never stored or transmitted in plain text

2. **JWT Security**
   - Tokens signed with HMAC-SHA256
   - 1-hour expiration by default
   - Contains minimal user information

3. **Tenant Isolation**
   - Users can only access tenants they're explicitly assigned to
   - Token validation enforces tenant boundaries
   - Each request validates tenant context

4. **Session Management**
   - Tokens can be invalidated on logout
   - Refresh tokens available for extended sessions
   - HTTP-only cookies recommended for token storage

## Development Tools

### Laravel Telescope
- **URL**: http://localhost:8000/telescope
- Monitor all API requests and responses
- Debug JWT token generation and validation
- Track database queries and performance

### Testing Credentials

| User | Password | Tenant Access | Role | Login Methods |
|------|----------|---------------|------|---------------|
| user@tenant1.com | password | tenant1 | User | SSO + Direct |
| admin@tenant1.com | password | tenant1 | Admin | SSO + Direct |
| user@tenant2.com | password | tenant2 | User | SSO + Direct |
| admin@tenant2.com | password | tenant2 | Admin | SSO + Direct |
| superadmin@sso.com | password | tenant1, tenant2 | Super Admin | SSO + Direct |

#### Login Method Examples

**Direct Login to Tenant Apps:**
- Visit `http://localhost:8001/login` or `http://localhost:8002/login`
- Use any valid credentials above
- Authentication happens through central SSO API
- Local Laravel session created automatically

**SSO Redirect Login:**
- Click "Login with Central SSO" button in tenant apps
- Redirected to central SSO for authentication
- Same result as direct login but different user flow

### Legacy Users (unknown passwords)
| User | Description |
|------|-------------|
| admin@example.com | Legacy admin user |
| multi@example.com | Legacy multi-tenant user |

## Common Issues and Solutions

### Issue: "Token not valid for this tenant"
**Cause**: Token's `tenants` array doesn't include the requested tenant
**Solution**: Ensure user has access to tenant in database, regenerate token

### Issue: "Access denied to tenant"
**Cause**: User not associated with tenant in tenant_users table
**Solution**: Add user-tenant relationship or use correct credentials

### Issue: "Could not create token"
**Cause**: JWT service configuration issue
**Solution**: Check JWT secret key in .env, ensure JWT package is installed

### Issue: Token expires too quickly
**Cause**: Default TTL is 60 minutes
**Solution**: Adjust JWT_TTL in .env file or implement refresh token flow

### Issue: "Invalid credentials" with correct password
**Cause**: Using wrong database (SQLite vs MariaDB)
**Solution**: Ensure using MariaDB in Docker, not local SQLite

### Issue: User not found errors
**Cause**: Database not seeded with test users
**Solution**: Run `docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder`