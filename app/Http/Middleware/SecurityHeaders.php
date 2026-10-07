<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // O PHP injeta X-Powered-By antes do Laravel montar a resposta; a remoção
        // definitiva em produção é expose_php=Off no php.ini do servidor.
        header_remove('X-Powered-By');

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Impede que outros sites embutam o ConectaIFPA em iframe (clickjacking).
        // Não afeta os iframes de saída (mapa do Google e VLibras).
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        // Report-Only: o navegador registra violações e não bloqueia recursos.
        // Não altera layout, scripts, VLibras, fontes, Quill nem o mapa.
        $response->headers->set('Content-Security-Policy-Report-Only', $this->contentSecurityPolicy());

        $response->headers->remove('X-Powered-By');

        return $response;
    }

    /**
     * Allowlist alinhada aos recursos já usados nas views (unpkg, Google Fonts,
     * Quill, VLibras e o embed do mapa). Destina-se a calibrar uma CSP futura.
     */
    private function contentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://vlibras.gov.br https://cdn.quilljs.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.quilljs.com",
            "font-src 'self' data: https://fonts.gstatic.com https://unpkg.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://vlibras.gov.br https://unpkg.com",
            "frame-src https://vlibras.gov.br https://maps.google.com https://www.google.com",
            "worker-src 'self' blob:",
        ];

        return implode('; ', $directives);
    }
}
