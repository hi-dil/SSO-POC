<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ApiKeyAuth
{
    /**
     * Handle an incoming request for API key authentication.
     * 
     * This middleware validates API keys for server-to-server communication
     * between the central SSO and tenant applications.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  $requiredScope  Optional scope required for the API key
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ?string $requiredScope = null)
    {
        $apiKey = $this->extractApiKey($request);
        
        if (!$apiKey) {
            Log::warning('API request without API key', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'API key required',
                'error_code' => 'MISSING_API_KEY'
            ], 401);
        }

        $keyData = $this->validateApiKey($apiKey);
        
        if (!$keyData) {
            Log::warning('Invalid API key used', [
                'api_key_prefix' => substr($apiKey, 0, 8) . '...',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
                'error_code' => 'INVALID_API_KEY'
            ], 401);
        }

        // Check if API key has required scope
        if ($requiredScope && !$this->hasScope($keyData, $requiredScope)) {
            Log::warning('API key missing required scope', [
                'tenant' => $keyData['tenant'],
                'required_scope' => $requiredScope,
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Insufficient API key permissions',
                'error_code' => 'INSUFFICIENT_SCOPE'
            ], 403);
        }

        // Add tenant information to request for downstream use
        $request->merge([
            'api_tenant' => $keyData['tenant'],
            'api_scopes' => $keyData['scopes'] ?? []
        ]);

        // Log successful API authentication
        Log::info('API key authenticated successfully', [
            'tenant' => $keyData['tenant'],
            'scopes' => $keyData['scopes'] ?? [],
            'ip' => $request->ip()
        ]);

        return $next($request);
    }

    /**
     * Extract API key from request headers or query parameters
     */
    private function extractApiKey(Request $request): ?string
    {
        // Try X-API-Key header first (preferred)
        $apiKey = $request->header('X-API-Key');
        
        if ($apiKey) {
            return $apiKey;
        }

        // Try Authorization header with API-Key scheme
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'API-Key ')) {
            return substr($authHeader, 8);
        }

        // Fallback to query parameter (less secure, only for development)
        if (config('app.env') === 'local') {
            return $request->query('api_key');
        }

        return null;
    }

    /**
     * Validate API key and return associated data
     */
    private function validateApiKey(string $apiKey): ?array
    {
        // Get configured API keys from environment
        $configuredKeys = $this->getConfiguredApiKeys();
        
        foreach ($configuredKeys as $keyData) {
            if (hash_equals($keyData['key'], $apiKey)) {
                return $keyData;
            }
        }

        return null;
    }

    /**
     * Get configured API keys from environment variables
     */
    private function getConfiguredApiKeys(): array
    {
        $keys = [];
        
        // Load API keys for each tenant
        $tenants = ['tenant1', 'tenant2']; // Could be dynamic from config
        
        foreach ($tenants as $tenant) {
            $envKey = strtoupper($tenant) . '_API_KEY';
            $apiKey = config("security.api_keys.{$tenant}") ?? env($envKey);
            
            if ($apiKey) {
                $keys[] = [
                    'key' => $apiKey,
                    'tenant' => $tenant,
                    'scopes' => ['audit', 'auth', 'validate'], // Default scopes
                    'created_at' => now(),
                    'last_used' => null
                ];
            }
        }

        // Add master API key if configured
        $masterKey = config('security.api_keys.master') ?? env('MASTER_API_KEY');
        if ($masterKey) {
            $keys[] = [
                'key' => $masterKey,
                'tenant' => 'master',
                'scopes' => ['*'], // All scopes
                'created_at' => now(),
                'last_used' => null
            ];
        }

        return $keys;
    }

    /**
     * Check if API key has required scope
     */
    private function hasScope(array $keyData, string $requiredScope): bool
    {
        $scopes = $keyData['scopes'] ?? [];
        
        // Wildcard scope grants all permissions
        if (in_array('*', $scopes)) {
            return true;
        }

        // Check for exact scope match
        return in_array($requiredScope, $scopes);
    }

    /**
     * Generate a new API key for a tenant
     * 
     * This is a utility method for generating secure API keys
     */
    public static function generateApiKey(string $prefix = 'sso'): string
    {
        $randomBytes = random_bytes(32);
        $timestamp = time();
        $hash = hash('sha256', $prefix . $randomBytes . $timestamp);
        
        return $prefix . '_' . substr($hash, 0, 40);
    }

    /**
     * Hash an API key for secure storage
     */
    public static function hashApiKey(string $apiKey): string
    {
        return hash('sha256', $apiKey . config('app.key'));
    }
}