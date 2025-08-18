<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\LoginAuditService;

class MainAuthController extends Controller
{
    private LoginAuditService $auditService;

    public function __construct(LoginAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function showLoginForm()
    {
        return view('auth.main-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Record failed login attempt
            $this->auditService->recordFailedLogin(
                $request->email,
                null,
                'direct',
                'Invalid credentials'
            );
            
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }

        // Load user's tenants
        $user->load('tenants');
        $tenants = $user->tenants;

        // Log the user in for Laravel session authentication
        Auth::login($user);

        // Record successful login
        $this->auditService->recordLogin($user, null, 'direct');

        // Redirect to dashboard after login
        return redirect()->route('dashboard');
    }

    public function showDashboard()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Load user's tenants
        $user->load('tenants');
        $tenants = $user->tenants;

        return view('auth.dashboard', [
            'user' => $user,
            'tenants' => $tenants
        ]);
    }

    public function showTenantSelection()
    {
        $userId = session('pending_auth_user');
        
        if (!$userId) {
            return redirect()->route('login')->withErrors(['error' => 'Please login first']);
        }

        $user = User::with('tenants')->find($userId);
        
        if (!$user) {
            session()->forget('pending_auth_user');
            return redirect()->route('login')->withErrors(['error' => 'Session expired']);
        }

        return view('auth.tenant-select', [
            'user' => $user,
            'tenants' => $user->tenants
        ]);
    }

    public function selectTenant(Request $request)
    {
        $request->validate([
            'tenant_slug' => 'required'
        ]);

        $userId = session('pending_auth_user');
        
        if (!$userId) {
            return redirect()->route('login')->withErrors(['error' => 'Please login first']);
        }

        $user = User::find($userId);
        
        if (!$user) {
            session()->forget('pending_auth_user');
            return redirect()->route('login')->withErrors(['error' => 'Session expired']);
        }

        // Verify user has access to selected tenant
        if (!$user->hasAccessToTenant($request->tenant_slug)) {
            return back()->withErrors(['tenant' => 'Access denied to selected tenant']);
        }

        return $this->redirectToTenant($user, $request->tenant_slug);
    }

    public function accessTenant(Request $request)
    {
        $request->validate([
            'tenant_slug' => 'required'
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Please login first']);
        }

        // Verify user has access to selected tenant
        if (!$user->hasAccessToTenant($request->tenant_slug)) {
            return back()->withErrors(['tenant' => 'Access denied to selected tenant']);
        }

        return $this->redirectToTenant($user, $request->tenant_slug);
    }

    private function redirectToTenant($user, $tenantSlug)
    {
        // Generate JWT token
        try {
            $customClaims = [
                'tenants' => $user->tenants->map(function($tenant) { return $tenant->slug; })->toArray(),
                'current_tenant' => $tenantSlug,
            ];
            
            // Get JWT TTL from settings (in minutes)
            $ttl = Setting::getJwtAccessTokenTtl();
            
            $token = JWTAuth::customClaims($customClaims)
                ->setTTL($ttl)
                ->fromUser($user);
            
            // Record SSO login for this tenant
            $this->auditService->recordLogin($user, $tenantSlug, 'sso');
            
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['error' => 'Could not create authentication token']);
        }

        // Clear temporary session
        session()->forget('pending_auth_user');

        // Fetch tenant URL from database
        $tenant = \App\Models\Tenant::where('slug', $tenantSlug)->first();

        if (!$tenant) {
            return redirect()->route('login')->withErrors(['error' => 'Invalid tenant']);
        }

        if (!$tenant->domain) {
            return redirect()->route('login')->withErrors(['error' => 'Tenant URL not configured']);
        }

        // Redirect to tenant application with token as query parameter
        // The tenant application will capture this token and store it in session
        $redirectUrl = $tenant->domain . '/sso/callback?token=' . urlencode($token) . '&user=' . urlencode(base64_encode(json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ])));
        
        return Redirect::away($redirectUrl);
    }

    public function logout()
    {
        // Record logout before clearing session
        $this->auditService->recordLogout();
        
        // Logout from Laravel session
        Auth::logout();
        
        // Clear SSO pending session
        session()->forget('pending_auth_user');
        
        try {
            if ($token = JWTAuth::getToken()) {
                JWTAuth::invalidate($token);
            }
        } catch (\Exception $e) {
            // Token might not exist or be invalid
        }

        // Invalidate the session and regenerate CSRF token
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/')->with('success', 'Logged out successfully');
    }
}