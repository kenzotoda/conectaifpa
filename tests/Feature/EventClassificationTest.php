<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_asks_for_classification_and_keeps_access_free(): void
    {
        $coordinator = User::factory()->create();

        $this->actingAs($coordinator)
            ->get('/events/create')
            ->assertOk()
            ->assertSee('Natureza')
            ->assertSee('Abrangência')
            ->assertSee('Periodicidade')
            ->assertSee('O acesso é gratuito')
            ->assertDontSee('value="pago"', false);
    }

    public function test_update_requires_classification_and_persists_edition(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent(['user_id' => $coordinator->id]);

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->payload($event))
            ->assertSessionHasErrors('natureza');

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->payload($event, [
                'natureza' => 'cientifico',
                'abrangencia' => 'nacional',
                'periodicidade' => 'regular',
            ]))
            ->assertSessionHasErrors('edicao');

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->payload($event, [
                'natureza' => 'cientifico',
                'abrangencia' => 'nacional',
                'periodicidade' => 'regular',
                'edicao' => 2,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $event->refresh();
        $this->assertSame('cientifico', $event->natureza);
        $this->assertSame('nacional', $event->abrangencia);
        $this->assertSame('regular', $event->periodicidade);
        $this->assertSame(2, (int) $event->edicao);
        $this->assertSame(Event::ACCESS_FREE, $event->acesso);

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Científico')
            ->assertSee('Nacional')
            ->assertSee('Regular, edição 2')
            ->assertSee('Gratuito');
    }

    public function test_unique_periodicity_discards_edition(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'periodicidade' => 'regular',
            'edicao' => 4,
        ]);

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->payload($event, [
                'natureza' => 'educativo',
                'abrangencia' => 'local',
                'periodicidade' => 'unico',
                'edicao' => 4,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull($event->fresh()->edicao);
    }

    public function test_porte_follows_capacity_bands(): void
    {
        $this->assertSame('Microevento', $this->eventWithSeats(50, 50)->porteLabel());
        $this->assertSame('Pequeno porte', $this->eventWithSeats(50, 51)->porteLabel());
        $this->assertSame('Pequeno porte', $this->eventWithSeats(250, 250)->porteLabel());
        $this->assertSame('Médio porte', $this->eventWithSeats(250, 251)->porteLabel());
        $this->assertSame('Médio porte', $this->eventWithSeats(2500, 2500)->porteLabel());
        $this->assertSame('Grande porte', $this->eventWithSeats(2500, 2501)->porteLabel());
        $this->assertSame('Grande porte', $this->eventWithSeats(20000, 20000)->porteLabel());
        $this->assertSame('Megaevento', $this->eventWithSeats(20000, 20001)->porteLabel());
    }

    private function eventWithSeats(int $internal, int $external): Event
    {
        return $this->makeEvent([
            'capacity' => $internal + $external,
            'internal_capacity' => $internal,
            'external_capacity' => $external,
        ]);
    }

    private function makeEvent(array $overrides = []): Event
    {
        $coordinatorId = $overrides['user_id'] ?? User::factory()->create()->id;
        $start = now()->addMonth()->startOfDay();

        return Event::create(array_merge([
            'user_id' => $coordinatorId,
            'title' => 'Semana de Tecnologia',
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
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
            'datetime_registration' => $start->copy()->setTime(9, 0),
            'accepts_submissions' => false,
        ], $overrides));
    }

    private function payload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'title' => $event->title,
            'category' => $event->category,
            'modality' => $event->modality,
            'event_type' => $event->event_type,
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
