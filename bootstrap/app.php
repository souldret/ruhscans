<?php

use App\Http\Middleware\EnsureAdult;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustCloudflareProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(prepend: [
            TrustCloudflareProxies::class,
        ]);

        $middleware->web(append: [
            EnsureAdult::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'adult' => EnsureAdult::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
