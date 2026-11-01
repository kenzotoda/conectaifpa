<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Discente');
        $response->assertSee('Técnico administrativo');
        $response->assertSee('Estudante de escola pública');
        $response->assertSee('Comunidade em geral');
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_new_users_can_register(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'participant',
            'participant_profile' => 'discente',
            'matricula' => '123456789012',
            'curso' => 'Licenciatura em Computacao',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_external_participant_can_register_without_matricula(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'External User',
            'email' => 'external@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'participant',
            'participant_profile' => 'estudante_escola_publica',
            'institution' => 'Escola Estadual de Exemplo',
            'curso' => '2 ano',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'external@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_external_participant);
        $this->assertSame('estudante_escola_publica', $user->participant_profile);
        $this->assertNull($user->matricula);
        $this->assertSame('Escola Estadual de Exemplo', $user->institution);
        $this->assertSame('2 ano', $user->curso);
    }

    public function test_docente_registers_without_matricula(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Docente User',
            'email' => 'docente@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'participant',
            'participant_profile' => 'docente',
            'curso' => 'Informatica',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'docente@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->is_external_participant);
        $this->assertSame('docente', $user->participant_profile);
        $this->assertNull($user->matricula);
        $this->assertNull($user->institution);
        $this->assertSame('Informatica', $user->curso);
    }

    public function test_general_community_can_register_without_organization(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'name' => 'Comunidade User',
            'email' => 'comunidade@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'participant',
            'participant_profile' => 'comunidade_geral',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'comunidade@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_external_participant);
        $this->assertSame('comunidade_geral', $user->participant_profile);
        $this->assertNull($user->institution);
        $this->assertNull($user->curso);
        $this->assertNull($user->matricula);
    }
}
