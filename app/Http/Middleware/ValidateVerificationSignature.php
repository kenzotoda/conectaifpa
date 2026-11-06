<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpFoundation\Response;

class ValidateVerificationSignature
{
    /**
     * Aceita a assinatura do caminho e também a assinatura antiga, feita com o endereço completo.
     * O clique deixa de falhar quando o https do e-mail e o http visto pelo servidor não coincidem.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidSignature(false) || $request->hasValidSignature(true)) {
            return $next($request);
        }

        throw new InvalidSignatureException;
    }
}
