<?php

namespace Tests\Feature;

use App\Models\User;
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
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
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
