<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SwaggerAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            abort(403, 'Access denied. Please login first.');
        }

        $user = Auth::user();

        // Check if user has permission to access Swagger docs
        if (!$user->hasPermissionTo('swagger.access')) {
            abort(403, 'Access denied. You do not have permission to access API documentation.');
        }

        return $next($request);
    }
}
