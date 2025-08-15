<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class MainAuthController extends Controller
{
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
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }

        // Load user's tenants
        $user->load('tenants');
        $tenants = $user->tenants;

        if ($tenants->isEmpty()) {
            return back()->withErrors(['email' => 'No tenant access assigned'])->withInput();
        }

        // Store user in session temporarily
        session(['pending_auth_user' => $user->id]);

        if ($tenants->count() === 1) {
            // Single tenant - redirect directly
            $tenant = $tenants->first();
            return $this->redirectToTenant($user, $tenant->slug);
        }

        // Multiple tenants - show selection page
        return redirect()->route('tenant.select');
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

    private function redirectToTenant($user, $tenantSlug)
    {
        // Generate JWT token
        try {
            $customClaims = [
                'tenants' => $user->tenants->map(function($tenant) { return $tenant->slug; })->toArray(),
                'current_tenant' => $tenantSlug,
            ];
            
            $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['error' => 'Could not create authentication token']);
        }

        // Clear temporary session
        session()->forget('pending_auth_user');

        // For testing purposes, show success page with token instead of redirecting
        return view('auth.login-success', [
            'token' => $token,
            'user' => $user,
            'tenant_slug' => $tenantSlug,
            'tenant_urls' => [
                'tenant1' => 'http://localhost:8001',
                'tenant2' => 'http://localhost:8002',
            ]
        ]);
    }

    public function logout()
    {
        session()->forget('pending_auth_user');
        
        try {
            if ($token = JWTAuth::getToken()) {
                JWTAuth::invalidate($token);
            }
        } catch (\Exception $e) {
            // Token might not exist or be invalid
        }

        return redirect('/')->with('success', 'Logged out successfully');
    }
}