# 📋 Tenant 1 Mobile API Reference

## Base URL

```
Production: https://tenant1.example.com/api/v1/mobile
Development: http://localhost:8001/api/v1/mobile
```

## Authentication

All protected endpoints require a valid Bearer token obtained through the authentication flow.

```http
Authorization: Bearer {access_token}
```

## Required Headers

All requests must include these security headers:

```http
Content-Type: application/json
X-Timestamp: {unix_timestamp}
X-Device-Id: {unique_device_id}
X-Signature: {hmac_sha256_signature}
X-Device-Info: {json_device_security_info}
```

### HMAC Signature Generation

```javascript
// Canonical request format
const canonicalRequest = `${method}|${path}|${timestamp}|${deviceId}|${body}`;

// Generate HMAC-SHA256 signature
const signature = crypto
  .createHmac('sha256', MOBILE_HMAC_SECRET)
  .update(canonicalRequest)
  .digest('hex');
```

---

## 🔐 Authentication Endpoints

### Generate Authorization Code

Generate an authorization code for OAuth 2.0 PKCE flow.

```http
POST /auth/authorize
```

**Request Body:**
```json
{
  "client_id": "mobile_app",
  "code_challenge": "E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM",
  "code_challenge_method": "S256",
  "scope": "read write"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "authorization_code": "abc123def456...",
    "expires_in": 600
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Invalid code challenge method"
}
```

---

### Exchange Code for Tokens

Exchange authorization code for access and refresh tokens.

```http
POST /auth/token
```

**Request Body:**
```json
{
  "code": "abc123def456...",
  "code_verifier": "dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk",
  "device_id": "device_12345",
  "email": "user@tenant1.com",
  "password": "password",
  "device_type": "ios",
  "device_name": "iPhone 15 Pro",
  "device_model": "iPhone16,1",
  "os_version": "17.0",
  "app_version": "1.0.0",
  "push_token": "fcm_token_here",
  "screen_resolution": "1179x2556",
  "timezone": "America/New_York",
  "language": "en-US"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token_type": "Bearer",
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 1800,
    "scope": "read write",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@tenant1.com"
    }
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Invalid or expired authorization code"
}
```

---

### Direct Login

Alternative login method bypassing OAuth flow.

```http
POST /auth/login
```

**Request Body:**
```json
{
  "email": "user@tenant1.com",
  "password": "password",
  "device_id": "device_12345",
  "device_info": {
    "device_type": "android",
    "device_name": "Samsung Galaxy S24",
    "device_model": "SM-S921U",
    "os_version": "14",
    "app_version": "1.0.0",
    "push_token": "fcm_token_here",
    "screen_resolution": "1440x3088",
    "timezone": "America/New_York",
    "language": "en-US"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token_type": "Bearer",
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 1800,
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@tenant1.com"
    }
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### Refresh Access Token

Refresh an expired access token using a refresh token.

```http
POST /auth/refresh
```

**Request Body:**
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "device_id": "device_12345",
  "device_fingerprint": "abc123fingerprint"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token_type": "Bearer",
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 1800
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Invalid refresh token: Device mismatch"
}
```

---

### Logout

Revoke current access token and optionally all device tokens.

```http
POST /auth/logout
```
*Requires Authentication*

**Request Body:**
```json
{
  "revoke_all_device_tokens": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

---

## 👤 Profile Endpoints

### Get User Profile

Retrieve current user's profile information.

```http
GET /profile
```
*Requires Authentication*

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@tenant1.com",
    "sso_user_id": 123,
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

---

### Update User Profile

Update user's profile information.

```http
PUT /profile
```
*Requires Authentication*

**Request Body:**
```json
{
  "name": "John Smith",
  "phone": "+1-555-123-4567",
  "timezone": "America/New_York"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Smith",
    "email": "user@tenant1.com",
    "phone": "+1-555-123-4567",
    "timezone": "America/New_York",
    "updated_at": "2024-01-15T11:00:00Z"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "errors": {
    "name": ["The name field is required."],
    "phone": ["The phone format is invalid."]
  }
}
```

---

### Upload Profile Avatar

Upload a profile avatar image.

```http
POST /profile/avatar
```
*Requires Authentication*

**Request:** Multipart form data
```
avatar: [image_file] (max 2MB, jpg/png)
```

**Response:**
```json
{
  "success": true,
  "data": {
    "avatar_url": "https://tenant1.example.com/storage/avatars/user-1-avatar.jpg",
    "message": "Avatar uploaded successfully"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "errors": {
    "avatar": ["The avatar must be an image.", "The avatar may not be greater than 2048 kilobytes."]
  }
}
```

---

### Delete Profile Avatar

Remove current profile avatar.

```http
DELETE /profile/avatar
```
*Requires Authentication*

**Response:**
```json
{
  "success": true,
  "message": "Avatar deleted successfully"
}
```

---

## 📱 Device Management

### List Registered Devices

Get all devices registered to the current user.

```http
GET /devices
```
*Requires Authentication*

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "device_id": "device_12345",
      "device_name": "iPhone 15 Pro",
      "device_type": "ios",
      "device_model": "iPhone16,1",
      "os_version": "17.0",
      "app_version": "1.0.0",
      "last_seen_at": "2024-01-15T10:30:00Z",
      "is_active": true
    },
    {
      "device_id": "device_67890",
      "device_name": "iPad Pro",
      "device_type": "ios",
      "device_model": "iPad14,6",
      "os_version": "17.0",
      "app_version": "1.0.0",
      "last_seen_at": "2024-01-10T15:45:00Z",
      "is_active": false
    }
  ]
}
```

---

### Revoke Device Access

Remove a device and revoke all its tokens.

```http
DELETE /devices/{device_id}
```
*Requires Authentication*

**Path Parameters:**
- `device_id` (string): The device ID to revoke

**Response:**
```json
{
  "success": true,
  "message": "Device access revoked successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Device not found"
}
```

---

### Update Push Token

Update push notification token for current device.

```http
POST /devices/current/push-token
```
*Requires Authentication*

**Request Body:**
```json
{
  "push_token": "new_fcm_or_apns_token_here"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Push token updated successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Device ID required"
}
```

---

## 📊 Resource Endpoints

### Get Tenant Resources

Retrieve tenant-specific resources for the authenticated user.

```http
GET /resources
```
*Requires Authentication*

**Query Parameters:**
- `page` (integer, optional): Page number for pagination (default: 1)
- `per_page` (integer, optional): Items per page (default: 15, max: 100)
- `search` (string, optional): Search term for filtering
- `sort` (string, optional): Sort field (default: created_at)
- `order` (string, optional): Sort order - asc/desc (default: desc)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Resource Title",
        "description": "Resource description",
        "type": "document",
        "url": "https://tenant1.example.com/resources/1",
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-15T10:30:00Z"
      }
    ],
    "first_page_url": "https://tenant1.example.com/api/v1/mobile/resources?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "https://tenant1.example.com/api/v1/mobile/resources?page=5",
    "next_page_url": "https://tenant1.example.com/api/v1/mobile/resources?page=2",
    "path": "https://tenant1.example.com/api/v1/mobile/resources",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 75
  }
}
```

---

### Get Single Resource

Retrieve a specific resource by ID.

```http
GET /resources/{id}
```
*Requires Authentication*

**Path Parameters:**
- `id` (integer): The resource ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Resource Title",
    "description": "Detailed resource description",
    "type": "document",
    "content": "Full resource content here...",
    "url": "https://tenant1.example.com/resources/1",
    "metadata": {
      "file_size": 1024,
      "mime_type": "application/pdf",
      "author": "John Doe"
    },
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Resource not found"
}
```

---

### Create New Resource

Create a new resource.

```http
POST /resources
```
*Requires Authentication*

**Request Body:**
```json
{
  "title": "New Resource",
  "description": "Resource description",
  "type": "document",
  "content": "Resource content",
  "metadata": {
    "tags": ["important", "project-a"],
    "category": "documentation"
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "New Resource",
    "description": "Resource description",
    "type": "document",
    "content": "Resource content",
    "url": "https://tenant1.example.com/resources/2",
    "metadata": {
      "tags": ["important", "project-a"],
      "category": "documentation"
    },
    "created_at": "2024-01-15T11:00:00Z",
    "updated_at": "2024-01-15T11:00:00Z"
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "errors": {
    "title": ["The title field is required."],
    "type": ["The selected type is invalid."]
  }
}
```

---

### Update Resource

Update an existing resource.

```http
PUT /resources/{id}
```
*Requires Authentication*

**Path Parameters:**
- `id` (integer): The resource ID

**Request Body:**
```json
{
  "title": "Updated Resource Title",
  "description": "Updated description",
  "content": "Updated content"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Updated Resource Title",
    "description": "Updated description",
    "content": "Updated content",
    "updated_at": "2024-01-15T11:30:00Z"
  }
}
```

---

### Delete Resource

Delete a resource.

```http
DELETE /resources/{id}
```
*Requires Authentication*

**Path Parameters:**
- `id` (integer): The resource ID

**Response:**
```json
{
  "success": true,
  "message": "Resource deleted successfully"
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Resource not found"
}
```

---

## 🚫 Error Responses

### Standard Error Format

All API errors follow this format:

```json
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE",
  "details": {
    "field": "Additional error details"
  }
}
```

### HTTP Status Codes

| Code | Description | When Used |
|------|-------------|-----------|
| 200 | OK | Successful requests |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Missing or invalid authentication |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Common Error Codes

| Error Code | Description | Resolution |
|------------|-------------|------------|
| `INVALID_SIGNATURE` | HMAC signature verification failed | Check signature generation |
| `EXPIRED_TIMESTAMP` | Request timestamp too old | Use current timestamp |
| `DEVICE_MISMATCH` | Device ID doesn't match token | Re-authenticate device |
| `TOKEN_EXPIRED` | Access token has expired | Use refresh token |
| `INVALID_REFRESH_TOKEN` | Refresh token is invalid | Re-authenticate user |
| `DEVICE_COMPROMISED` | Device security check failed | Update app/device |
| `RATE_LIMIT_EXCEEDED` | Too many requests | Implement backoff |

---

## 🔐 Security Headers

### X-Device-Info Format

```json
{
  "jailbroken": false,
  "rooted": false,
  "debugger": false,
  "emulator": false,
  "app_integrity": true
}
```

### Rate Limits

| Endpoint Type | Limit | Window |
|---------------|-------|--------|
| Authentication | 10 requests | 1 minute |
| General API | 60 requests | 1 minute |
| File Upload | 5 requests | 1 minute |
| Device Management | 20 requests | 1 minute |

---

## 📱 Platform-Specific Notes

### iOS Considerations
- Use iOS Keychain for token storage
- Implement certificate pinning in production
- Handle background app refresh for token renewal
- Use biometric authentication when available

### Android Considerations  
- Use EncryptedSharedPreferences for token storage
- Implement certificate pinning with OkHttp
- Handle foreground service for background operations
- Use BiometricPrompt for authentication

### React Native
- Use react-native-keychain for secure storage
- Implement proper SSL pinning
- Handle deep linking for OAuth redirects
- Use proper navigation state management

### Flutter
- Use flutter_secure_storage for token storage
- Implement certificate pinning with dio
- Handle platform-specific security features
- Use proper state management (Provider, Bloc, etc.)

---

## 🧪 Testing

### Test Credentials

Use these credentials for testing in development:

```
Email: user@tenant1.com
Password: password

Email: admin@tenant1.com  
Password: password
```

### Example cURL Commands

```bash
# Generate authorization code
curl -X POST http://localhost:8001/api/v1/mobile/auth/authorize \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: $(date +%s)" \
  -H "X-Device-Id: test-device-001" \
  -H "X-Signature: [generated_signature]" \
  -d '{
    "client_id": "mobile_app",
    "code_challenge": "E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM",
    "code_challenge_method": "S256"
  }'

# Direct login
curl -X POST http://localhost:8001/api/v1/mobile/auth/login \
  -H "Content-Type: application/json" \
  -H "X-Timestamp: $(date +%s)" \
  -H "X-Device-Id: test-device-001" \
  -H "X-Signature: [generated_signature]" \
  -H "X-Device-Info: {\"jailbroken\":false,\"rooted\":false,\"debugger\":false,\"emulator\":false}" \
  -d '{
    "email": "user@tenant1.com",
    "password": "password",
    "device_id": "test-device-001",
    "device_info": {
      "device_type": "ios",
      "device_name": "iPhone Test",
      "device_model": "iPhone16,1",
      "os_version": "17.0",
      "app_version": "1.0.0"
    }
  }'

# Get profile (with token)
curl -X GET http://localhost:8001/api/v1/mobile/profile \
  -H "Authorization: Bearer [access_token]" \
  -H "X-Timestamp: $(date +%s)" \
  -H "X-Device-Id: test-device-001" \
  -H "X-Signature: [generated_signature]"
```

---

## 📚 Additional Resources

- [Implementation Guide](implementation-guide.md) - Backend setup
- [Security Configuration](security-configuration.md) - Security setup details
- [iOS SDK Guide](client-sdks/ios-implementation.md) - iOS client implementation
- [Android SDK Guide](client-sdks/android-implementation.md) - Android client implementation
- [Testing Guide](testing-guide.md) - Comprehensive testing strategies

---

*For support or questions about this API, refer to the implementation guide or check the troubleshooting section.*