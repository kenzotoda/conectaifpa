<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailQueued;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->withPersonalTeam()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
        $response->assertSee('Confirme seu e-mail');
        $response->assertSee('Reenviar e-mail de confirmação');
        $response->assertSee('Já ativei');
        $response->assertSee('Sair da conta');
        $response->assertSee($user->email);
        $response->assertSee(config('mail.from.address'));
        $response->assertDontSee('conectaifpa@gmail.com');
        $response->assertDontSee('Edit Profile');
    }

    public function test_email_can_be_verified(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertOk()->assertSee('Conta ativada')->assertSee('Continuar');
    }

    public function test_activation_link_works_without_a_session_on_that_device(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();
        $url = (new VerifyEmailQueued)->toMail($user)->actionUrl;
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->get($path.'?'.$query)
            ->assertOk()
            ->assertSee('Conta ativada')
            ->assertSee('Já ativei');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->actingAs($user->fresh())
            ->get(route('verification.status'))
            ->assertOk()
            ->assertJson(['verified' => true]);

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_waiting_screen_can_ask_whether_the_account_is_active(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.status'))
            ->assertOk()
            ->assertJson(['verified' => false]);
    }

    public function test_email_can_not_verified_with_invalid_hash(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_link_ignores_the_host_seen_by_the_server(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();
        $url = (new VerifyEmailQueued)->toMail($user)->actionUrl;

        $this->assertStringStartsWith(rtrim((string) config('app.url'), '/'), $url);

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $this->actingAs($user)
            ->get('http://outro-endereco.test'.$path.'?'.$query)
            ->assertOk()
            ->assertSee('Conta ativada');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_broken_verification_link_explains_how_to_ask_for_another(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/email/verify/'.$user->id.'/'.sha1($user->email).'?expires=1&signature=invalida')
            ->assertForbidden()
            ->assertSee('Peça outro link');

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_seeded_accounts_enter_without_email_verification(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([
            'admin@conectaifpa.com',
            'reviewer@conectaifpa.com',
            'aluno@conectaifpa.com',
        ] as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertTrue($user->hasVerifiedEmail());

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk();
        }
    }
}
