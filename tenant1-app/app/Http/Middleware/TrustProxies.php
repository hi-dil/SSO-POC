<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Trust all proxies in development, specific IPs in production
        $this->proxies = $this->getTrustedProxies();
    }

    /**
     * Get the trusted proxy configuration based on environment.
     *
     * @return array|string|null
     */
    protected function getTrustedProxies()
    {
        $trustedProxies = env('TRUSTED_PROXIES');
        
        if ($trustedProxies === '*') {
            // Trust all proxies - use with caution, only for development or trusted networks
            return '*';
        }
        
        if ($trustedProxies) {
            // Split comma-separated list of proxy IPs/ranges
            return array_map('trim', explode(',', $trustedProxies));
        }
        
        // Default Cloudflare IP ranges for production
        return $this->getCloudflareProxies();
    }

    /**
     * Get Cloudflare's proxy IP ranges.
     *
     * @return array
     */
    protected function getCloudflareProxies(): array
    {
        return [
            // Cloudflare IPv4 ranges
            '173.245.48.0/20',
            '103.21.244.0/22',
            '103.22.200.0/22',
            '103.31.4.0/22',
            '141.101.64.0/18',
            '108.162.192.0/18',
            '190.93.240.0/20',
            '188.114.96.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
            '162.158.0.0/15',
            '104.16.0.0/13',
            '104.24.0.0/14',
            '172.64.0.0/13',
            '131.0.72.0/22',
            
            // Cloudflare IPv6 ranges (optional)
            '2400:cb00::/32',
            '2606:4700::/32',
            '2803:f800::/32',
            '2405:b500::/32',
            '2405:8100::/32',
            '2a06:98c0::/29',
            '2c0f:f248::/32',
        ];
    }
}