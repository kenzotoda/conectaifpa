<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_creates_guest_and_organizer_accounts_and_lists_them(): void
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);

        $this->actingAs($coordinator)
            ->post('/register/guest', [
                'name' => 'Ana Convidada',
                'email' => 'ana.convidada@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'participant',
            ])
            ->assertRedirect(route('accounts.managed'));

        $this->actingAs($coordinator)
            ->post('/register/organizer', [
                'name' => 'Bruno Organizador',
                'email' => 'bruno.organizador@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('accounts.managed'));

        $guest = User::query()->where('email', 'ana.convidada@example.com')->first();
        $organizer = User::query()->where('email', 'bruno.organizador@example.com')->first();

        $this->assertSame(User::ROLE_GUEST, $guest->role);
        $this->assertSame(User::ROLE_ORGANIZER, $organizer->role);
        $this->assertSame($coordinator->id, $guest->created_by_user_id);
        $this->assertSame($coordinator->id, $organizer->created_by_user_id);

        $this->actingAs($coordinator)
            ->get(route('accounts.managed'))
            ->assertOk()
            ->assertSee('Ana Convidada')
            ->assertSee('Bruno Organizador');
    }

    public function test_public_registration_cannot_create_a_guest_account(): void
    {
        $this->post('/register', [
            'name' => 'Falsa Convidada',
            'email' => 'falsa@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'guest',
        ])->assertSessionHasErrors('role');

        $this->assertNull(User::query()->where('email', 'falsa@example.com')->first());
    }

    public function test_email_lookup_fills_only_the_matching_role(): void
    {
        $participant = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'name' => 'Carla Participante',
            'email' => 'carla@example.com',
            'participant_profile' => User::PROFILE_DISCENTE,
            'curso' => 'Informática',
        ]);
        $other = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'email' => 'outro@example.com',
        ]);

        $this->actingAs($participant)
            ->getJson(route('accounts.lookup', ['email' => 'carla@example.com', 'papel' => 'participant']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('name', 'Carla Participante');

        $this->actingAs($other)
            ->getJson(route('accounts.lookup', ['email' => 'carla@example.com', 'papel' => 'guest']))
            ->assertForbidden();
    }

    public function test_coauthor_must_be_an_existing_participant_and_keeps_that_name(): void
    {
        $author = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'name' => 'Autor Principal',
            'email' => 'autor@example.com',
        ]);
        $coauthor = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'name' => 'Coautor Real',
            'email' => 'coautor@example.com',
            'participant_profile' => User::PROFILE_DISCENTE,
            'curso' => 'Informática',
        ]);
        $event = Event::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Evento',
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
            'capacity' => 20,
            'internal_capacity' => 10,
            'external_capacity' => 10,
            'description' => 'Descricao',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'campus' => Event::FIXED_CAMPUS,
            'building' => Event::FIXED_BUILDING,
            'venue' => Event::FIXED_VENUE,
            'address' => Event::FIXED_ADDRESS,
            'image' => 'teste.png',
            'coordinator_name' => 'Coordenacao',
            'coordinator_email' => 'coord@example.com',
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->addDay(),
        ]);
        $work = Work::create([
            'event_id' => $event->id,
            'submitter_user_id' => $author->id,
            'work_type' => 'resumo_simples',
            'file_path' => 'arquivo.pdf',
            'status' => Work::STATUS_SUBMITTED,
        ]);

        $this->actingAs($author)
            ->put(route('works.update', $work), [
                'work_type' => 'resumo_simples',
                'coauthors_manual' => [
                    ['author_email' => 'ninguem@example.com', 'author_name' => 'Nome Inventado'],
                ],
            ])
            ->assertSessionHasErrors('coauthors_manual.0.author_email');

        $this->actingAs($author)
            ->put(route('works.update', $work), [
                'work_type' => 'resumo_simples',
                'coauthors_manual' => [
                    ['author_email' => 'coautor@example.com', 'author_name' => 'Nome Inventado', 'institution' => 'Outra'],
                ],
            ])
            ->assertRedirect();

        $saved = $work->authors()->where('is_main_author', false)->first();
        $this->assertSame($coauthor->id, $saved->user_id);
        $this->assertSame('Coautor Real', $saved->author_name);
        $this->assertSame('coautor@example.com', $saved->author_email);
    }

    public function test_guest_only_sees_linked_events_and_cannot_open_event_creation(): void
    {
        $guest = User::factory()->create([
            'role' => User::ROLE_GUEST,
            'name' => 'Diana Convidada',
            'email' => 'diana@example.com',
        ]);
        $linked = Event::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Evento Vinculado',
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
            'capacity' => 20,
            'internal_capacity' => 10,
            'external_capacity' => 10,
            'description' => 'Descricao',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'campus' => Event::FIXED_CAMPUS,
            'building' => Event::FIXED_BUILDING,
            'venue' => Event::FIXED_VENUE,
            'address' => Event::FIXED_ADDRESS,
            'image' => 'teste.png',
            'coordinator_name' => 'Coordenacao',
            'coordinator_email' => 'coord@example.com',
        ]);
        Event::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Evento Alheio',
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
            'capacity' => 20,
            'internal_capacity' => 10,
            'external_capacity' => 10,
            'description' => 'Descricao',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'campus' => Event::FIXED_CAMPUS,
            'building' => Event::FIXED_BUILDING,
            'venue' => Event::FIXED_VENUE,
            'address' => Event::FIXED_ADDRESS,
            'image' => 'teste.png',
            'coordinator_name' => 'Coordenacao',
            'coordinator_email' => 'coord@example.com',
        ]);
        EventGuest::create([
            'event_id' => $linked->id,
            'user_id' => $guest->id,
            'name' => $guest->name,
            'email' => $guest->email,
            'role_type' => 'palestrante',
        ]);

        $this->actingAs($guest)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Evento Vinculado')
            ->assertDontSee('Evento Alheio');

        $this->actingAs($guest)
            ->get('/events/create')
            ->assertForbidden();
    }

    public function test_organizer_manages_only_designated_events(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $organizer = User::factory()->create(['role' => User::ROLE_ORGANIZER]);
        $assigned = $this->simpleEvent($owner, 'Evento Designado');
        $other = $this->simpleEvent($owner, 'Evento Alheio');
        $assigned->organizers()->sync([$organizer->id]);

        $this->actingAs($organizer)
            ->get('/events/create')
            ->assertForbidden();

        $this->actingAs($organizer)
            ->get('/events/edit/'.$assigned->id)
            ->assertOk()
            ->assertSee('Editar Evento');

        $this->actingAs($organizer)
            ->get('/events/edit/'.$other->id)
            ->assertForbidden();

        $this->actingAs($organizer)
            ->get(route('events.certificates.index', $assigned))
            ->assertOk();

        $this->actingAs($organizer)
            ->get(route('events.certificates.index', $other))
            ->assertForbidden();

        $this->actingAs($organizer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Evento Designado')
            ->assertDontSee('Evento Alheio')
            ->assertDontSee('Novo evento');
    }

    public function test_coordinator_sees_my_certificates_only_when_one_exists(): void
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $event = $this->simpleEvent($coordinator, 'Evento do Coordenador');

        $this->actingAs($coordinator)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Meus certificados');

        Certificate::create([
            'user_id' => $coordinator->id,
            'tipo' => Certificate::TYPE_ORGANIZATION,
            'event_id' => $event->id,
            'arquivo_pdf' => 'certificates/'.$event->id.'/coord.pdf',
            'codigo_validacao' => 'COORD-CERT-1',
            'data_emissao' => now(),
        ]);

        $this->actingAs($coordinator->fresh())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Meus certificados');
    }

    public function test_coordinator_can_look_up_their_own_email_as_an_organizer(): void
    {
        $coordinator = User::factory()->create([
            'role' => User::ROLE_COORDINATOR,
            'name' => 'Clara Coordenadora',
            'email' => 'clara@example.com',
        ]);
        $other = User::factory()->create([
            'role' => User::ROLE_COORDINATOR,
            'email' => 'outro.coordenador@example.com',
        ]);

        $this->actingAs($coordinator)
            ->getJson(route('accounts.lookup', ['email' => 'clara@example.com', 'papel' => 'organizer']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('name', 'Clara Coordenadora');

        $this->actingAs($coordinator)
            ->getJson(route('accounts.lookup', ['email' => $other->email, 'papel' => 'organizer']))
            ->assertOk()
            ->assertJsonPath('found', false);

        $this->actingAs($coordinator)
            ->get('/events/create')
            ->assertOk()
            ->assertSee('Incluir meu e-mail');
    }

    private function simpleEvent(User $owner, string $title): Event
    {
        return Event::create([
            'user_id' => $owner->id,
            'title' => $title,
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
            'capacity' => 20,
            'internal_capacity' => 10,
            'external_capacity' => 10,
            'description' => 'Descricao',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonth()->addDay()->toDateString(),
            'campus' => Event::FIXED_CAMPUS,
            'building' => Event::FIXED_BUILDING,
            'venue' => Event::FIXED_VENUE,
            'address' => Event::FIXED_ADDRESS,
            'image' => 'teste.png',
            'coordinator_name' => 'Coordenacao',
            'coordinator_email' => 'coord@example.com',
        ]);
    }
}
