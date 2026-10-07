<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_public_pages_send_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertCount(1, $response->headers->all('X-Frame-Options'));
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeaderMissing('X-Powered-By');

        $permissions = $response->headers->get('Permissions-Policy');
        $this->assertNotNull($permissions);
        $this->assertStringContainsString('camera=()', $permissions);

        $csp = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString('https://vlibras.gov.br', $csp);

        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }
}
