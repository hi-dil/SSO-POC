<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SSOService;

class AuthController extends Controller
{
    protected $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Try local authentication first
        $credentials = $request->only('email', 'password');
        
        if (auth()->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard')->with('success', 'Welcome back!');
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = $this->ssoService->register(
            $request->name,
            $request->email,
            $request->password,
            $request->password_confirmation
        );

        if ($result['success']) {
            return redirect('/dashboard')->with('success', 'Registration successful!');
        }

        $errors = $result['errors'] ?? ['email' => $result['message']];
        return back()->withErrors($errors)->withInput();
    }

    public function ssoRedirect()
    {
        $callback = url('/sso/callback');
        $ssoUrl = env('CENTRAL_SSO_URL') . '/auth/' . env('TENANT_SLUG');
        
        return redirect($ssoUrl . '?callback_url=' . urlencode($callback));
    }

    public function ssoCallback(Request $request)
    {
        $token = $request->get('token');
        
        if (!$token) {
            return redirect('/login')->withErrors(['error' => 'Authentication failed']);
        }

        // Validate token with central SSO
        $result = $this->ssoService->validateToken($token);
        
        if ($result['valid']) {
            $ssoUser = $result['user'];
            
            // Find or create local user based on SSO user data
            $localUser = \App\Models\User::where('email', $ssoUser['email'])->first();
            
            if (!$localUser) {
                // Create new local user if they don't exist
                $localUser = \App\Models\User::create([
                    'name' => $ssoUser['name'],
                    'email' => $ssoUser['email'],
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)), // Random password since SSO handles auth
                ]);
            } else {
                // Update existing user's name in case it changed
                $localUser->update(['name' => $ssoUser['name']]);
            }
            
            // Authenticate the local user using Laravel's auth system
            auth()->login($localUser);
            
            // Store JWT token for API calls to central SSO
            session(['jwt_token' => $token]);
            
            return redirect('/dashboard')->with('success', 'Welcome!');
        }

        return redirect('/login')->withErrors(['error' => 'Invalid authentication token']);
    }

    public function logout(Request $request)
    {
        // Check if user wants to logout from all SSO sessions
        $logoutFromSSO = $request->get('sso_logout', false);
        
        // Logout from local session
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        if ($logoutFromSSO) {
            // Also clear JWT token session data
            $request->session()->forget('jwt_token');
            
            // Redirect to central SSO logout to clear the SSO session
            $callback = url('/');
            $ssoLogoutUrl = env('CENTRAL_SSO_URL') . '/auth/logout?callback_url=' . urlencode($callback);
            return redirect($ssoLogoutUrl);
        }
        
        return redirect('/')->with('success', 'You have been logged out.');
    }

    public function dashboard()
    {
        // Get local authenticated user (auth middleware ensures user is authenticated)
        $localUser = auth()->user();
        
        // Try to get extended user info from JWT token if available
        $jwtToken = session('jwt_token');
        $user = [
            'id' => $localUser->id,
            'name' => $localUser->name,
            'email' => $localUser->email,
            'current_tenant' => env('TENANT_SLUG'),
            'tenants' => [env('TENANT_SLUG')] // Default to current tenant
        ];
        
        // If we have a JWT token, try to get the full tenant info
        if ($jwtToken) {
            $result = $this->ssoService->validateToken($jwtToken);
            if ($result['valid'] && isset($result['user']['tenants'])) {
                $user['tenants'] = $result['user']['tenants'];
            }
        }
        
        return view('dashboard', compact('user'));
    }
}
