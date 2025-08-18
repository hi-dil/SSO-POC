<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SSOService
{
    protected $client;
    protected $apiUrl;
    protected $tenantSlug;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10.0,
            'verify' => false, // For local development only
        ]);
        $this->apiUrl = env('CENTRAL_SSO_API', 'http://central-sso:8000/api');
        $this->tenantSlug = env('TENANT_SLUG', 'tenant1');
    }

    public function login($email, $password)
    {
        try {
            $response = $this->client->post($this->apiUrl . '/auth/login', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'tenant_slug' => $this->tenantSlug,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['success']) {
                // Store JWT token in session
                Session::put('jwt_token', $data['token']);
                Session::put('user', $data['user']);
                
                return [
                    'success' => true,
                    'user' => $data['user'],
                    'token' => $data['token']
                ];
            }

            return ['success' => false, 'message' => 'Login failed'];
        } catch (GuzzleException $e) {
            Log::error('SSO Login Error: ' . $e->getMessage());
            
            $errorResponse = null;
            if ($e->hasResponse()) {
                $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
            }
            
            return [
                'success' => false,
                'message' => $errorResponse['message'] ?? 'Authentication failed'
            ];
        }
    }

    public function register($name, $email, $password, $passwordConfirmation)
    {
        try {
            $response = $this->client->post($this->apiUrl . '/auth/register', [
                'json' => [
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'password_confirmation' => $passwordConfirmation,
                    'tenant_slug' => $this->tenantSlug,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['success']) {
                // Store JWT token in session
                Session::put('jwt_token', $data['token']);
                Session::put('user', $data['user']);
                
                return [
                    'success' => true,
                    'user' => $data['user'],
                    'token' => $data['token']
                ];
            }

            return ['success' => false, 'message' => 'Registration failed'];
        } catch (GuzzleException $e) {
            Log::error('SSO Register Error: ' . $e->getMessage());
            
            $errorResponse = null;
            if ($e->hasResponse()) {
                $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
            }
            
            return [
                'success' => false,
                'message' => $errorResponse['message'] ?? 'Registration failed',
                'errors' => $errorResponse['errors'] ?? []
            ];
        }
    }

    public function validateToken($token = null)
    {
        if (!$token) {
            $token = Session::get('jwt_token');
        }

        if (!$token) {
            return ['valid' => false, 'message' => 'No token provided'];
        }

        try {
            $response = $this->client->post($this->apiUrl . '/auth/validate', [
                'json' => [
                    'token' => $token,
                    'tenant_slug' => $this->tenantSlug,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data;
        } catch (GuzzleException $e) {
            Log::error('SSO Validate Error: ' . $e->getMessage());
            return ['valid' => false, 'message' => 'Token validation failed'];
        }
    }

    public function getUser()
    {
        $token = Session::get('jwt_token');
        
        if (!$token) {
            return null;
        }

        try {
            $response = $this->client->get($this->apiUrl . '/auth/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if ($data['success']) {
                return $data['user'];
            }
            
            return null;
        } catch (GuzzleException $e) {
            Log::error('SSO Get User Error: ' . $e->getMessage());
            return null;
        }
    }

    public function logout()
    {
        $token = Session::get('jwt_token');
        
        if ($token) {
            try {
                $this->client->post($this->apiUrl . '/auth/logout', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token
                    ]
                ]);
            } catch (GuzzleException $e) {
                Log::error('SSO Logout Error: ' . $e->getMessage());
            }
        }
        
        Session::forget('jwt_token');
        Session::forget('user');
        
        return true;
    }

    public function isAuthenticated()
    {
        $result = $this->validateToken();
        return $result['valid'] ?? false;
    }
}