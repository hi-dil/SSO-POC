<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SecureSSOService;
use App\Services\LoginAuditService;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * Secure Authentication Controller
 * 
 * Handles all authentication flows with enterprise security:
 * - Direct login via central SSO API
 * - SSO redirect processing
 * - User registration
 * - Secure logout
 * - Comprehensive audit logging
 */
class AuthController extends Controller
{
    protected SecureSSOService $ssoService;
    protected LoginAuditService $auditService;

    public function __construct(SecureSSOService $ssoService, LoginAuditService $auditService)
    {
        $this->ssoService = $ssoService;
        $this->auditService = $auditService;
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle direct login via central SSO API with security
     */
    public function login(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $credentials = $request->only('email', 'password');
        
        // Rate limiting check (basic)
        $rateLimitKey = 'login_attempts:' . request()->ip();
        $attempts = cache()->get($rateLimitKey, 0);
        $maxAttempts = config('security.rate_limiting.login_attempts', 5);
        $window = config('security.rate_limiting.login_window', 60);

        if ($attempts >= $maxAttempts) {
            $this->auditService->recordSecurityEvent('rate_limit_exceeded', [
                'ip' => request()->ip(),
                'email' => $credentials['email'],
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts
            ]);

            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again later.'
            ])->withInput();
        }

        // Increment attempt counter
        cache()->put($rateLimitKey, $attempts + 1, now()->addSeconds($window));

        // Authenticate through central SSO API with security
        $result = $this->ssoService->login(
            $credentials['email'],
            $credentials['password']
        );

        if ($result['success']) {
            // Clear rate limiting on success
            cache()->forget($rateLimitKey);

            // Authentication successful at central SSO
            $ssoUser = $result['user'];
            $token = $result['token'];
            
            // Verify tenant access
            $tenantSlug = config('app.tenant_slug');
            if (!in_array($tenantSlug, $ssoUser['tenants'] ?? [])) {
                $this->auditService->recordLogin(
                    0,
                    $request->email,
                    'direct',
                    false,
                    'Access denied to tenant'
                );
                
                return back()->withErrors([
                    'email' => 'Access denied to this application'
                ])->withInput();
            }
            
            // Create/update local user
            $localUser = $this->ssoService->createOrUpdateUser($ssoUser);
            
            // Create local Laravel session
            $this->ssoService->authenticateUser($localUser);
            
            // Store JWT token for future API calls
            session(['jwt_token' => $token]);
            session(['sso_user_data' => $ssoUser]);
            
            // Record successful login
            $this->auditService->recordLogin(
                $ssoUser['id'],
                $ssoUser['email'],
                'direct',
                true
            );
            
            return redirect()->intended('/dashboard')->with('success', 'Welcome back!');
        }

        // Authentication failed - record failure
        $this->auditService->recordLogin(
            0,
            $request->email,
            'direct',
            false,
            $result['message'] ?? 'Invalid credentials'
        );

        return back()->withErrors([
            'email' => $result['message'] ?? 'Invalid credentials'
        ])->withInput();
    }

    /**
     * Show registration form
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle user registration via central SSO API
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $result = $this->ssoService->register(
            $request->name,
            $request->email,
            $request->password,
            $request->password_confirmation
        );

        if ($result['success']) {
            // Registration successful - authenticate user
            $ssoUser = $result['user'];
            $token = $result['token'];
            
            $localUser = $this->ssoService->createOrUpdateUser($ssoUser);
            $this->ssoService->authenticateUser($localUser);
            
            session(['jwt_token' => $token]);
            session(['sso_user_data' => $ssoUser]);

            $this->auditService->recordLogin(
                $ssoUser['id'],
                $ssoUser['email'],
                'registration',
                true
            );
            
            return redirect('/dashboard')->with('success', 'Registration successful!');
        }

        $errors = $result['errors'] ?? ['email' => $result['message']];
        return back()->withErrors($errors)->withInput();
    }

    /**
     * SSO processing page with security validation
     */
    public function ssoProcess()
    {
        $this->auditService->recordSSOProcessing('process_start');
        
        return view('auth.sso-process', [
            'centralSSOUrl' => config('app.central_sso_url'),
            'tenantSlug' => config('app.tenant_slug'),
        ]);
    }

    /**
     * Handle SSO callback with comprehensive security validation
     */
    public function ssoCallback(Request $request)
    {
        $token = $request->input('token');
        
        if (!$token) {
            $this->auditService->recordSSOProcessing('callback_failed', [
                'reason' => 'No token provided'
            ]);
            return response()->json(['error' => 'No token provided'], 400);
        }

        // Validate token via secure API
        $result = $this->ssoService->validateToken($token);
        
        if ($result['valid']) {
            $ssoUser = $result['user'];
            
            // Verify tenant access
            $tenantSlug = config('app.tenant_slug');
            if (!in_array($tenantSlug, $ssoUser['tenants'] ?? [])) {
                $this->auditService->recordSSOProcessing('callback_failed', [
                    'reason' => 'Access denied to tenant',
                    'user_id' => $ssoUser['id'],
                    'email' => $ssoUser['email']
                ]);
                return response()->json(['error' => 'Access denied to this tenant'], 403);
            }

            // Create/update local user
            $localUser = $this->ssoService->createOrUpdateUser($ssoUser);
            
            // Authenticate locally
            $this->ssoService->authenticateUser($localUser);
            
            // Store JWT token
            session(['jwt_token' => $token]);
            session(['sso_user_data' => $ssoUser]);

            // Record successful SSO login
            $this->auditService->recordLogin(
                $ssoUser['id'],
                $ssoUser['email'],
                'sso',
                true
            );

            $this->auditService->recordSSOProcessing('callback_success', [
                'user_id' => $ssoUser['id'],
                'email' => $ssoUser['email']
            ]);
            
            return response()->json([
                'success' => true, 
                'redirect' => route('dashboard')
            ]);
        }

        $this->auditService->recordSSOProcessing('callback_failed', [
            'reason' => 'Invalid token'
        ]);

        return response()->json(['error' => 'Invalid authentication token'], 401);
    }

    /**
     * Handle secure logout with audit
     */
    public function logout(Request $request)
    {
        $logoutFromSSO = $request->get('sso_logout', false);
        
        // Record logout before clearing session
        $this->auditService->recordLogout();
        
        // Clear local session
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        if ($logoutFromSSO) {
            // Also clear JWT token session data
            $request->session()->forget('jwt_token');
            $request->session()->forget('sso_user_data');
            
            // Redirect to central SSO logout to clear the SSO session
            $callback = url('/');
            $ssoLogoutUrl = config('app.central_sso_url') . '/auth/logout?callback_url=' . urlencode($callback);
            return redirect($ssoLogoutUrl);
        }
        
        return redirect('/')->with('success', 'You have been logged out.');
    }

    /**
     * Dashboard with user information
     */
    public function dashboard()
    {
        $localUser = auth()->user();
        $jwtToken = session('jwt_token');
        
        $user = [
            'id' => $localUser->id,
            'name' => $localUser->name,
            'email' => $localUser->email,
            'current_tenant' => config('app.tenant_slug'),
            'tenants' => [config('app.tenant_slug')],
            'is_admin' => $localUser->is_admin ?? false,
        ];
        
        // Get extended tenant info if JWT token is available
        if ($jwtToken) {
            $result = $this->ssoService->validateToken($jwtToken);
            if ($result['valid'] && isset($result['user']['tenants'])) {
                $user['tenants'] = $result['user']['tenants'];
            }
        }
        
        return view('dashboard', compact('user'));
    }

    /**
     * Health check endpoint for monitoring
     */
    public function health()
    {
        $ssoHealthy = $this->ssoService->healthCheck();
        
        return response()->json([
            'status' => $ssoHealthy ? 'healthy' : 'degraded',
            'tenant' => config('app.tenant_slug'),
            'sso_available' => $ssoHealthy,
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0')
        ], $ssoHealthy ? 200 : 503);
    }
}