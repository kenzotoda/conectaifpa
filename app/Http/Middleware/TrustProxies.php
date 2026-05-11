<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    protected $proxies = '*';

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO;

    /**
     * Em local/testing, não confiar em proxy implícito ('*'), para que cabeçalhos
     * X-Forwarded-* falsos não forcem cookie Secure em HTTP (sessão perdida → 419).
     */
    protected function proxies(): array|string|null
    {
        if (app()->environment(['local', 'testing'])) {
            return [];
        }

        return parent::proxies();
    }
}
