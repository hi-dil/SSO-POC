<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Services\SecureSSOService;
use App\Services\LoginAuditService;

class SSOCallbackController extends Controller
{
    protected $ssoService;
    protected $auditService;

    public function __construct(SecureSSOService $ssoService, LoginAuditService $auditService)
    {
        $this->ssoService = $ssoService;
        $this->auditService = $auditService;
    }

    public function callback(Request $request)
    {
        $token = $request->get('token');
        $userEncoded = $request->get('user');

        Log::info('SSO Callback: Received parameters', [
            'has_token' => !empty($token),
            'has_user' => !empty($userEncoded),
            'token_length' => $token ? strlen($token) : 0,
            'user_length' => $userEncoded ? strlen($userEncoded) : 0,
            'all_params' => $request->all()
        ]);

        if (!$token || !$userEncoded) {
            Log::error('SSO Callback: Missing token or user data', [
                'token' => $token ? 'present' : 'missing',
                'user' => $userEncoded ? 'present' : 'missing'
            ]);
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

            // Find or create local user based on SSO user data
            $localUser = \App\Models\User::where('email', $userData['email'])->first();
            
            if (!$localUser) {
                // Create new local user if they don't exist
                $localUser = \App\Models\User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)), // Random password since SSO handles auth
                    'sso_user_id' => $userData['id'], // Mark as SSO user
                ]);
                Log::info('SSO Callback: Created new local user', ['email' => $userData['email']]);
            } else {
                // Update existing user's name and mark as SSO user if not already marked
                $localUser->update([
                    'name' => $userData['name'],
                    'sso_user_id' => $userData['id'], // Mark as SSO user
                ]);
                Log::info('SSO Callback: Updated existing user', ['email' => $userData['email']]);
            }
            
            // Authenticate the local user using Laravel's auth system
            auth()->login($localUser, true); // Remember the user
            
            // Regenerate session for security
            request()->session()->regenerate();
            
            // Store JWT token for API calls to central SSO
            Session::put('jwt_token', $token);
            
            // Record SSO login to central audit system
            $this->auditService->recordLogin(
                $userData['id'], // Use central SSO user ID
                $userData['email'],
                'sso', // SSO method
                true // Successful
            );
            
            Log::info('SSO Callback: User authenticated successfully', [
                'user_id' => $localUser->id,
                'email' => $localUser->email
            ]);

            // Redirect to dashboard
            return redirect('/dashboard')->with('success', 'Welcome, ' . $userData['name'] . '!');

        } catch (\Exception $e) {
            Log::error('SSO Callback Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['error' => 'Authentication failed']);
        }
    }
}