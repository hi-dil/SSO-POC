# Testing the Enhanced SSO Flow

## What Changed

The SSO system now supports **seamless authentication** - if a user is already logged into the central SSO server, clicking "Login with SSO" on a tenant app will automatically authenticate them without showing the login form.

## Test Scenarios

### Scenario 1: Fresh Login (No Existing Session)

1. **Clear all browser data** or use incognito mode
2. Visit `http://localhost:8001` (Tenant 1)
3. Click "Login with SSO"
4. **Expected**: Redirected to login form at `http://localhost:8000/auth/tenant1`
5. Enter credentials (e.g., `user@tenant1.com` / `password`)
6. **Expected**: Automatically redirected back to Tenant 1 and logged in

### Scenario 2: Seamless SSO (Existing Session)

1. **First, login to central SSO directly:**
   - Visit `http://localhost:8000/login`
   - Login with `superadmin@sso.com` / `password`
   - **Expected**: Redirected to central dashboard

2. **Now test seamless SSO:**
   - Visit `http://localhost:8001` (Tenant 1)
   - Click "Login with SSO"
   - **Expected**: NO login form shown, automatically redirected back and logged in
   - Check that you're authenticated as `superadmin@sso.com`

3. **Test with second tenant:**
   - Visit `http://localhost:8002` (Tenant 2)
   - Click "Login with SSO"
   - **Expected**: Again, NO login form shown, automatically logged in

### Scenario 3: Access Denied (User Logged In But No Tenant Access)

1. **Login to central SSO with a single-tenant user:**
   - Visit `http://localhost:8000/login`
   - Login with `user@tenant1.com` / `password` (only has access to tenant1)

2. **Try to access tenant2:**
   - Visit `http://localhost:8002` (Tenant 2)
   - Click "Login with SSO"
   - **Expected**: Shows login form with error: "You do not have access to this tenant"
   - Shows "Switch to a different account" link

### Scenario 4: Logout Options

1. **Test local logout:**
   - While logged into a tenant app, click "Logout ▼"
   - Choose "Logout from [Tenant Name]"
   - **Expected**: Logged out of tenant app only
   - Visit central SSO - still logged in there

2. **Test global logout:**
   - Login to a tenant app again
   - Click "Logout ▼"
   - Choose "Logout from all apps"
   - **Expected**: Logged out from both tenant and central SSO
   - Visit central SSO - should show login form

## Code Changes Summary

### Central SSO Controller (`SSOController.php`)
- Added session check in `showLoginForm()` method
- If user already authenticated and has tenant access → auto-redirect with token
- If user authenticated but no tenant access → show error with switch account option
- Added `logout()` method for SSO logout

### Tenant App Controllers (`AuthController.php`)
- Enhanced `logout()` method with optional SSO logout
- Added `sso_logout` parameter to logout from all apps

### UI Enhancements
- Added dropdown logout menu with two options:
  - "Logout from [Tenant Name]" (local only)
  - "Logout from all apps" (SSO logout)

## Flow Diagrams

### Before (Always Show Login Form)
```
User clicks "Login with SSO" → Always show login form → Enter credentials → Redirect back
```

### After (Smart SSO Check)
```
User clicks "Login with SSO" → Check SSO session
├─ Not logged in → Show login form → Enter credentials → Redirect back
├─ Logged in + has access → Auto-generate token → Redirect back immediately
└─ Logged in + no access → Show error + switch account option
```

## Testing Commands

```bash
# Start all services
docker compose up -d

# Check services are running
docker ps

# Seed test users (if not already done)
docker exec central-sso php artisan db:seed --class=AddTestUsersSeeder

# Clear browser data and test the flows above
```

## Expected Behavior

- **Seamless experience**: Users logged into central SSO don't see login forms again
- **Security maintained**: Users without tenant access get clear error messages
- **Flexible logout**: Users can choose to logout locally or globally
- **Cross-tenant access**: Users with multi-tenant access get seamless switching

This creates a true Single Sign-On experience where users authenticate once and access multiple applications without re-entering credentials.