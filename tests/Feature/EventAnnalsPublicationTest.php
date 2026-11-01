<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EventAnnalsPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_coordinator_annals_page_no_longer_exists(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent(['user_id' => $coordinator->id, 'finalized_at' => now()]);

        $this->actingAs($coordinator)
            ->get('/events/'.$event->id.'/anais/coordenacao')
            ->assertNotFound();
    }

    public function test_upload_is_blocked_before_the_event_is_finalized(): void
    {
        Http::fake();
        $coordinator = User::factory()->create();
        $event = $this->makeEvent(['user_id' => $coordinator->id]);

        $this->actingAs($coordinator)
            ->from('/dashboard')
            ->post(route('events.annals.store', $event->id), [
                'annals_kind' => Event::ANNALS_KIND_ANAIS,
                'file' => UploadedFile::fake()->create('anais.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('msg', 'Publique os anais ou o e-book somente depois de finalizar o evento.');

        $this->assertFalse($event->fresh()->hasAnnalsPublication());
        Http::assertNothingSent();
    }

    public function test_coordinator_publishes_a_pdf_and_anyone_can_download_it(): void
    {
        Http::fake([
            '*' => Http::response('%PDF-1.4 fake', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $coordinator = User::factory()->create();
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'finalized_at' => now(),
        ]);

        $this->actingAs($coordinator)
            ->from('/dashboard')
            ->post(route('events.annals.store', $event->id), [
                'annals_kind' => Event::ANNALS_KIND_EBOOK,
                'file' => UploadedFile::fake()->create('ebook.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('msg');

        $event->refresh();
        $this->assertTrue($event->hasAnnalsPublication());
        $this->assertSame(Event::ANNALS_KIND_EBOOK, $event->annals_kind);
        $this->assertSame('ebook.pdf', $event->annals_file_name);

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Baixar E-book')
            ->assertDontSee('300 internas restantes');

        $this->get(route('events.annals', $event->id))
            ->assertOk()
            ->assertSee('E-book')
            ->assertSee('Baixar PDF');

        $this->get(route('events.annals.download', $event->id))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="ebook.pdf"');
    }

    public function test_visitor_does_not_see_download_before_publication(): void
    {
        $event = $this->makeEvent(['finalized_at' => now()]);

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertDontSee('Baixar Anais')
            ->assertDontSee('Baixar E-book');

        $this->get(route('events.annals.download', $event->id))->assertNotFound();
    }

    private function makeEvent(array $overrides = []): Event
    {
        $coordinatorId = $overrides['user_id'] ?? User::factory()->create()->id;
        $start = now()->addMonth()->startOfDay();

        return Event::create(array_merge([
            'user_id' => $coordinatorId,
            'title' => 'Evento de anais',
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
            'capacity' => 10,
            'internal_capacity' => 5,
            'external_capacity' => 5,
            'description' => 'Descricao.',
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
}
