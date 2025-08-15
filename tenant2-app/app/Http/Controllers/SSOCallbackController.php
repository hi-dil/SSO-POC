<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Services\SSOService;

class SSOCallbackController extends Controller
{
    protected $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function callback(Request $request)
    {
        $token = $request->get('token');
        $userEncoded = $request->get('user');

        if (!$token || !$userEncoded) {
            Log::error('SSO Callback: Missing token or user data');
            return redirect('/login')->withErrors(['error' => 'Invalid SSO callback']);
        }

        try {
            // Decode user data
            $userData = json_decode(base64_decode($userEncoded), true);
            
            if (!$userData) {
                throw new \Exception('Invalid user data');
            }

            // Validate the token with central SSO
            $validation = $this->ssoService->validateToken($token);
            
            if (!($validation['valid'] ?? false)) {
                Log::error('SSO Callback: Token validation failed', $validation);
                return redirect('/login')->withErrors(['error' => 'Invalid authentication token']);
            }

            // Store token and user data in session
            Session::put('jwt_token', $token);
            Session::put('user', $userData);
            
            Log::info('SSO Callback: User authenticated successfully', [
                'user_id' => $userData['id'],
                'email' => $userData['email']
            ]);

            // Redirect to dashboard
            return redirect('/dashboard')->with('success', 'Welcome, ' . $userData['name'] . '!');

        } catch (\Exception $e) {
            Log::error('SSO Callback Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['error' => 'Authentication failed']);
        }
    }
}