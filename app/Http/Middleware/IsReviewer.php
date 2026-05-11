<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsReviewer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! Auth::user()->isReviewer()) {
            abort(403, 'Acesso negado. Somente avaliadores podem acessar esta página.');
        }

        return $next($request);
    }
}
