<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAudienceCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_quota_blocks_only_internal_participants(): void
    {
        $event = $this->makeEvent(['capacity' => 2, 'internal_capacity' => 1, 'external_capacity' => 1]);
        $firstInternal = $this->makeParticipant(false);
        $secondInternal = $this->makeParticipant(false);
        $external = $this->makeParticipant(true);

        $this->join($firstInternal, $event)->assertRedirect('/dashboard');
        $this->assertSame(1, $event->fresh()->audienceRegistrationCounts()['internal']);

        $this->join($secondInternal, $event)
            ->assertRedirect('/events/'.$event->id)
            ->assertSessionHas('msg', 'Não há mais vagas para participantes internos neste evento.');

        $this->join($external, $event)->assertRedirect('/dashboard');
        $this->assertSame(1, $event->fresh()->audienceRegistrationCounts()['external']);
    }

    public function test_external_quota_blocks_only_external_participants(): void
    {
        $event = $this->makeEvent(['capacity' => 2, 'internal_capacity' => 1, 'external_capacity' => 1]);
        $external = $this->makeParticipant(true);
        $anotherExternal = $this->makeParticipant(true);
        $internal = $this->makeParticipant(false);

        $this->join($external, $event)->assertRedirect('/dashboard');

        $this->join($anotherExternal, $event)
            ->assertRedirect('/events/'.$event->id)
            ->assertSessionHas('msg', 'Não há mais vagas para participantes externos neste evento.');

        $this->join($internal, $event)->assertRedirect('/dashboard');
    }

    public function test_legacy_event_without_quotas_keeps_total_capacity(): void
    {
        $event = $this->makeEvent([
            'capacity' => 1,
            'internal_capacity' => null,
            'external_capacity' => null,
        ]);
        $internal = $this->makeParticipant(false);
        $external = $this->makeParticipant(true);

        $this->assertFalse($event->hasAudienceQuotas());
        $this->join($internal, $event)->assertRedirect('/dashboard');
        $this->join($external, $event)
            ->assertRedirect('/events/'.$event->id)
            ->assertSessionHas('msg', 'Não há mais vagas para este evento.');
    }

    public function test_edit_refuses_a_limit_below_current_registrations(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'capacity' => 4,
            'internal_capacity' => 2,
            'external_capacity' => 2,
        ]);
        $event->users()->attach($this->makeParticipant(false)->id);
        $event->users()->attach($this->makeParticipant(false)->id);

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->updatePayload($event, [
                'capacity' => 3,
                'internal_capacity' => 1,
                'external_capacity' => 2,
            ]))
            ->assertSessionHasErrors('internal_capacity');

        $event->refresh();
        $this->assertSame(2, (int) $event->internal_capacity);
        $this->assertSame(4, (int) $event->capacity);
    }

    public function test_edit_accepts_a_lower_limit_after_registrations_fit_and_enrollment_follows_it(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'capacity' => 4,
            'internal_capacity' => 2,
            'external_capacity' => 2,
        ]);
        $internal = $this->makeParticipant(false);
        $event->users()->attach($internal->id);

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->updatePayload($event, [
                'capacity' => 2,
                'internal_capacity' => 1,
                'external_capacity' => 1,
            ]))
            ->assertRedirect('/dashboard');

        $event->refresh();
        $this->assertSame(1, (int) $event->internal_capacity);
        $this->assertSame(1, (int) $event->external_capacity);

        $this->join($this->makeParticipant(false), $event)
            ->assertSessionHas('msg', 'Não há mais vagas para participantes internos neste evento.');
        $this->join($this->makeParticipant(true), $event)->assertRedirect('/dashboard');
    }

    public function test_removing_a_participant_frees_that_category_spot(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'capacity' => 2,
            'internal_capacity' => 1,
            'external_capacity' => 1,
        ]);
        $internal = $this->makeParticipant(false);
        $event->users()->attach($internal->id);

        $this->join($this->makeParticipant(false), $event)
            ->assertSessionHas('msg', 'Não há mais vagas para participantes internos neste evento.');

        $this->actingAs($coordinator)
            ->delete('/events/'.$event->id.'/remove/'.$internal->id)
            ->assertSessionHas('msg', 'Participante removido. A vaga interna foi liberada.');

        $this->join($this->makeParticipant(false), $event)->assertRedirect('/dashboard');
    }

    public function test_edit_requires_category_quotas_to_sum_to_total_capacity(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent(['user_id' => $coordinator->id]);

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->updatePayload($event, [
                'capacity' => 4,
                'internal_capacity' => 3,
                'external_capacity' => 2,
            ]))
            ->assertSessionHasErrors('external_capacity');
    }

    public function test_suggested_split_keeps_existing_registrations_and_sums_to_capacity(): void
    {
        $event = $this->makeEvent([
            'capacity' => 10,
            'internal_capacity' => null,
            'external_capacity' => null,
        ]);
        $event->users()->attach($this->makeParticipant(false)->id);
        $event->users()->attach($this->makeParticipant(false)->id);
        $event->users()->attach($this->makeParticipant(false)->id);
        $event->users()->attach($this->makeParticipant(true)->id);

        $suggested = $event->fresh()->suggestedAudienceCapacities();

        $this->assertSame(6, $suggested['internal']);
        $this->assertSame(4, $suggested['external']);
        $this->assertSame(10, $suggested['internal'] + $suggested['external']);
    }

    public function test_coordinator_sees_category_counts_on_dashboard_and_registered_page(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'title' => 'Semana de Tecnologia',
            'capacity' => 4,
            'internal_capacity' => 3,
            'external_capacity' => 1,
        ]);
        $internal = $this->makeParticipant(false);
        $external = $this->makeParticipant(true);
        $event->users()->attach([$internal->id, $external->id]);

        $this->actingAs($coordinator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Semana de Tecnologia')
            ->assertSee('Internos 1/3')
            ->assertSee('Externos 1/1');

        $this->actingAs($coordinator)
            ->get('/events/registered/'.$event->id)
            ->assertOk()
            ->assertSee('Internos')
            ->assertSee('Externos')
            ->assertSee($internal->name)
            ->assertSee($external->name)
            ->assertSee('/ 3')
            ->assertSee('/ 1')
            ->assertSee('Ações');
    }

    public function test_finalized_event_keeps_the_participant_list_and_hides_removal(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'title' => 'Evento encerrado',
            'finalized_at' => now(),
        ]);
        $participant = $this->makeParticipant(false);
        $event->users()->attach($participant->id);

        $this->actingAs($coordinator)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('/events/registered/'.$event->id, false);

        $this->actingAs($coordinator)
            ->get('/events/registered/'.$event->id)
            ->assertOk()
            ->assertSee($participant->name)
            ->assertSee('A remoção de inscritos está bloqueada.')
            ->assertSee('Ações')
            ->assertSee('Bloqueada')
            ->assertDontSee('Remover participante');

        $this->actingAs($coordinator)
            ->delete('/events/'.$event->id.'/remove/'.$participant->id)
            ->assertRedirect()
            ->assertSessionHas('msg', 'Este evento foi finalizado. Não é possível alterar a lista de inscritos.');

        $this->assertTrue($event->users()->where('users.id', $participant->id)->exists());
    }

    public function test_create_and_edit_forms_show_the_quota_fields(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent(['user_id' => $coordinator->id]);

        $this->actingAs($coordinator)
            ->get('/events/create')
            ->assertOk()
            ->assertSee('Vagas internas')
            ->assertSee('Vagas externas')
            ->assertSee('Dividir igualmente');

        $this->actingAs($coordinator)
            ->get('/events/edit/'.$event->id)
            ->assertOk()
            ->assertSee('Inscritos agora')
            ->assertSee('Vagas internas');
    }

    public function test_event_page_tells_each_audience_when_its_spots_are_gone(): void
    {
        $event = $this->makeEvent(['capacity' => 2, 'internal_capacity' => 1, 'external_capacity' => 1]);
        $event->users()->attach($this->makeParticipant(false)->id);
        $internal = $this->makeParticipant(false);
        $external = $this->makeParticipant(true);

        $this->actingAs($internal)
            ->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('As vagas para participantes internos estão esgotadas.')
            ->assertSee('Não há vagas para o seu perfil')
            ->assertDontSee('Vagas internas')
            ->assertDontSee('internas restantes');

        $coordinator = User::factory()->create();
        $event->update(['user_id' => $coordinator->id]);

        $this->actingAs($coordinator)
            ->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Vagas internas')
            ->assertSee('0 restantes')
            ->assertSee('1 de 1 ocupadas');

        auth()->logout();

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Há vagas')
            ->assertDontSee('Vagas internas')
            ->assertDontSee('Vagas externas');

        $this->actingAs($external)
            ->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Realizar inscrição')
            ->assertSee('Há vagas para o seu perfil')
            ->assertDontSee('As vagas para participantes externos estão esgotadas.');
    }

    private function join(User $user, Event $event)
    {
        return $this->actingAs($user)
            ->from('/events/'.$event->id)
            ->post('/events/join/'.$event->id);
    }

    private function makeParticipant(bool $external): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'is_external_participant' => $external,
            'participant_profile' => $external ? User::PROFILE_COMUNIDADE_GERAL : User::PROFILE_DISCENTE,
            'institution' => $external ? 'Comunidade Exemplo' : null,
        ]);
    }

    private function makeEvent(array $overrides = []): Event
    {
        $coordinatorId = $overrides['user_id'] ?? User::factory()->create()->id;
        $start = now()->addMonth()->startOfDay();

        return Event::create(array_merge([
            'user_id' => $coordinatorId,
            'title' => 'Evento de teste',
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
            'capacity' => 4,
            'internal_capacity' => 2,
            'external_capacity' => 2,
            'description' => 'Descricao do evento de teste.',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'campus' => Event::FIXED_CAMPUS,
            'building' => Event::FIXED_BUILDING,
            'venue' => Event::FIXED_VENUE,
            'address' => Event::FIXED_ADDRESS,
            'image' => 'teste.png',
            'coordinator_name' => 'Coordenacao',
            'coordinator_email' => 'coord@example.com',
            'datetime_registration' => $start->copy()->setTime(9, 0),
            'accepts_submissions' => false,
        ], $overrides));
    }

    private function updatePayload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'title' => $event->title,
            'category' => $event->category,
            'modality' => $event->modality,
            'event_type' => $event->event_type ?: 'Congresso',
            'natureza' => $event->natureza ?: 'cientifico',
            'abrangencia' => $event->abrangencia ?: 'local',
            'periodicidade' => $event->periodicidade ?: 'unico',
            'capacity' => $event->capacity,
            'internal_capacity' => $event->internal_capacity,
            'external_capacity' => $event->external_capacity,
            'description' => $event->description,
            'start_date' => $event->start_date->format('Y-m-d'),
            'end_date' => $event->end_date->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '18:00',
            'campus' => Event::FIXED_CAMPUS,
            'building' => Event::FIXED_BUILDING,
            'venue' => Event::FIXED_VENUE,
            'address' => Event::FIXED_ADDRESS,
            'coordinator_name' => $event->coordinator_name,
            'coordinator_email' => $event->coordinator_email,
            'datetime_registration' => $event->datetime_registration->format('Y-m-d H:i:s'),
            'accepts_submissions' => 0,
        ], $overrides);
    }
}
