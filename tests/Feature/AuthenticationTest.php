<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_remember_me_sets_a_long_lived_cookie_and_restores_the_session(): void
    {
        $user = User::factory()->create();

        $withoutRemember = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $withoutRemember->assertCookieMissing(Auth::guard()->getRecallerName());

        $this->post('/logout');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $recaller = $response->getCookie(Auth::guard()->getRecallerName(), decrypt: false);

        $this->assertNotNull($recaller);
        $this->assertGreaterThan(now()->addDays(300)->getTimestamp(), $recaller->getExpiresTime());

        $response->baseResponse->prepare(Request::create('https://conectaifpa.test/dashboard', 'GET'));
        $this->assertTrue($recaller->isSecure());

        $this->flushSession();

        $this->withCookie($recaller->getName(), $recaller->getValue())
            ->get(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }
}
