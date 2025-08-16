<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'super-admin' => \App\Http\Middleware\CheckSuperAdmin::class,
            'telescope.access' => \App\Http\Middleware\TelescopeAccessMiddleware::class,
            'swagger.access' => \App\Http\Middleware\SwaggerAccessMiddleware::class,
            'track.activity' => \App\Http\Middleware\TrackUserActivity::class,
        ]);
        
        // Add activity tracking to web middleware group
        $middleware->web(append: [
            \App\Http\Middleware\TrackUserActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
