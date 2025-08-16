<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class SecureSSOService
{
    protected $client;
    protected $apiUrl;
    protected $tenantSlug;
    protected $apiKey;
    protected $hmacSecret;
    protected $hmacAlgorithm;

    public function __construct()
    {
        // Get configuration from environment
        $this->apiUrl = env('CENTRAL_SSO_API', 'http://central-sso:8000/api');
        $this->tenantSlug = env('TENANT_SLUG', 'tenant2');
        $this->apiKey = env('TENANT2_API_KEY');
        $this->hmacSecret = env('HMAC_SECRET');
        $this->hmacAlgorithm = env('HMAC_ALGORITHM', 'sha256');

        // Verify SSL in production
        $sslVerify = env('SSL_VERIFY', app()->environment('production'));
        
        $this->client = new Client([
            'timeout' => 10.0,
            'verify' => $sslVerify,
            'headers' => [
                'User-Agent' => 'SecureSSO-Client/1.0 (' . $this->tenantSlug . ')',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        if (!$this->apiKey) {
            throw new \InvalidArgumentException('API key is required for secure SSO communication');
        }

        if (!$this->hmacSecret) {
            throw new \InvalidArgumentException('HMAC secret is required for request signing');
        }
    }

    /**
     * Authenticate user with enhanced security
     */
    public function login($email, $password)
    {
        try {
            $payload = [
                'email' => $email,
                'password' => $password,
                'tenant_slug' => $this->tenantSlug,
            ];

            $response = $this->makeSecureRequest('POST', '/auth/login', $payload);
            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['success']) {
                // Store JWT token and user data in session
                Session::put('jwt_token', $data['token']);
                Session::put('user', $data['user']);
                
                Log::info('Secure SSO login successful', [
                    'user_id' => $data['user']['id'],
                    'email' => $email,
                    'tenant' => $this->tenantSlug
                ]);
                
                return [
                    'success' => true,
                    'user' => $data['user'],
                    'token' => $data['token']
                ];
            }

            return ['success' => false, 'message' => $data['message'] ?? 'Login failed'];

        } catch (GuzzleException $e) {
            Log::error('Secure SSO Login Error: ' . $e->getMessage(), [
                'tenant' => $this->tenantSlug,
                'email' => $email
            ]);
            
            $errorResponse = $this->parseErrorResponse($e);
            
            return [
                'success' => false,
                'message' => $errorResponse['message'] ?? 'Authentication failed'
            ];
        }
    }

    /**
     * Register user with enhanced security
     */
    public function register($name, $email, $password, $passwordConfirmation)
    {
        try {
            $payload = [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
                'tenant_slug' => $this->tenantSlug,
            ];

            $response = $this->makeSecureRequest('POST', '/auth/register', $payload);
            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['success']) {
                // Store JWT token and user data in session
                Session::put('jwt_token', $data['token']);
                Session::put('user', $data['user']);
                
                Log::info('Secure SSO registration successful', [
                    'user_id' => $data['user']['id'],
                    'email' => $email,
                    'tenant' => $this->tenantSlug
                ]);
                
                return [
                    'success' => true,
                    'user' => $data['user'],
                    'token' => $data['token']
                ];
            }

            return ['success' => false, 'message' => $data['message'] ?? 'Registration failed'];

        } catch (GuzzleException $e) {
            Log::error('Secure SSO Register Error: ' . $e->getMessage(), [
                'tenant' => $this->tenantSlug,
                'email' => $email
            ]);
            
            $errorResponse = $this->parseErrorResponse($e);
            
            return [
                'success' => false,
                'message' => $errorResponse['message'] ?? 'Registration failed',
                'errors' => $errorResponse['errors'] ?? []
            ];
        }
    }

    /**
     * Validate token with enhanced security
     */
    public function validateToken($token = null)
    {
        if (!$token) {
            $token = Session::get('jwt_token');
        }

        if (!$token) {
            return ['valid' => false, 'message' => 'No token provided'];
        }

        try {
            $payload = [
                'token' => $token,
                'tenant_slug' => $this->tenantSlug,
            ];

            $response = $this->makeSecureRequest('POST', '/auth/validate', $payload);
            $data = json_decode($response->getBody()->getContents(), true);
            
            return $data;

        } catch (GuzzleException $e) {
            Log::error('Secure SSO Validate Error: ' . $e->getMessage(), [
                'tenant' => $this->tenantSlug
            ]);
            
            return ['valid' => false, 'message' => 'Token validation failed'];
        }
    }

    /**
     * Record login audit with enhanced security
     */
    public function recordLoginAudit($userId, $email, $method, $success, $failureReason = null)
    {
        try {
            $payload = [
                'user_id' => $userId,
                'email' => $email,
                'tenant_id' => $this->tenantSlug,
                'login_method' => $method,
                'is_successful' => $success,
                'failure_reason' => $failureReason,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];

            $response = $this->makeSecureRequest('POST', '/audit/login', $payload);
            
            Log::debug('Login audit recorded successfully', [
                'user_id' => $userId,
                'tenant' => $this->tenantSlug,
                'method' => $method,
                'success' => $success
            ]);

            return true;

        } catch (GuzzleException $e) {
            Log::warning('Failed to record login audit: ' . $e->getMessage(), [
                'user_id' => $userId,
                'tenant' => $this->tenantSlug,
                'method' => $method
            ]);
            
            // Don't fail the main operation if audit fails
            return false;
        }
    }

    /**
     * Make a secure API request with authentication and signing
     */
    private function makeSecureRequest($method, $endpoint, $payload = [])
    {
        $uri = $endpoint;
        $body = !empty($payload) ? json_encode($payload) : '';
        
        // Generate request headers with security
        $headers = $this->generateSecureHeaders($method, $uri, $body);
        
        // Make the HTTP request
        return $this->client->request($method, $this->apiUrl . $uri, [
            'headers' => $headers,
            'body' => $body
        ]);
    }

    /**
     * Generate secure headers for request authentication and signing
     */
    private function generateSecureHeaders($method, $uri, $body)
    {
        $timestamp = now()->toISOString();
        $requestId = $this->generateRequestId();
        
        $headers = [
            'X-API-Key' => $this->apiKey,
            'X-Timestamp' => $timestamp,
            'X-Tenant-ID' => $this->tenantSlug,
            'X-Request-ID' => $requestId,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        // Generate HMAC signature
        if ($this->hmacSecret) {
            $signature = $this->generateSignature($method, $uri, $headers, $body);
            $headers['X-Signature'] = $signature;
            $headers['X-Signature-Algorithm'] = $this->hmacAlgorithm;
        }

        return $headers;
    }

    /**
     * Generate HMAC signature for request
     */
    private function generateSignature($method, $uri, $headers, $body)
    {
        // Create canonical request string
        $canonicalRequest = $this->createCanonicalRequest($method, $uri, $headers, $body);
        
        // Generate HMAC signature
        return hash_hmac($this->hmacAlgorithm, $canonicalRequest, $this->hmacSecret);
    }

    /**
     * Create canonical request string for signing
     */
    private function createCanonicalRequest($method, $uri, $headers, $body)
    {
        $method = strtoupper($method);
        
        // Headers to include in signature (sorted)
        $signedHeaders = [
            'content-type' => $headers['Content-Type'],
            'x-timestamp' => $headers['X-Timestamp'],
            'x-tenant-id' => $headers['X-Tenant-ID'],
            'x-request-id' => $headers['X-Request-ID']
        ];
        ksort($signedHeaders);

        // Create signed headers string
        $signedHeadersString = implode(';', array_keys($signedHeaders));
        
        // Create canonical headers string
        $canonicalHeaders = '';
        foreach ($signedHeaders as $name => $value) {
            $canonicalHeaders .= $name . ':' . trim($value) . "\n";
        }

        // Hash the body
        $bodyHash = hash($this->hmacAlgorithm, $body);

        // Create canonical request
        return implode("\n", [
            $method,
            $uri,
            '', // Query string (empty)
            $canonicalHeaders,
            $signedHeadersString,
            $bodyHash
        ]);
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId()
    {
        return 'req_' . $this->tenantSlug . '_' . uniqid() . '_' . bin2hex(random_bytes(4));
    }

    /**
     * Parse error response from API
     */
    private function parseErrorResponse(GuzzleException $e)
    {
        $errorResponse = ['message' => 'Request failed'];
        
        if ($e->hasResponse()) {
            try {
                $errorData = json_decode($e->getResponse()->getBody()->getContents(), true);
                if ($errorData) {
                    $errorResponse = $errorData;
                }
            } catch (\Exception $parseError) {
                Log::warning('Failed to parse error response: ' . $parseError->getMessage());
            }
        }
        
        return $errorResponse;
    }

    /**
     * Get current user from session
     */
    public function getUser()
    {
        return Session::get('user');
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated()
    {
        $token = Session::get('jwt_token');
        if (!$token) {
            return false;
        }

        $result = $this->validateToken($token);
        return $result['valid'] ?? false;
    }

    /**
     * Logout user and clean up session
     */
    public function logout()
    {
        $token = Session::get('jwt_token');
        
        if ($token) {
            try {
                // Optionally notify central SSO of logout
                $this->makeSecureRequest('POST', '/auth/logout', []);
            } catch (GuzzleException $e) {
                Log::warning('Failed to notify SSO of logout: ' . $e->getMessage());
            }
        }
        
        // Clear local session
        Session::forget(['jwt_token', 'user']);
        
        Log::info('User logged out', [
            'tenant' => $this->tenantSlug
        ]);
        
        return true;
    }

    /**
     * Generate a new API key (utility method)
     */
    public static function generateApiKey($prefix = 'tenant')
    {
        $randomBytes = random_bytes(32);
        $timestamp = time();
        $hash = hash('sha256', $prefix . $randomBytes . $timestamp);
        
        return $prefix . '_' . substr($hash, 0, 40);
    }
}