<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SSOService;

class SSOAuthenticate
{
    protected $ssoService;

    public function __construct(SSOService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check Laravel's session-based authentication first
        if (auth()->check()) {
            return $next($request);
        }
        
        // Fallback to JWT token validation for API-style authentication
        if ($this->ssoService->isAuthenticated()) {
            return $next($request);
        }

        return redirect('/login')->withErrors(['error' => 'Please login to continue']);
    }
}
