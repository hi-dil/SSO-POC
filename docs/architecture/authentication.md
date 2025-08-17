# Authentication Systems

Complete guide to authentication flows, session management, and security in the multi-tenant SSO system.

## 🏗️ Architecture Overview

The SSO system implements a **dual-session architecture** combining centralized authentication with local session management.

```
┌─────────────────────────────────────────────────────────────────┐
│                   Authentication Architecture                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐         │
│  │ Tenant App  │    │ Tenant App  │    │ Central SSO │         │
│  │ (Laravel)   │    │ (Laravel)   │    │ (Laravel)   │         │
│  │ Port 8001   │    │ Port 8002   │    │ Port 8000   │         │
│  └─────┬───────┘    └─────┬───────┘    └─────┬───────┘         │
│        │                  │                  │                 │
│        │         JWT Authentication          │                 │
│        └──────────────────┼──────────────────┘                 │
│                           │                                    │
│                  ┌────────┴────────┐                           │
│                  │   MariaDB       │                           │
│                  │ ┌─────────────┐ │                           │
│                  │ │ sso_main    │ │                           │
│                  │ │ tenant1_db  │ │                           │
│                  │ │ tenant2_db  │ │                           │
│                  │ └─────────────┘ │                           │
│                  └─────────────────┘                           │
└─────────────────────────────────────────────────────────────────┘
```

## 🔐 Authentication Methods

The system supports multiple authentication approaches to provide flexibility and optimal user experience:

### 1. **Seamless SSO Flow** (Primary Method)
- User clicks "Login with SSO" in tenant app
- JavaScript-based authentication checking
- Auto-login for authenticated users
- No unnecessary login forms

### 2. **Direct Login** (Dual-Session)
- User fills login form directly in tenant app
- Credentials validated through central SSO API
- Local Laravel session created
- Best performance for repeat visits

### 3. **Traditional SSO Redirect**
- Full redirect to central SSO for authentication
- Return to tenant app with JWT token
- Classic SSO experience

### 4. **API Authentication**
- Direct API calls with JWT tokens
- Programmatic access for integrations
- Stateless authentication

## 🔄 Authentication Flows

### Seamless SSO Flow (Recommended)

```mermaid
sequenceDiagram
    participant U as User
    participant T as Tenant App
    participant SSO as Central SSO
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

**Benefits:**
- **Seamless UX**: No login forms for authenticated users
- **Fast Response**: JavaScript-based checking
- **Loading States**: Clear progress indication
- **Graceful Fallback**: Login form when needed

### Dual-Session Direct Login

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

**Benefits:**
- **🎯 Centralized Authentication**: All credentials validated by central SSO
- **⚡ Local Session Management**: Independent sessions per tenant
- **🚀 Performance**: Reduced API calls after authentication
- **🔄 User Data Sync**: Auto-sync on login
- **📊 Audit Trail**: All authentications tracked centrally

### Traditional SSO Redirect

```mermaid
sequenceDiagram
    participant U as User
    participant T as Tenant App
    participant SSO as Central SSO
    participant DB as Database
    
    Note over U,DB: Standard SSO Flow
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
    SSO->>SSO: 13. Generate JWT with claims
    SSO->>T: 14. Redirect with token
    T->>SSO: 15. Validate token via API
    SSO->>T: 16. Return validation result
    T->>T: 17. Create session
    T->>U: 18. User logged in
```

## 🔑 JWT Token Management

### Token Structure

```json
{
  "header": {
    "typ": "JWT",
    "alg": "HS256"
  },
  "payload": {
    "sub": "25",
    "iss": "central-sso",
    "aud": "tenant-applications",
    "iat": 1693123456,
    "exp": 1693127056,
    "user_id": 25,
    "email": "superadmin@sso.com",
    "name": "Super Admin",
    "tenants": ["tenant1", "tenant2"],
    "current_tenant": "tenant1",
    "is_admin": true,
    "permissions": ["users.view", "tenants.manage"]
  }
}
```

### Token Validation Process

```php
// In tenant application
public function validateJWT($token, $tenantSlug)
{
    try {
        // Decode JWT token
        $payload = JWT::decode($token, $this->jwtSecret, ['HS256']);
        
        // Verify token is not expired
        if ($payload->exp < time()) {
            throw new TokenExpiredException();
        }
        
        // Verify user has access to this tenant
        if (!in_array($tenantSlug, $payload->tenants)) {
            throw new UnauthorizedTenantException();
        }
        
        return [
            'valid' => true,
            'user_id' => $payload->user_id,
            'user_data' => $payload
        ];
        
    } catch (Exception $e) {
        return ['valid' => false, 'error' => $e->getMessage()];
    }
}
```

### Token Refresh Strategy

```php
// Automatic token refresh
public function refreshTokenIfNeeded($token)
{
    $payload = JWT::decode($token, $this->jwtSecret, ['HS256']);
    
    // Refresh if token expires within 10 minutes
    if ($payload->exp - time() < 600) {
        $response = $this->ssoAPI->post('/api/auth/refresh', [
            'token' => $token
        ]);
        
        if ($response['success']) {
            session(['jwt_token' => $response['token']]);
            return $response['token'];
        }
    }
    
    return $token;
}
```

## 🗂️ Session Management

### Session Data Structure

The dual-session architecture maintains both Laravel session data and SSO integration data:

```php
// Laravel Session Storage
session([
    // Standard Laravel Auth
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

### Session Lifecycle

```php
// During login - create session
public function createSession($user, $jwtToken, $ssoUserData)
{
    // Standard Laravel authentication
    auth()->login($user);
    
    // Store SSO data
    session([
        'jwt_token' => $jwtToken,
        'sso_user_data' => $ssoUserData
    ]);
    
    // Update local user data
    $user->update([
        'sso_user_id' => $ssoUserData['id'],
        'name' => $ssoUserData['name'],
        'email' => $ssoUserData['email'],
        'last_login_at' => now()
    ]);
}

// During request - check session
public function checkSession()
{
    if (!auth()->check()) {
        return false;
    }
    
    $jwtToken = session('jwt_token');
    if (!$jwtToken) {
        auth()->logout();
        return false;
    }
    
    // Optionally validate token with SSO
    $validation = $this->validateTokenWithSSO($jwtToken);
    if (!$validation['valid']) {
        auth()->logout();
        session()->flush();
        return false;
    }
    
    return true;
}
```

### Cross-Tenant Session Sharing

```php
// Middleware to handle cross-tenant access
public function handle($request, Closure $next)
{
    $currentTenant = config('app.tenant_slug');
    $userTenants = session('sso_user_data.tenants', []);
    
    // Check if user has access to current tenant
    if (!in_array($currentTenant, $userTenants)) {
        abort(403, 'Access denied for this tenant');
    }
    
    // Update current tenant in session
    session(['sso_user_data.current_tenant' => $currentTenant]);
    
    return $next($request);
}
```

## 🔒 Security Implementation

### Password Security

```php
// Password hashing (in Central SSO)
public function hashPassword($password)
{
    return Hash::make($password, [
        'rounds' => 12, // Bcrypt rounds
        'memory' => 1024,
        'time' => 2,
        'threads' => 2,
    ]);
}

// Password verification
public function verifyPassword($password, $hashedPassword)
{
    return Hash::check($password, $hashedPassword);
}
```

### CSRF Protection

```php
// In blade templates
<form method="POST" action="/login">
    @csrf
    <!-- form fields -->
</form>

// In middleware stack
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\VerifyCsrfToken::class,
    ],
];
```

### Rate Limiting

```php
// Authentication rate limiting
public function login(Request $request)
{
    $key = 'login_attempts:' . $request->ip();
    $attempts = Cache::get($key, 0);
    
    if ($attempts >= 5) {
        throw new TooManyRequestsException('Too many login attempts');
    }
    
    // Attempt authentication
    $result = $this->attemptAuth($request);
    
    if (!$result['success']) {
        Cache::put($key, $attempts + 1, 300); // 5 minutes
        throw new AuthenticationException('Invalid credentials');
    }
    
    Cache::forget($key);
    return $result;
}
```

### Input Validation

```php
// Request validation
public function login(LoginRequest $request)
{
    // LoginRequest validation rules
    return [
        'email' => 'required|email|max:255',
        'password' => 'required|string|min:8|max:255',
        'tenant_slug' => 'required|string|exists:tenants,slug'
    ];
}

// XSS Protection
public function sanitizeInput($input)
{
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}
```

## 🔧 API Authentication

### API Login Endpoint

```php
// POST /api/auth/login
public function apiLogin(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
        'tenant_slug' => 'required|string'
    ]);
    
    // Find user
    $user = User::where('email', $request->email)->first();
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }
    
    // Check tenant access
    if (!$user->hasAccessToTenant($request->tenant_slug)) {
        return response()->json(['error' => 'Access denied'], 403);
    }
    
    // Generate JWT
    $token = $this->generateJWTToken($user, $request->tenant_slug);
    
    return response()->json([
        'success' => true,
        'token' => $token,
        'user' => $user->toArray(),
        'expires_in' => config('jwt.ttl') * 60
    ]);
}
```

### Token-Based API Access

```php
// API middleware for token validation
public function handle($request, Closure $next)
{
    $token = $request->bearerToken();
    
    if (!$token) {
        return response()->json(['error' => 'Token required'], 401);
    }
    
    try {
        $payload = JWT::decode($token, config('jwt.secret'), ['HS256']);
        $request->attributes->set('jwt_payload', $payload);
        $request->attributes->set('user_id', $payload->user_id);
        
    } catch (Exception $e) {
        return response()->json(['error' => 'Invalid token'], 401);
    }
    
    return $next($request);
}

// Using in API routes
Route::middleware('jwt.auth')->group(function () {
    Route::get('/profile', 'ProfileController@show');
    Route::post('/logout', 'AuthController@logout');
});
```

## 🔄 Logout Implementation

### Single Application Logout

```php
public function logout(Request $request)
{
    // Invalidate JWT token (add to blacklist)
    $token = session('jwt_token');
    if ($token) {
        $this->blacklistToken($token);
    }
    
    // Laravel logout
    auth()->logout();
    
    // Clear session
    session()->flush();
    
    // Regenerate session ID
    session()->regenerate();
    
    return redirect('/login');
}
```

### Global SSO Logout

```php
public function globalLogout(Request $request)
{
    $jwtToken = session('jwt_token');
    
    // Call Central SSO logout endpoint
    $response = Http::post(config('sso.central_url') . '/api/auth/logout', [
        'token' => $jwtToken
    ]);
    
    // Local logout
    auth()->logout();
    session()->flush();
    session()->regenerate();
    
    // Redirect to Central SSO for complete logout
    return redirect(config('sso.central_url') . '/logout?redirect=' . urlencode(request()->url()));
}
```

## 🧪 Testing Authentication

### Unit Tests

```php
public function test_user_can_login_with_valid_credentials()
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password')
    ]);
    
    $response = $this->post('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
        'tenant_slug' => 'tenant1'
    ]);
    
    $response->assertOk()
            ->assertJsonStructure(['token', 'user', 'expires_in']);
}

public function test_jwt_token_validation()
{
    $user = User::factory()->create();
    $token = $this->generateJWTToken($user, 'tenant1');
    
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token
    ])->get('/api/profile');
    
    $response->assertOk();
}
```

### Integration Tests

```php
public function test_cross_tenant_authentication_flow()
{
    // Login to tenant1
    $this->post('/login', [
        'email' => 'superadmin@sso.com',
        'password' => 'password'
    ]);
    
    // Access tenant2 with SSO
    $response = $this->get('http://localhost:8002/auth/sso');
    $response->assertRedirect(); // Should auto-login
    
    // Verify access to tenant2
    $this->followRedirects($response)
         ->assertSee('Tenant 2 Dashboard');
}
```

## 📊 Authentication Metrics

### Login Audit Tracking

```php
// Track all authentication attempts
public function recordLoginAttempt($email, $tenantSlug, $success, $metadata = [])
{
    LoginAudit::create([
        'email' => $email,
        'tenant_slug' => $tenantSlug,
        'success' => $success,
        'ip_address' => request()->ip(),
        'user_agent' => request()->userAgent(),
        'login_method' => $metadata['method'] ?? 'standard',
        'metadata' => json_encode($metadata),
        'attempted_at' => now()
    ]);
}

// Usage in authentication
public function authenticate($credentials)
{
    $success = $this->attemptAuth($credentials);
    
    $this->recordLoginAttempt(
        $credentials['email'],
        $credentials['tenant_slug'],
        $success,
        ['method' => 'direct_login']
    );
    
    return $success;
}
```

### Performance Monitoring

```php
// Monitor authentication performance
public function monitorAuthPerformance()
{
    $startTime = microtime(true);
    
    // Perform authentication
    $result = $this->authenticate($credentials);
    
    $duration = microtime(true) - $startTime;
    
    // Log performance metrics
    Log::info('Authentication performance', [
        'duration' => $duration,
        'success' => $result['success'],
        'method' => 'dual_session'
    ]);
    
    return $result;
}
```

---

## 🔗 Related Documentation

- **[Multi-Tenancy Design](multi-tenancy.md)** - Tenant architecture and isolation
- **[Database Design](database-design.md)** - Schema and relationships
- **[Security Guide](../guides/security.md)** - Security best practices
- **[API Reference](../reference/api.md)** - Complete API documentation