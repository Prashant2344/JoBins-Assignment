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
        // Register custom middleware
        $middleware->alias([
            'api.rate_limit' => \App\Http\Middleware\ApiRateLimit::class,
            'api.protection' => \App\Http\Middleware\ApiProtection::class,
            'api.logging' => \App\Http\Middleware\RequestLogging::class,
        ]);
        
        // Apply middleware to API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\ApiProtection::class,
            \App\Http\Middleware\RequestLogging::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
