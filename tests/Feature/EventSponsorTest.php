<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSponsorTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_a_sponsor_and_the_public_page_lists_it(): void
    {
        [$event, $coordinator] = $this->eventForCoordinator();

        $this->actingAs($coordinator)
            ->post('/events/'.$event->id.'/patrocinadores', [
                'name' => 'IFPA Belém',
                'website' => 'https://belem.ifpa.edu.br',
            ])
            ->assertRedirect('/events/'.$event->id.'/patrocinadores');

        $this->assertDatabaseHas('event_sponsors', [
            'event_id' => $event->id,
            'name' => 'IFPA Belém',
            'website' => 'https://belem.ifpa.edu.br',
        ]);

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Patrocinadores')
            ->assertSee('IFPA Belém')
            ->assertSee('https://belem.ifpa.edu.br', false);
    }

    public function test_public_page_hides_the_section_when_there_are_no_sponsors(): void
    {
        [$event] = $this->eventForCoordinator();

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertDontSee('>Patrocinadores<', false);
    }

    public function test_website_is_optional_and_must_be_http_when_present(): void
    {
        [$event, $coordinator] = $this->eventForCoordinator();

        $this->actingAs($coordinator)
            ->post('/events/'.$event->id.'/patrocinadores', [
                'name' => 'Apoio local',
            ])
            ->assertRedirect('/events/'.$event->id.'/patrocinadores');

        $this->assertDatabaseHas('event_sponsors', [
            'event_id' => $event->id,
            'name' => 'Apoio local',
            'website' => null,
        ]);

        $this->actingAs($coordinator)
            ->from('/events/'.$event->id.'/patrocinadores')
            ->post('/events/'.$event->id.'/patrocinadores', [
                'name' => 'Site inválido',
                'website' => 'javascript:alert(1)',
            ])
            ->assertRedirect('/events/'.$event->id.'/patrocinadores')
            ->assertSessionHasErrors('website');

        $this->assertDatabaseMissing('event_sponsors', [
            'name' => 'Site inválido',
        ]);
    }

    public function test_owner_can_remove_a_sponsor(): void
    {
        [$event, $coordinator] = $this->eventForCoordinator();
        $sponsor = EventSponsor::create([
            'event_id' => $event->id,
            'name' => 'Parceiro',
        ]);

        $this->actingAs($coordinator)
            ->delete('/events/'.$event->id.'/patrocinadores/'.$sponsor->id)
            ->assertRedirect('/events/'.$event->id.'/patrocinadores');

        $this->assertDatabaseMissing('event_sponsors', ['id' => $sponsor->id]);
    }

    public function test_another_coordinator_and_a_participant_cannot_manage_sponsors(): void
    {
        [$event] = $this->eventForCoordinator();
        $other = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $participant = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);

        $this->actingAs($other)
            ->get('/events/'.$event->id.'/patrocinadores')
            ->assertRedirect('/dashboard');

        $this->actingAs($other)
            ->post('/events/'.$event->id.'/patrocinadores', ['name' => 'Intruso'])
            ->assertRedirect('/dashboard');

        $this->actingAs($participant)
            ->get('/events/'.$event->id.'/patrocinadores')
            ->assertForbidden();

        $this->assertDatabaseCount('event_sponsors', 0);
    }

    public function test_finalized_event_rejects_sponsor_changes(): void
    {
        [$event, $coordinator] = $this->eventForCoordinator();
        $event->update(['finalized_at' => now()]);

        $this->actingAs($coordinator)
            ->post('/events/'.$event->id.'/patrocinadores', ['name' => 'Tarde demais'])
            ->assertRedirect('/dashboard');

        $this->assertDatabaseCount('event_sponsors', 0);
    }

    /**
     * @return array{0: Event, 1: User}
     */
    private function eventForCoordinator(): array
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $start = now()->addMonth()->startOfDay();

        $event = Event::create([
            'user_id' => $coordinator->id,
            'title' => 'Semana com patrocínio',
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
            'datetime_registration' => $start->copy()->setTime(9, 0),
            'accepts_submissions' => false,
        ]);

        return [$event, $coordinator];
    }
}
