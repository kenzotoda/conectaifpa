<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSatisfactionResponse;
use App\Models\User;
use App\Notifications\SatisfactionSurveyAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SatisfactionSurveyTest extends TestCase
{
    use RefreshDatabase;

    public function test_survey_opens_only_after_the_event_is_closed(): void
    {
        [$event, , $participant] = $this->closedEvent(finalized: false);

        $this->actingAs($participant)
            ->get(route('events.satisfaction.form', $event))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('msg', 'A pesquisa abre depois que o evento é encerrado.');
    }

    public function test_enrolled_participant_answers_once_and_coordinator_sees_averages_without_names(): void
    {
        [$event, $coordinator, $participant] = $this->closedEvent();
        $other = User::factory()->create(['role' => User::ROLE_PARTICIPANT, 'name' => 'Outra Pessoa']);
        $event->users()->attach($other->id);

        $this->actingAs($participant)
            ->post(route('events.satisfaction.store', $event), [
                'organization_score' => 4,
                'content_score' => 4,
                'environment_score' => 2,
                'overall_score' => 4,
                'comment' => 'A sala estava boa.',
            ])
            ->assertRedirect(route('events.satisfaction.form', $event));

        $this->actingAs($other)
            ->post(route('events.satisfaction.store', $event), [
                'organization_score' => 2,
                'content_score' => 2,
                'environment_score' => 2,
                'overall_score' => 2,
            ])
            ->assertRedirect();

        $this->actingAs($participant)
            ->post(route('events.satisfaction.store', $event), [
                'organization_score' => 1,
                'content_score' => 1,
                'environment_score' => 1,
                'overall_score' => 1,
            ])
            ->assertRedirect(route('events.satisfaction.form', $event));

        $this->assertSame(1, EventSatisfactionResponse::query()->where('user_id', $participant->id)->count());

        $this->actingAs($coordinator)
            ->get(route('events.satisfaction.results', $event))
            ->assertOk()
            ->assertSee('3,0')
            ->assertSee('A sala estava boa.')
            ->assertDontSee('Ana Participante')
            ->assertDontSee('Outra Pessoa');
    }

    public function test_someone_outside_the_event_cannot_answer(): void
    {
        [$event] = $this->closedEvent();
        $outsider = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);

        $this->actingAs($outsider)
            ->get(route('events.satisfaction.form', $event))
            ->assertForbidden();
    }

    public function test_coordinator_sends_the_invite_once(): void
    {
        Notification::fake();
        [$event, $coordinator, $participant] = $this->closedEvent();

        $this->actingAs($coordinator)
            ->post(route('events.satisfaction.invite', $event))
            ->assertRedirect();

        $this->actingAs($coordinator)
            ->post(route('events.satisfaction.invite', $event))
            ->assertRedirect()
            ->assertSessionHas('msg', 'O convite desta pesquisa já foi enviado.');

        Notification::assertSentToTimes($participant, SatisfactionSurveyAvailable::class, 1);
    }

    /**
     * @return array{0: Event, 1: User, 2: User}
     */
    private function closedEvent(bool $finalized = true): array
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $participant = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'name' => 'Ana Participante',
        ]);
        $start = now()->subDays(3)->startOfDay();

        $event = Event::create([
            'user_id' => $coordinator->id,
            'title' => 'Semana encerrada',
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
            'natureza' => 'cientifico',
            'abrangencia' => 'nacional',
            'periodicidade' => 'unico',
            'acesso' => Event::ACCESS_FREE,
            'capacity' => 20,
            'internal_capacity' => 10,
            'external_capacity' => 10,
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
            'datetime_registration' => $start->copy()->subDay(),
            'accepts_submissions' => false,
            'finalized_at' => $finalized ? now() : null,
        ]);
        $event->users()->attach($participant->id);

        return [$event, $coordinator, $participant];
    }
}
