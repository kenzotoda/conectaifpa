<?php

use App\Http\Middleware\EnsureVerifiedEmail;
use App\Http\Middleware\IsCoordinator;
use App\Http\Middleware\ManagesEvent;
use App\Http\Middleware\IsReviewer;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

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

        // 👇 cabeçalhos de segurança em todas as respostas
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [
            EnsureVerifiedEmail::class,
        ]);

        // 👇 seus aliases continuam intactos
        $middleware->alias([
            'isCoordinator' => IsCoordinator::class,
            'managesEvent' => ManagesEvent::class,
            'isReviewer' => IsReviewer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if (! $request->routeIs('verification.verify')) {
                return null;
            }

            return response()->view('auth.verification-link-invalid', [], 403);
        });
    })
    ->create();
