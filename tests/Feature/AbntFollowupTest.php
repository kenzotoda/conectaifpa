<?php

namespace Tests\Feature;

use App\Mail\OutboundMailPace;
use App\Models\Activity;
use App\Models\ActivityPresence;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventPresence;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkAuthor;
use App\Notifications\EventNewsPublished;
use App\Notifications\VerifyEmailQueued;
use App\Services\AbntWorkReference;
use App\Services\CertificateIssuer;
use App\Services\SupabaseObjectClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AbntFollowupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->mock(SupabaseObjectClient::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(false);
        });
    }

    public function test_annals_reference_appears_only_after_the_pdf_is_published(): void
    {
        [, , $work] = $this->publishedWork();

        $this->assertNull(AbntWorkReference::forWork($work));

        $work->event->update([
            'annals_kind' => 'anais',
            'annals_file_name' => 'anais.pdf',
            'annals_storage_path' => 'anais/anais.pdf',
            'annals_uploaded_at' => now(),
        ]);

        $reference = AbntWorkReference::forWork($work->fresh());
        $this->assertNotNull($reference);
        $this->assertStringContainsString('DISCENTE, Ana', $reference);
        $this->assertStringContainsString('Anais', $reference);
        $this->assertSame(0, Certificate::query()->count());
    }

    public function test_participation_rewrite_keeps_the_validation_code_and_does_not_email_again(): void
    {
        Notification::fake();
        [$event, $coordinator] = $this->readyEvent();
        $participant = User::factory()->create(['role' => User::ROLE_PARTICIPANT, 'name' => 'Ana Discente']);
        $event->users()->attach($participant->id);
        EventPresence::create([
            'event_id' => $event->id,
            'user_id' => $participant->id,
            'presente' => true,
            'presente_presencial' => true,
            'marcado_por' => $coordinator->id,
            'data_marcacao' => now(),
        ]);

        $issuer = app(CertificateIssuer::class);
        $this->assertTrue($issuer->issueParticipation($participant, $event));
        $certificate = Certificate::query()->where('tipo', Certificate::TYPE_PARTICIPATION)->first();
        $code = $certificate->codigo_validacao;
        Notification::assertSentToTimes($participant, \App\Notifications\CertificateAvailable::class, 1);

        $event->update(['certificate_total_hours' => 16]);
        $this->assertTrue($issuer->issueParticipation($participant->fresh(), $event->fresh()));
        $this->assertSame($code, $certificate->fresh()->codigo_validacao);
        Notification::assertSentToTimes($participant, \App\Notifications\CertificateAvailable::class, 1);
    }

    public function test_activity_certificate_requires_recorded_presence(): void
    {
        [$event] = $this->readyEvent();
        $participant = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);
        $event->users()->attach($participant->id);
        $activity = Activity::create([
            'event_id' => $event->id,
            'title' => 'Oficina',
            'type' => Activity::TYPE_WORKSHOP,
            'start_at' => now()->subDay(),
            'end_at' => now()->subDay()->addHour(),
            'location' => 'Sala 1',
            'workload_hours' => 8,
        ]);
        $presence = ActivityPresence::create([
            'activity_id' => $activity->id,
            'user_id' => $participant->id,
            'presente' => false,
            'data_marcacao' => now(),
        ]);

        $issuer = app(CertificateIssuer::class);
        $this->assertFalse($issuer->issueActivity($participant, $activity->fresh(), $event));

        $presence->update(['presente' => true]);
        $this->assertTrue($issuer->issueActivity($participant, $activity->fresh(), $event));
        $this->assertSame(1, Certificate::query()->where('tipo', Certificate::TYPE_ACTIVITY)->count());
    }

    public function test_activity_presence_saves_the_whole_list_in_one_request(): void
    {
        [$event, $coordinator] = $this->readyEvent();
        $present = User::factory()->create(['role' => User::ROLE_PARTICIPANT, 'name' => 'Ana Presente']);
        $absent = User::factory()->create(['role' => User::ROLE_PARTICIPANT, 'name' => 'Bruno Ausente']);
        $outsider = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);
        $event->users()->attach([$present->id, $absent->id]);
        $activity = Activity::create([
            'event_id' => $event->id,
            'title' => 'Oficina',
            'type' => Activity::TYPE_WORKSHOP,
            'start_at' => now()->subDay(),
            'end_at' => now()->subDay()->addHour(),
            'location' => 'Sala 1',
            'workload_hours' => 8,
        ]);

        $this->actingAs($coordinator)
            ->get(route('events.certificates.activity.presence', [$event, $activity]))
            ->assertOk()
            ->assertSee('Ana Presente')
            ->assertSee('Bruno Ausente')
            ->assertDontSee('Depois de');

        $this->actingAs($coordinator)
            ->post(route('events.certificates.activity.presence.update', [$event, $activity]), [
                'presentes' => $present->id.','.$outsider->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('presence_msg', 'Presença salva.');

        $this->assertTrue((bool) ActivityPresence::query()->where('activity_id', $activity->id)->where('user_id', $present->id)->value('presente'));
        $this->assertFalse((bool) ActivityPresence::query()->where('activity_id', $activity->id)->where('user_id', $absent->id)->value('presente'));
        $this->assertNull(ActivityPresence::query()->where('activity_id', $activity->id)->where('user_id', $outsider->id)->first());
        $this->assertNotNull(ActivityPresence::query()->where('activity_id', $activity->id)->where('user_id', $absent->id)->value('data_marcacao'));
    }

    public function test_hybrid_presence_records_both_channels(): void
    {
        [$event, $coordinator] = $this->readyEvent(['modality' => 'Híbrido']);
        $participant = User::factory()->create(['role' => User::ROLE_PARTICIPANT, 'name' => 'Paula Híbrida']);
        $event->users()->attach($participant->id);

        $this->actingAs($coordinator)
            ->post(route('events.certificates.presence', $event), [
                'presente_presencial' => [$participant->id],
                'presente_online' => [$participant->id],
            ])
            ->assertRedirect();

        $presence = EventPresence::query()->where('user_id', $participant->id)->first();
        $this->assertTrue($presence->presente);
        $this->assertTrue($presence->presente_presencial);
        $this->assertTrue($presence->presente_online);

        $this->assertTrue(app(CertificateIssuer::class)->issueParticipation($participant, $event->fresh()));
        $certificate = Certificate::query()->first();
        $sheet = \App\Services\CertificateDocument::forCertificate($certificate);
        $this->assertSame('Presença presencial e online registrada', $sheet['presence']);
        $this->assertStringContainsString('(participação online)', $sheet['place']);
    }

    public function test_outbound_mail_pace_is_shared_by_news_and_verification(): void
    {
        $this->assertSame(2, OutboundMailPace::PER_SECOND);
        $this->assertContains(\Illuminate\Contracts\Queue\ShouldQueue::class, class_implements(EventNewsPublished::class));
        $this->assertContains(\Illuminate\Contracts\Queue\ShouldQueue::class, class_implements(VerifyEmailQueued::class));

        Notification::fake();
        [$event, $coordinator] = $this->readyEvent(['finalized_at' => null]);
        $participant = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);
        $event->users()->attach($participant->id);

        $this->actingAs($coordinator)
            ->post('/events/'.$event->id.'/novidades', [
                'title' => 'Nova palestra',
                'content' => 'A programação ganhou uma palestra.',
            ])
            ->assertRedirect();

        Notification::assertSentTo($participant, EventNewsPublished::class);
        Notification::assertNotSentTo($coordinator, EventNewsPublished::class);
    }

    public function test_unverified_user_is_sent_to_the_verification_notice(): void
    {
        $user = User::factory()->unverified()->create(['role' => User::ROLE_PARTICIPANT]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));

        $user->markEmailAsVerified();

        $this->actingAs($user->fresh())
            ->get(route('dashboard'))
            ->assertOk();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: Event, 1: User}
     */
    private function readyEvent(array $overrides = []): array
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $start = now()->subDays(3)->startOfDay();

        $event = Event::create(array_merge([
            'user_id' => $coordinator->id,
            'title' => 'Semana de Tecnologia',
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
            'certificate_organizer' => 'Coordenação do evento',
            'certificate_institution' => 'IFPA Campus Belém',
            'certificate_total_hours' => 8,
            'finalized_at' => now(),
        ], $overrides));

        return [$event, $coordinator];
    }

    /**
     * @return array{0: Event, 1: User, 2: Work}
     */
    private function publishedWork(): array
    {
        [$event, $coordinator] = $this->readyEvent(['accepts_submissions' => true]);
        $author = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'name' => 'Ana Discente',
        ]);
        $work = Work::create([
            'event_id' => $event->id,
            'submitter_user_id' => $author->id,
            'title' => 'Educação e tecnologia',
            'abstract' => null,
            'work_type' => Work::TYPE_RESUMO_SIMPLES,
            'file_path' => 'arquivo.pdf',
            'status' => Work::STATUS_PRESENTED,
            'certificate_presentation_title' => 'Educação e tecnologia',
        ]);
        WorkAuthor::create([
            'work_id' => $work->id,
            'user_id' => $author->id,
            'author_name' => 'Ana Discente',
            'is_main_author' => true,
            'author_order' => 1,
        ]);

        return [$event, $author, $work];
    }
}
