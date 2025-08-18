<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request with rate limiting
     */
    public function handle(Request $request, Closure $next, ?string $limit = null): Response
    {
        // Get rate limit configuration
        $rateLimitConfig = $this->getRateLimitConfig($request, $limit);
        
        // Check if rate limiting is enabled
        if (!$rateLimitConfig['enabled']) {
            return $next($request);
        }
        
        // Get rate limit keys
        $keys = $this->getRateLimitKeys($request, $rateLimitConfig);
        
        // Check each key for rate limit violations
        foreach ($keys as $keyConfig) {
            $result = $this->checkRateLimit($keyConfig);
            
            if ($result['exceeded']) {
                return $this->handleRateLimitExceeded($request, $keyConfig, $result);
            }
        }
        
        // Process the request
        $response = $next($request);
        
        // Record successful request for each key
        foreach ($keys as $keyConfig) {
            $this->recordRequest($keyConfig);
        }
        
        // Add rate limit headers to response
        $this->addRateLimitHeaders($response, $keys[0] ?? null);
        
        return $response;
    }
    
    /**
     * Get rate limit configuration based on request
     */
    private function getRateLimitConfig(Request $request, ?string $limit): array
    {
        $config = [
            'enabled' => config('security.rate_limiting.by_ip', true) || 
                        config('security.rate_limiting.by_api_key', true),
            'window_minutes' => 1,
            'store' => config('security.rate_limiting.store', 'cache'),
        ];
        
        // Determine limits based on endpoint and parameters
        if ($limit) {
            // Custom limit specified in middleware parameter
            $config['limit'] = (int) $limit;
        } elseif ($request->is('api/auth/*')) {
            // Authentication endpoints
            $config['limit'] = config('security.rate_limiting.auth_per_minute', 10);
        } elseif ($request->is('api/audit/*')) {
            // Audit endpoints  
            $config['limit'] = config('security.rate_limiting.audit_per_minute', 100);
        } else {
            // Default API endpoints
            $config['limit'] = config('security.rate_limiting.default_per_minute', 60);
        }
        
        return $config;
    }
    
    /**
     * Get rate limit keys for the request
     */
    private function getRateLimitKeys(Request $request, array $config): array
    {
        $keys = [];
        
        // Rate limit by IP address
        if (config('security.rate_limiting.by_ip', true)) {
            $keys[] = [
                'key' => 'rate_limit:ip:' . $request->ip() . ':' . $request->path(),
                'type' => 'ip',
                'identifier' => $request->ip(),
                'limit' => $config['limit'],
                'window_minutes' => $config['window_minutes'],
                'store' => $config['store']
            ];
        }
        
        // Rate limit by API key
        if (config('security.rate_limiting.by_api_key', true)) {
            $apiKey = $this->extractApiKey($request);
            if ($apiKey) {
                $tenantId = $this->getTenantFromApiKey($apiKey);
                $tenantLimit = $this->getTenantRateLimit($tenantId, $config['limit']);
                
                $keys[] = [
                    'key' => 'rate_limit:api_key:' . substr(hash('sha256', $apiKey), 0, 16) . ':' . $request->path(),
                    'type' => 'api_key',
                    'identifier' => $tenantId ?: 'unknown',
                    'limit' => $tenantLimit,
                    'window_minutes' => $config['window_minutes'],
                    'store' => $config['store']
                ];
            }
        }
        
        return $keys;
    }
    
    /**
     * Check rate limit for a specific key
     */
    private function checkRateLimit(array $keyConfig): array
    {
        $key = $keyConfig['key'];
        $limit = $keyConfig['limit'];
        $windowMinutes = $keyConfig['window_minutes'];
        
        // Get current request count
        $current = $this->getCurrentCount($key, $keyConfig['store']);
        $remaining = max(0, $limit - $current);
        $exceeded = $current >= $limit;
        
        // Calculate reset time
        $resetTime = now()->addMinutes($windowMinutes)->timestamp;
        
        return [
            'exceeded' => $exceeded,
            'current' => $current,
            'limit' => $limit,
            'remaining' => $remaining,
            'reset_time' => $resetTime,
            'key_config' => $keyConfig
        ];
    }
    
    /**
     * Handle rate limit exceeded scenario
     */
    private function handleRateLimitExceeded(Request $request, array $keyConfig, array $result): Response
    {
        // Log rate limit violation
        Log::warning('Rate limit exceeded', [
            'type' => $keyConfig['type'],
            'identifier' => $keyConfig['identifier'],
            'limit' => $result['limit'],
            'current' => $result['current'],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->path(),
            'method' => $request->method()
        ]);
        
        // Create rate limit exceeded response
        $response = response()->json([
            'message' => 'Rate limit exceeded',
            'error' => 'too_many_requests',
            'details' => [
                'limit' => $result['limit'],
                'remaining' => $result['remaining'],
                'reset_time' => $result['reset_time'],
                'retry_after' => $this->getRetryAfter($result['reset_time'])
            ]
        ], Response::HTTP_TOO_MANY_REQUESTS);
        
        // Add rate limit headers
        $this->addRateLimitHeaders($response, $keyConfig, $result);
        
        return $response;
    }
    
    /**
     * Record a successful request
     */
    private function recordRequest(array $keyConfig): void
    {
        $key = $keyConfig['key'];
        $windowMinutes = $keyConfig['window_minutes'];
        $store = $keyConfig['store'];
        
        if ($store === 'redis' && app()->bound('redis')) {
            // Use Redis with sliding window
            $redis = app('redis');
            $now = now()->timestamp;
            $windowStart = $now - ($windowMinutes * 60);
            
            // Remove expired entries
            $redis->zremrangebyscore($key, 0, $windowStart);
            
            // Add current request
            $redis->zadd($key, $now, $now . ':' . uniqid());
            
            // Set expiration
            $redis->expire($key, $windowMinutes * 60);
        } else {
            // Use Laravel cache
            $current = Cache::get($key, 0);
            Cache::put($key, $current + 1, now()->addMinutes($windowMinutes));
        }
    }
    
    /**
     * Get current request count for a key
     */
    private function getCurrentCount(string $key, string $store): int
    {
        if ($store === 'redis' && app()->bound('redis')) {
            // Use Redis sliding window count
            $redis = app('redis');
            $windowStart = now()->timestamp - 60; // 1 minute window
            
            // Remove expired entries and count current
            $redis->zremrangebyscore($key, 0, $windowStart);
            return $redis->zcard($key);
        } else {
            // Use Laravel cache
            return Cache::get($key, 0);
        }
    }
    
    /**
     * Extract API key from request
     */
    private function extractApiKey(Request $request): ?string
    {
        // Check X-API-Key header
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return $apiKey;
        }
        
        // Check Authorization header
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }
        
        return null;
    }
    
    /**
     * Get tenant ID from API key
     */
    private function getTenantFromApiKey(string $apiKey): ?string
    {
        $apiKeys = config('security.api_keys', []);
        
        foreach ($apiKeys as $tenant => $key) {
            if ($key === $apiKey) {
                return $tenant;
            }
        }
        
        return null;
    }
    
    /**
     * Get tenant-specific rate limit
     */
    private function getTenantRateLimit(string $tenantId, int $defaultLimit): int
    {
        $tenantConfig = config("security.tenants.{$tenantId}", []);
        return $tenantConfig['rate_limit'] ?? 
               config('security.tenants.default.rate_limit', $defaultLimit);
    }
    
    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders($response, ?array $keyConfig, ?array $result = null): void
    {
        if (!$keyConfig) {
            return;
        }
        
        if (!$result) {
            $result = $this->checkRateLimit($keyConfig);
        }
        
        $response->headers->set('X-RateLimit-Limit', $result['limit']);
        $response->headers->set('X-RateLimit-Remaining', $result['remaining']);
        $response->headers->set('X-RateLimit-Reset', $result['reset_time']);
        
        if ($result['exceeded']) {
            $response->headers->set('Retry-After', $this->getRetryAfter($result['reset_time']));
        }
    }
    
    /**
     * Calculate retry after seconds
     */
    private function getRetryAfter(int $resetTime): int
    {
        return max(1, $resetTime - now()->timestamp);
    }
}