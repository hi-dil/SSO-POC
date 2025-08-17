# External SSO Integration (OAuth Providers)

**Status**: 📋 TODO - Future Implementation  
**Priority**: Medium  
**Estimated Effort**: 2-3 weeks  
**Dependencies**: Current dual-session architecture

## 🎯 Overview

Integration of external OAuth providers (Google, GitHub, Facebook, Microsoft, etc.) into the multi-tenant SSO system using a **centralized architecture** where all OAuth interactions are handled exclusively by the central SSO server.

## 🏗️ Architecture Design

### Centralized OAuth Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                   Centralized OAuth Architecture               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐         │
│  │ Tenant App  │    │Central SSO  │    │ OAuth       │         │
│  │             │    │ Server      │    │ Provider    │         │
│  │ ┌─────────┐ │    │ ┌─────────┐ │    │ (Google)    │         │
│  │ │"Login   │ │    │ │OAuth    │ │    │             │         │
│  │ │with     │────────→│Service  │────────→│         │         │
│  │ │Google"  │ │    │ │         │ │    │             │         │
│  │ └─────────┘ │    │ └─────────┘ │    │             │         │
│  └─────────────┘    └─────────────┘    └─────────────┘         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Authentication Flow Sequence

1. **Initiation**: User clicks "Login with Google" on Tenant App
2. **API Call**: Tenant App → Central SSO API (`/api/auth/external/initiate`)
3. **Provider Setup**: Central SSO validates tenant permissions and creates OAuth URL
4. **Redirect**: User redirected to Google OAuth
5. **Authentication**: User authenticates with Google
6. **Callback**: Google → Central SSO callback endpoint
7. **Processing**: Central SSO processes OAuth response, creates/links accounts
8. **Token Generation**: Central SSO generates JWT with tenant context
9. **Return**: User redirected back to Tenant App with token
10. **Session Creation**: Tenant App creates local session (same as password login)

## 🔧 Implementation Components

### Database Schema Changes

#### OAuth Accounts Table (Central SSO)
```sql
CREATE TABLE oauth_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(50) NOT NULL,           -- google, github, facebook, microsoft
    provider_id VARCHAR(255) NOT NULL,       -- OAuth provider user ID
    provider_email VARCHAR(255),             -- Email from OAuth provider
    provider_name VARCHAR(255),              -- Name from OAuth provider
    provider_avatar VARCHAR(500),            -- Avatar URL from OAuth provider
    access_token TEXT,                       -- Encrypted OAuth access token
    refresh_token TEXT,                      -- Encrypted OAuth refresh token
    token_expires_at TIMESTAMP NULL,         -- Token expiration
    raw_data JSON,                          -- Full OAuth response data
    is_verified BOOLEAN DEFAULT TRUE,        -- OAuth accounts are pre-verified
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_account (provider, provider_id),
    INDEX idx_user_provider (user_id, provider),
    INDEX idx_provider_email (provider, provider_email)
);
```

#### Tenant OAuth Configuration
```sql
-- Add to tenants table
ALTER TABLE tenants ADD COLUMN oauth_settings JSON NULL;

-- Example oauth_settings structure:
{
  "enabled_providers": ["google", "github"],
  "auto_assign_domains": {
    "company.com": "auto_approve",
    "contractor.com": "admin_approval"
  },
  "default_role": "user",
  "require_admin_approval": false
}
```

#### User Table Updates
```sql
-- Add to users table
ALTER TABLE users ADD COLUMN account_type ENUM('local', 'oauth', 'hybrid') DEFAULT 'local';
ALTER TABLE users MODIFY password VARCHAR(255) NULL; -- Allow NULL for OAuth-only accounts
```

### Central SSO Server Components

#### 1. OAuth Service Class
```php
// app/Services/OAuthService.php
class OAuthService
{
    public function getProviderRedirectUrl(string $provider, string $tenantSlug, string $callbackUrl): string
    public function handleProviderCallback(string $provider, Request $request): array
    public function linkOAuthAccount(User $user, string $provider, array $oauthData): OAuthAccount
    public function createUserFromOAuth(array $oauthData, string $provider, string $tenantSlug): User
    public function getEnabledProvidersForTenant(string $tenantSlug): array
}
```

#### 2. External Auth Controller
```php
// app/Http/Controllers/Api/ExternalAuthController.php
class ExternalAuthController extends Controller
{
    public function initiateOAuth(Request $request): JsonResponse
    public function handleOAuthCallback(string $provider, Request $request): RedirectResponse
    public function getAvailableProviders(string $tenantSlug): JsonResponse
    public function linkAccount(Request $request): JsonResponse
    public function unlinkAccount(Request $request): JsonResponse
}
```

#### 3. API Routes
```php
// External OAuth initiation
POST /api/auth/external/initiate
// Request: { provider, tenant_slug, callback_url }
// Response: { redirect_url, state }

// OAuth callback (for providers)
GET /auth/{provider}/callback

// Get available providers for tenant
GET /api/auth/external/providers/{tenant_slug}

// Account linking (authenticated users)
POST /api/auth/external/link
DELETE /api/auth/external/unlink
```

### Tenant Application Components

#### 1. Updated Auth Controller
```php
// app/Http/Controllers/AuthController.php
class AuthController extends Controller
{
    public function initiateExternalAuth(Request $request): RedirectResponse
    public function handleExternalCallback(Request $request): RedirectResponse
    public function getAvailableProviders(): JsonResponse
}
```

#### 2. Updated Login View
```html
<!-- Add to login form -->
<div class="external-auth-section">
    <div class="divider">
        <span>Or continue with</span>
    </div>
    
    <div class="external-providers">
        <button class="btn-oauth btn-google" data-provider="google">
            <i class="fab fa-google"></i> Continue with Google
        </button>
        <button class="btn-oauth btn-github" data-provider="github">
            <i class="fab fa-github"></i> Continue with GitHub
        </button>
        <!-- More providers... -->
    </div>
</div>
```

## 🔐 Security Considerations

### OAuth State Management
- Generate cryptographically secure state parameters
- Store state with tenant context and expiration
- Validate state on callback to prevent CSRF attacks

### Token Security
- Encrypt OAuth access/refresh tokens at rest
- Implement token refresh logic for long-lived access
- Never expose OAuth tokens to tenant applications

### Provider Validation
- Validate OAuth responses thoroughly
- Check email verification status from providers
- Handle provider-specific edge cases

### Account Linking Security
- Require email verification for account linking
- Implement confirmation flow for linking existing accounts
- Prevent unauthorized account takeovers

## 🎛️ Configuration Management

### Environment Variables
```bash
# OAuth Provider Configuration (Central SSO only)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://sso.your-domain.com/auth/google/callback

GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_client_secret

MICROSOFT_CLIENT_ID=your_microsoft_client_id
MICROSOFT_CLIENT_SECRET=your_microsoft_client_secret

# OAuth Security
OAUTH_STATE_ENCRYPTION_KEY=32_character_encryption_key
OAUTH_TOKEN_ENCRYPTION_KEY=32_character_encryption_key
```

### Per-Tenant Configuration
```json
{
  "enabled_providers": ["google", "github", "microsoft"],
  "provider_settings": {
    "google": {
      "enabled": true,
      "auto_create_users": true,
      "require_email_verification": false
    },
    "github": {
      "enabled": true,
      "auto_create_users": false,
      "require_admin_approval": true
    }
  },
  "auto_assignment_rules": {
    "email_domains": {
      "company.com": {
        "action": "auto_approve",
        "default_role": "user"
      },
      "admin.company.com": {
        "action": "auto_approve", 
        "default_role": "admin"
      }
    }
  },
  "account_linking": {
    "allow_multiple_providers": true,
    "require_email_match": true,
    "send_notification": true
  }
}
```

## 📊 Audit and Analytics

### OAuth-Specific Audit Events
- External authentication attempts (success/failure)
- Account linking/unlinking activities
- Provider-specific login analytics
- OAuth token refresh events
- Admin approval workflow events

### Analytics Metrics
- Popular OAuth providers by tenant
- OAuth vs password login ratios
- Account linking success rates
- Failed OAuth attempts by provider
- Time-to-complete OAuth flows

## 🧪 Testing Strategy

### Unit Tests
- OAuth service provider interactions
- State generation and validation
- Account linking logic
- Token encryption/decryption
- Tenant permission validation

### Integration Tests
- End-to-end OAuth flows
- Cross-tenant access validation
- Account linking scenarios
- Error handling and recovery
- Provider callback validation

### Manual Testing Scenarios
- First-time OAuth user registration
- Existing user account linking
- Multiple provider linking
- Tenant permission enforcement
- Error state handling

## 🚀 Deployment Considerations

### OAuth App Registration
- Register OAuth applications with each provider
- Configure callback URLs for production/staging
- Set up appropriate scopes and permissions
- Handle provider-specific requirements

### Production Configuration
- Secure storage of OAuth credentials
- SSL/TLS requirements for OAuth callbacks
- Rate limiting for OAuth endpoints
- Monitoring OAuth success rates

### Rollout Strategy
1. **Phase 1**: Implement Google OAuth for single tenant (testing)
2. **Phase 2**: Add GitHub/Microsoft support
3. **Phase 3**: Enable for all tenants with configuration
4. **Phase 4**: Advanced features (account linking, admin approval)

## 🔗 Related Documentation

### Architecture References
- [Authentication Systems](../architecture/authentication.md) - Current auth flows
- [Multi-Tenancy Design](../architecture/multi-tenancy.md) - Tenant isolation
- [Database Design](../architecture/database-design.md) - Schema structure

### Implementation Guides
- [Security Guide](../guides/security.md) - Security best practices
- [User Management](../guides/user-management.md) - User lifecycle
- [Tenant Management](../guides/tenant-management.md) - Tenant configuration

### Technical References
- [API Documentation](../reference/api.md) - Current API structure
- [Configuration Reference](../reference/configuration.md) - Environment setup
- [Troubleshooting Guide](../reference/troubleshooting.md) - Common issues

---

## 💡 Implementation Notes

### Development Priorities
1. **Google OAuth** - Most common enterprise use case
2. **GitHub OAuth** - Developer-focused tenants
3. **Microsoft OAuth** - Enterprise/Office 365 integration
4. **Facebook OAuth** - Consumer applications

### Technical Considerations
- Laravel Socialite package compatibility
- JWT token size with additional OAuth claims
- Database migration strategy for existing users
- Backward compatibility with password-based auth

### Future Enhancements
- SAML 2.0 support for enterprise SSO
- OpenID Connect implementation
- Multi-factor authentication integration
- Advanced account linking workflows

---

**Last Updated**: TBD  
**Next Review**: TBD  
**Implementation Target**: TBD