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

        $result = $this->ssoService->login($request->email, $request->password);

        if ($result['success']) {
            return redirect()->intended('/dashboard')->with('success', 'Welcome back!');
        }

        return back()->withErrors(['email' => $result['message']])->withInput();
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
        $callback = url('/auth/callback');
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
            session(['jwt_token' => $token]);
            session(['user' => $result['user']]);
            
            return redirect('/dashboard')->with('success', 'Welcome!');
        }

        return redirect('/login')->withErrors(['error' => 'Invalid authentication token']);
    }

    public function logout()
    {
        $this->ssoService->logout();
        return redirect('/')->with('success', 'You have been logged out.');
    }

    public function dashboard()
    {
        if (!$this->ssoService->isAuthenticated()) {
            return redirect('/login')->withErrors(['error' => 'Please login to continue']);
        }

        $user = session('user');
        return view('dashboard', compact('user'));
    }
}
