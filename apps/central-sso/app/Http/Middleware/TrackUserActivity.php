<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LoginAuditService;
use Illuminate\Support\Facades\Auth;

class TrackUserActivity
{
    private LoginAuditService $auditService;

    public function __construct(LoginAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Process the request first
        $response = $next($request);

        // Track activity only for authenticated users
        if (Auth::check()) {
            $this->trackActivity($request);
        }

        return $response;
    }

    /**
     * Track user activity
     */
    private function trackActivity(Request $request): void
    {
        try {
            // Extract tenant context from request if available
            $tenantId = $this->extractTenantContext($request);
            
            // Prepare activity data
            $activityData = [
                'route' => $request->route()?->getName(),
                'url' => $request->url(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString(),
            ];

            // Update activity
            $this->auditService->updateActivity(
                session()->getId(),
                $tenantId,
                $activityData
            );
        } catch (\Exception $e) {
            // Log error but don't break the request
            \Log::error('Failed to track user activity: ' . $e->getMessage());
        }
    }

    /**
     * Extract tenant context from the request
     */
    private function extractTenantContext(Request $request): ?string
    {
        // Check for tenant in request parameters
        if ($request->has('tenant_id')) {
            return $request->get('tenant_id');
        }

        // Check for tenant in route parameters
        if ($request->route() && $request->route()->hasParameter('tenant')) {
            return $request->route()->parameter('tenant');
        }

        // Check for tenant in session
        if (session()->has('current_tenant')) {
            return session()->get('current_tenant');
        }

        // Check for tenant in URL path (admin routes)
        $path = $request->path();
        if (preg_match('/^admin\/tenants\/([^\/]+)/', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
