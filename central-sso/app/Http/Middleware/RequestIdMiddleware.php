<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    /**
     * Handle an incoming request and assign a unique request ID
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate or extract request ID
        $requestId = $this->getOrGenerateRequestId($request);
        
        // Store request ID in request attributes
        $request->attributes->set('request_id', $requestId);
        
        // Add request ID to log context
        Log::withContext([
            'request_id' => $requestId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
        ]);
        
        // Process the request
        $response = $next($request);
        
        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);
        
        // Log request completion
        $this->logRequestCompletion($request, $response, $requestId);
        
        return $response;
    }
    
    /**
     * Get existing request ID or generate a new one
     */
    private function getOrGenerateRequestId(Request $request): string
    {
        // Check if request ID is provided in headers
        $requestId = $request->header('X-Request-ID');
        
        if ($requestId && $this->isValidRequestId($requestId)) {
            return $requestId;
        }
        
        // Generate new request ID
        return $this->generateRequestId();
    }
    
    /**
     * Generate a unique request ID
     */
    private function generateRequestId(): string
    {
        // Format: req_YYYYMMDD_HHMMSS_UUID_RANDOM
        $timestamp = now()->format('Ymd_His');
        $uuid = Str::uuid()->toString();
        $random = bin2hex(random_bytes(4));
        
        return "req_{$timestamp}_{$uuid}_{$random}";
    }
    
    /**
     * Validate request ID format
     */
    private function isValidRequestId(string $requestId): bool
    {
        // Check basic format and length constraints
        if (strlen($requestId) > 100 || strlen($requestId) < 10) {
            return false;
        }
        
        // Check for potentially malicious content
        if (preg_match('/[^\w\-_.]/', $requestId)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log request completion with timing and status
     */
    private function logRequestCompletion(Request $request, $response, string $requestId): void
    {
        $statusCode = $response->getStatusCode();
        $duration = $this->getRequestDuration($request);
        
        $logData = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
        
        // Add user information if available
        if ($request->user()) {
            $logData['user_id'] = $request->user()->id;
            $logData['user_email'] = $request->user()->email;
        }
        
        // Add API key information if available
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            $logData['api_key_hash'] = substr(hash('sha256', $apiKey), 0, 16);
        }
        
        // Log based on status code
        if ($statusCode >= 500) {
            Log::error('Request completed with server error', $logData);
        } elseif ($statusCode >= 400) {
            Log::warning('Request completed with client error', $logData);
        } elseif ($duration > 5000) { // Slow requests over 5 seconds
            Log::warning('Slow request detected', $logData);
        } else {
            Log::info('Request completed successfully', $logData);
        }
    }
    
    /**
     * Calculate request duration in milliseconds
     */
    private function getRequestDuration(Request $request): float
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        return round((microtime(true) - $startTime) * 1000, 2);
    }
    
    /**
     * Get request ID from current request
     */
    public static function getCurrentRequestId(): ?string
    {
        $request = request();
        return $request ? $request->attributes->get('request_id') : null;
    }
}