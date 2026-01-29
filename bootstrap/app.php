<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\IsCoordinator;
use App\Http\Middleware\TrustProxies; // 👈 IMPORTANTE

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // 👇 ATIVA O TRUST PROXIES
        $middleware->trustProxies(at: TrustProxies::class);

        // 👇 seus aliases continuam intactos
        $middleware->alias([
            'isCoordinator' => IsCoordinator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
