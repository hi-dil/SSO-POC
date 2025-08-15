<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class SSOController extends Controller
{
    public function showLoginForm($tenant_slug, Request $request)
    {
        $tenant = Tenant::where('slug', $tenant_slug)->first();
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }
        
        $callback_url = $request->get('callback_url');
        
        // Show processing page that will check authentication via JavaScript
        return view('auth.sso-processing', [
            'tenant' => $tenant,
            'tenant_slug' => $tenant_slug,
            'callback_url' => $callback_url
        ]);
    }
    
    /**
     * API endpoint to check authentication status for SSO
     */
    public function checkAuth($tenant_slug, Request $request)
    {
        $tenant = Tenant::where('slug', $tenant_slug)->first();
        
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }
        
        $callback_url = $request->get('callback_url');
        
        \Log::info('SSO Auth Check', [
            'session_id' => session()->getId(),
            'auth_check' => auth()->check(),
            'user_id' => auth()->id(),
            'tenant_slug' => $tenant_slug,
            'callback_url' => $callback_url
        ]);
        
        // Check if user is already authenticated via session
        if (auth()->check()) {
            $user = auth()->user()->load('tenants');
            
            \Log::info('SSO User Found', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'tenants' => $user->tenants->pluck('slug')->toArray()
            ]);
            
            // Check if the authenticated user has access to this tenant
            if ($user->hasAccessToTenant($tenant_slug)) {
                \Log::info('SSO Auto-authenticating user', [
                    'user_id' => $user->id,
                    'tenant_slug' => $tenant_slug
                ]);
                
                // Generate JWT token for auto-authentication
                try {
                    $customClaims = [
                        'tenants' => $user->tenants->map(function($tenant) { return $tenant->slug; })->toArray(),
                        'current_tenant' => $tenant_slug,
                    ];
                    
                    $token = JWTAuth::customClaims($customClaims)->fromUser($user);
                } catch (\Exception $e) {
                    \Log::error('Auto-authentication token generation failed: ' . $e->getMessage());
                    return response()->json(['authenticated' => false, 'redirect_to_login' => true]);
                }
                
                // Return redirect URL with token
                $userParam = urlencode(base64_encode(json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ])));
                
                $callbackUrl = $callback_url . '?token=' . urlencode($token) . '&user=' . $userParam;
                
                return response()->json([
                    'authenticated' => true,
                    'redirect_to' => $callbackUrl
                ]);
            } else {
                \Log::warning('SSO User lacks tenant access', [
                    'user_id' => $user->id,
                    'tenant_slug' => $tenant_slug,
                    'user_tenants' => $user->tenants->pluck('slug')->toArray()
                ]);
                
                return response()->json([
                    'authenticated' => true,
                    'access_denied' => true,
                    'message' => 'You do not have access to this tenant. Please contact your administrator.'
                ]);
            }
        }
        
        \Log::info('SSO No authenticated user found');
        
        // User is not authenticated
        return response()->json(['authenticated' => false, 'redirect_to_login' => true]);
    }
    
    /**
     * Auto-authenticate an already logged-in user for SSO
     */
    private function autoAuthenticate($user, $tenant_slug, $callback_url)
    {
        if (!$callback_url) {
            abort(400, 'Callback URL is required');
        }
        
        try {
            $customClaims = [
                'tenants' => $user->tenants->map(function($tenant) { return $tenant->slug; })->toArray(),
                'current_tenant' => $tenant_slug,
            ];
            
            $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        } catch (\Exception $e) {
            \Log::error('Auto-authentication failed: ' . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Could not create authentication token']);
        }
        
        // Redirect back to tenant app with token and user data
        $userParam = urlencode(base64_encode(json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ])));
        
        $callbackUrl = $callback_url . '?token=' . urlencode($token) . '&user=' . $userParam;
        
        \Log::info('SSO Auto-authentication redirect', [
            'user_id' => $user->id,
            'tenant_slug' => $tenant_slug,
            'callback_url' => $callback_url,
            'session_id_before_redirect' => session()->getId(),
            'auth_check_before_redirect' => auth()->check()
        ]);
        
        // Important: Do NOT invalidate the session during auto-authentication
        // The user should remain logged in to the central SSO
        
        return redirect($callbackUrl);
    }
    
    /**
     * Logout from SSO and optionally redirect to a callback URL
     */
    public function logout(Request $request)
    {
        $callback_url = $request->get('callback_url');
        
        // Log the logout action
        if (auth()->check()) {
            \Log::info('SSO Logout', [
                'user_id' => auth()->id(),
                'email' => auth()->user()->email
            ]);
        }
        
        // Clear the authentication session
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Redirect to callback URL or default logout page
        if ($callback_url && filter_var($callback_url, FILTER_VALIDATE_URL)) {
            return redirect($callback_url);
        }
        
        return redirect('/')->with('message', 'You have been logged out successfully.');
    }
    
    public function handleLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'tenant_slug' => 'required',
            'callback_url' => 'required|url'
        ]);
        
        $user = User::where('email', $request->email)->with('tenants')->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }
        
        if (!$user->hasAccessToTenant($request->tenant_slug)) {
            return back()->withErrors(['email' => 'Access denied to this tenant'])->withInput();
        }
        
        // Log the user into Laravel session for future SSO requests
        auth()->login($user);
        
        try {
            $customClaims = [
                'tenants' => $user->tenants->map(function($tenant) { return $tenant->slug; })->toArray(),
                'current_tenant' => $request->tenant_slug,
            ];
            
            $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Could not create authentication token'])->withInput();
        }
        
        // Redirect back to tenant app with token and user data
        $userParam = urlencode(base64_encode(json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ])));
        
        $callbackUrl = $request->callback_url . '?token=' . urlencode($token) . '&user=' . $userParam;
        \Log::info('SSO Redirect to tenant', [
            'callback_url' => $request->callback_url,
            'full_url' => $callbackUrl,
            'token_length' => strlen($token)
        ]);
        
        return redirect($callbackUrl);
    }
}