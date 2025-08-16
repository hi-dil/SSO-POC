<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RequestSigningService
{
    private string $hmacSecret;
    private string $algorithm;
    private int $timeoutMinutes;
    private array $signedHeaders;

    public function __construct()
    {
        $this->hmacSecret = config('security.request_signing.hmac_secret') ?? env('HMAC_SECRET');
        $this->algorithm = config('security.request_signing.hmac_algorithm', 'sha256');
        $this->timeoutMinutes = config('security.request_signing.request_timeout_minutes', 5);
        $this->signedHeaders = config('security.request_signing.signed_headers', [
            'content-type',
            'x-timestamp',
            'x-tenant-id',
            'x-request-id'
        ]);

        if (!$this->hmacSecret) {
            throw new \InvalidArgumentException('HMAC secret is required for request signing');
        }
    }

    /**
     * Generate a signature for an outgoing request
     */
    public function signRequest(
        string $method,
        string $uri,
        array $headers = [],
        string $body = ''
    ): array {
        // Add timestamp and request ID if not present
        if (!isset($headers['x-timestamp'])) {
            $headers['x-timestamp'] = now()->toISOString();
        }
        
        if (!isset($headers['x-request-id'])) {
            $headers['x-request-id'] = $this->generateRequestId();
        }

        // Create canonical request string
        $canonicalRequest = $this->createCanonicalRequest($method, $uri, $headers, $body);
        
        // Generate signature
        $signature = hash_hmac($this->algorithm, $canonicalRequest, $this->hmacSecret);
        
        // Add signature to headers
        $headers['x-signature'] = $signature;
        $headers['x-signature-algorithm'] = $this->algorithm;
        
        Log::debug('Request signed', [
            'method' => $method,
            'uri' => $uri,
            'signature' => substr($signature, 0, 10) . '...',
            'request_id' => $headers['x-request-id']
        ]);

        return $headers;
    }

    /**
     * Verify the signature of an incoming request
     */
    public function verifyRequest(Request $request): array
    {
        try {
            // Check if signature verification is enabled
            if (!config('security.request_signing.verify_signatures', true)) {
                return ['valid' => true, 'message' => 'Signature verification disabled'];
            }

            // Extract signature from request
            $signature = $request->header('x-signature');
            $algorithm = $request->header('x-signature-algorithm', $this->algorithm);
            
            if (!$signature) {
                return ['valid' => false, 'message' => 'Missing signature'];
            }

            // Verify timestamp
            $timestamp = $request->header('x-timestamp');
            if (!$this->isValidTimestamp($timestamp)) {
                return ['valid' => false, 'message' => 'Invalid or expired timestamp'];
            }

            // Create canonical request string
            $headers = $this->extractHeaders($request);
            $canonicalRequest = $this->createCanonicalRequest(
                $request->method(),
                $request->getRequestUri(),
                $headers,
                $request->getContent()
            );

            // Generate expected signature
            $expectedSignature = hash_hmac($algorithm, $canonicalRequest, $this->hmacSecret);

            // Compare signatures using timing-safe comparison
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Request signature verification failed', [
                    'method' => $request->method(),
                    'uri' => $request->getRequestUri(),
                    'expected' => substr($expectedSignature, 0, 10) . '...',
                    'received' => substr($signature, 0, 10) . '...',
                    'ip' => $request->ip()
                ]);
                
                return ['valid' => false, 'message' => 'Invalid signature'];
            }

            Log::info('Request signature verified successfully', [
                'method' => $request->method(),
                'uri' => $request->getRequestUri(),
                'request_id' => $request->header('x-request-id'),
                'ip' => $request->ip()
            ]);

            return ['valid' => true, 'message' => 'Signature verified'];

        } catch (\Exception $e) {
            Log::error('Error verifying request signature', [
                'error' => $e->getMessage(),
                'method' => $request->method(),
                'uri' => $request->getRequestUri(),
                'ip' => $request->ip()
            ]);
            
            return ['valid' => false, 'message' => 'Signature verification error'];
        }
    }

    /**
     * Create canonical request string for signing
     */
    private function createCanonicalRequest(
        string $method,
        string $uri,
        array $headers,
        string $body
    ): string {
        // Normalize method and URI
        $method = strtoupper($method);
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        
        // Sort headers by name (case-insensitive)
        $sortedHeaders = [];
        foreach ($this->signedHeaders as $headerName) {
            $value = $headers[strtolower($headerName)] ?? '';
            if ($value !== '') {
                $sortedHeaders[strtolower($headerName)] = trim($value);
            }
        }
        ksort($sortedHeaders);

        // Create signed headers string
        $signedHeadersString = implode(';', array_keys($sortedHeaders));
        
        // Create canonical headers string
        $canonicalHeaders = '';
        foreach ($sortedHeaders as $name => $value) {
            $canonicalHeaders .= $name . ':' . $value . "\n";
        }

        // Hash the body
        $bodyHash = hash($this->algorithm, $body);

        // Create canonical request
        $canonicalRequest = implode("\n", [
            $method,
            $uri,
            '', // Query string (empty for our use case)
            $canonicalHeaders,
            $signedHeadersString,
            $bodyHash
        ]);

        return $canonicalRequest;
    }

    /**
     * Extract headers from request for signing
     */
    private function extractHeaders(Request $request): array
    {
        $headers = [];
        
        foreach ($this->signedHeaders as $headerName) {
            $value = $request->header($headerName);
            if ($value !== null) {
                $headers[strtolower($headerName)] = $value;
            }
        }

        return $headers;
    }

    /**
     * Validate timestamp to prevent replay attacks
     */
    private function isValidTimestamp(?string $timestamp): bool
    {
        if (!$timestamp) {
            return false;
        }

        try {
            $requestTime = Carbon::parse($timestamp);
            $now = now();
            
            // Check if timestamp is within acceptable range
            $timeDiff = abs($now->diffInMinutes($requestTime));
            
            return $timeDiff <= $this->timeoutMinutes;
            
        } catch (\Exception $e) {
            Log::warning('Invalid timestamp format', [
                'timestamp' => $timestamp,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Generate unique request ID
     */
    private function generateRequestId(): string
    {
        return 'req_' . uniqid() . '_' . bin2hex(random_bytes(8));
    }

    /**
     * Get signing headers for a request
     */
    public function getSigningHeaders(
        string $method,
        string $uri,
        string $tenantId,
        string $body = ''
    ): array {
        $headers = [
            'content-type' => 'application/json',
            'x-timestamp' => now()->toISOString(),
            'x-tenant-id' => $tenantId,
            'x-request-id' => $this->generateRequestId()
        ];

        return $this->signRequest($method, $uri, $headers, $body);
    }

    /**
     * Validate request nonce to prevent replay attacks
     */
    public function validateNonce(string $requestId): bool
    {
        $cacheKey = "request_nonce:{$requestId}";
        
        // Check if nonce already exists
        if (cache()->has($cacheKey)) {
            return false; // Replay attack
        }

        // Store nonce with TTL
        cache()->put($cacheKey, true, now()->addMinutes($this->timeoutMinutes * 2));
        
        return true;
    }

    /**
     * Generate signature for webhook payload
     */
    public function signWebhook(string $payload, string $secret = null): string
    {
        $secret = $secret ?: $this->hmacSecret;
        return hash_hmac($this->algorithm, $payload, $secret);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, string $signature, string $secret = null): bool
    {
        $secret = $secret ?: $this->hmacSecret;
        $expectedSignature = $this->signWebhook($payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}