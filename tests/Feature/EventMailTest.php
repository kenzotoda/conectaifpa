<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventPresence;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkPresentation;
use App\Models\WorkReviewer;
use App\Notifications\CertificateAvailable;
use App\Notifications\EventRegistrationConfirmed;
use App\Notifications\PresentationScheduled;
use App\Notifications\ReviewerAssigned;
use App\Notifications\WorkDecisionRecorded;
use App\Notifications\WorkSubmitted;
use App\Services\CertificateIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
    }

    public function test_mail_example_points_at_resend_without_secrets(): void
    {
        $example = file_get_contents(base_path('.env.example'));

        $this->assertIsString($example);
        $this->assertStringContainsString('MAIL_MAILER=resend', $example);
        $this->assertStringContainsString('MAIL_FROM_ADDRESS=nao-responda@conectaifpa.com.br', $example);
        $this->assertStringContainsString('MAIL_FROM_NAME="ConectaIFPA"', $example);
        $this->assertDoesNotMatchRegularExpression('/^RESEND_API_KEY=\S+/m', $example);
        $this->assertStringNotContainsString('smtp.gmail.com', $example);
        $this->assertStringNotContainsString('MAIL_PASSWORD', $example);
    }

    public function test_registration_email_goes_only_to_a_new_participant(): void
    {
        $event = $this->makeEvent();
        $participant = $this->makeParticipant();

        $this->actingAs($participant)
            ->post('/events/join/'.$event->id)
            ->assertRedirect('/dashboard');

        Notification::assertSentTo(
            $participant,
            EventRegistrationConfirmed::class,
            function (EventRegistrationConfirmed $notification) use ($participant, $event) {
                $mail = $notification->toMail($participant);

                return $mail->subject === 'Inscrição confirmada: '.$event->title
                    && $this->mailContains($mail->introLines, $event->title)
                    && $this->mailContains($mail->introLines, 'Presencial');
            }
        );
        Mail::assertNothingSent();

        $this->actingAs($participant)->post('/events/join/'.$event->id);

        Notification::assertSentToTimes($participant, EventRegistrationConfirmed::class, 1);
    }

    public function test_coordinator_registration_does_not_send_email(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent(['user_id' => $coordinator->id]);

        $this->actingAs($coordinator)->post('/events/join/'.$event->id)->assertRedirect();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_submission_email_confirms_the_work_type(): void
    {
        config([
            'services.supabase.url' => 'https://supabase.test',
            'services.supabase.service_role' => 'test-key',
            'services.supabase.bucket_works' => 'works-test',
        ]);
        Http::fake([
            'https://supabase.test/*' => Http::response(['ok' => true], 200),
        ]);

        $event = $this->makeEvent([
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->addDays(5),
        ]);
        $participant = $this->makeParticipant();
        $event->users()->attach($participant->id);

        $this->actingAs($participant)->post('/events/'.$event->id.'/works', [
            'work_type' => Work::TYPE_RESUMO_SIMPLES,
            'file' => UploadedFile::fake()->createWithContent('trabalho.pdf', '%PDF-1.4 teste'),
        ])->assertRedirect(route('works.my'));

        Notification::assertSentTo(
            $participant,
            WorkSubmitted::class,
            function (WorkSubmitted $notification) use ($participant, $event) {
                $mail = $notification->toMail($participant);

                return $mail->subject === 'Submissão recebida: '.$event->title
                    && $this->mailContains($mail->introLines, 'Resumo Simples');
            }
        );
        Mail::assertNothingSent();
    }

    public function test_reviewer_is_notified_only_on_the_first_assignment(): void
    {
        $coordinator = User::factory()->create();
        $participant = $this->makeParticipant();
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->subDay(),
            'reviewers_min_per_work' => 1,
            'reviewers_max_per_work' => 1,
        ]);
        $work = $this->makeWork($event, $participant);

        $payload = [
            'work_ids' => [$work->id],
            'reviewer_ids' => [$reviewer->id],
            'reviewers_per_work' => 1,
        ];

        $this->actingAs($coordinator)
            ->post(route('events.works.reviewers.distribute', $event), $payload)
            ->assertRedirect();

        Notification::assertSentTo(
            $reviewer,
            ReviewerAssigned::class,
            function (ReviewerAssigned $notification) use ($reviewer, $event, $participant) {
                $mail = $notification->toMail($reviewer);
                $body = implode(' ', array_map('strval', $mail->introLines));

                return $mail->subject === 'Trabalho para avaliar: '.$event->title
                    && ! str_contains($body, $participant->name);
            }
        );

        $this->actingAs($coordinator)
            ->post(route('events.works.reviewers.distribute', $event), $payload)
            ->assertRedirect();

        Notification::assertSentToTimes($reviewer, ReviewerAssigned::class, 1);
        Mail::assertNothingSent();
    }

    public function test_decision_email_describes_rejection_and_requested_corrections(): void
    {
        $coordinator = User::factory()->create();
        $participant = $this->makeParticipant();
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->subDay(),
        ]);
        $work = $this->makeWork($event, $participant, ['title' => 'Energia solar no campus']);
        WorkReviewer::create([
            'work_id' => $work->id,
            'reviewer_user_id' => $reviewer->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
            'status' => WorkReviewer::STATUS_COMPLETED,
        ]);

        $this->actingAs($coordinator)->post(route('works.decision', $work), [
            'status' => Work::STATUS_REJECTED,
            'coordinator_final_score' => '4.5',
            'author_feedback' => 'Fora do escopo do evento.',
        ])->assertRedirect();

        Notification::assertSentTo(
            $participant,
            WorkDecisionRecorded::class,
            function (WorkDecisionRecorded $notification) use ($participant, $event) {
                $mail = $notification->toMail($participant);

                return $notification->outcome === WorkDecisionRecorded::REJECTED
                    && $mail->subject === 'Trabalho não aceito: '.$event->title
                    && $this->mailContains($mail->introLines, 'Fora do escopo do evento.');
            }
        );

        $work->refresh();
        $work->status = Work::STATUS_UNDER_REVIEW;
        $work->coordinator_feedback_file_path = 'retorno.pdf';
        $work->save();

        Notification::fake();

        $deadline = now()->addDays(3)->seconds(0);
        $this->actingAs($coordinator)->post(route('works.decision', $work), [
            'status' => Work::STATUS_ACCEPTED_WITH_CORRECTIONS,
            'coordinator_final_score' => '8',
            'author_feedback' => 'Ajustar a metodologia.',
            'correction_deadline_at' => $deadline->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        Notification::assertSentTo(
            $participant,
            WorkDecisionRecorded::class,
            function (WorkDecisionRecorded $notification) use ($participant, $deadline) {
                $mail = $notification->toMail($participant);

                return $notification->outcome === WorkDecisionRecorded::CORRECTIONS
                    && str_contains($mail->subject, 'Correção solicitada')
                    && $this->mailContains($mail->introLines, $deadline->timezone(config('app.timezone'))->format('d/m/Y H:i'))
                    && $this->mailContains($mail->introLines, 'Ajustar a metodologia.');
            }
        );
        Mail::assertNothingSent();
    }

    public function test_presentation_schedule_email_includes_place_and_time(): void
    {
        $coordinator = User::factory()->create();
        $participant = $this->makeParticipant();
        $event = $this->makeEvent(['user_id' => $coordinator->id]);
        $work = $this->makeWork($event, $participant, [
            'title' => 'Painel de resíduos',
            'status' => Work::STATUS_FINAL_VALIDATED,
        ]);
        $day = $event->start_date->format('Y-m-d');

        $this->actingAs($coordinator)->post(route('works.presentation.upsert', $work), [
            'presentation_type' => WorkPresentation::TYPE_ORAL,
            'scheduled_start' => $day.' 11:00',
            'scheduled_end' => $day.' 12:00',
            'location' => 'Auditório 1',
        ])->assertRedirect();

        Notification::assertSentTo(
            $participant,
            PresentationScheduled::class,
            function (PresentationScheduled $notification) use ($participant, $event) {
                $mail = $notification->toMail($participant);

                return $mail->subject === 'Apresentação agendada: '.$event->title
                    && $this->mailContains($mail->introLines, 'Auditório 1')
                    && $this->mailContains($mail->introLines, '11:00')
                    && $this->mailContains($mail->introLines, 'Oral');
            }
        );
        Mail::assertNothingSent();

        $this->actingAs($coordinator)->post(route('works.presentation.upsert', $work), [
            'presentation_type' => WorkPresentation::TYPE_ORAL,
            'scheduled_start' => $day.' 11:00',
            'scheduled_end' => $day.' 12:00',
            'location' => 'Auditório 1',
        ])->assertRedirect();

        Notification::assertSentToTimes($participant, PresentationScheduled::class, 1);
    }

    public function test_new_participation_certificate_sends_one_email(): void
    {
        config([
            'services.supabase.url' => null,
            'services.supabase.service_role' => null,
        ]);
        Storage::fake('public');

        $participant = $this->makeParticipant();
        $event = $this->makeEvent([
            'finalized_at' => now(),
            'certificate_total_hours' => 8,
        ]);
        EventPresence::create([
            'user_id' => $participant->id,
            'event_id' => $event->id,
            'presente' => true,
        ]);

        $issuer = app(CertificateIssuer::class);

        $this->assertTrue($issuer->issueParticipation($participant, $event));
        $this->assertFalse($issuer->issueParticipation($participant, $event->fresh()));

        Notification::assertSentToTimes($participant, CertificateAvailable::class, 1);
        Notification::assertSentTo(
            $participant,
            CertificateAvailable::class,
            function (CertificateAvailable $notification) use ($participant, $event) {
                $mail = $notification->toMail($participant);

                return $mail->subject === 'Certificado disponível: '.$event->title
                    && $this->mailContains($mail->introLines, 'Participação no evento')
                    && $this->mailContains($mail->introLines, $notification->certificate->codigo_validacao);
            }
        );
        Mail::assertNothingSent();
    }

    /**
     * @param  list<mixed>  $lines
     */
    private function mailContains(array $lines, string $needle): bool
    {
        return str_contains(implode(' ', array_map('strval', $lines)), $needle);
    }

    private function makeParticipant(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'is_external_participant' => false,
            'participant_profile' => User::PROFILE_DISCENTE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeWork(Event $event, User $participant, array $overrides = []): Work
    {
        return Work::create(array_merge([
            'event_id' => $event->id,
            'submitter_user_id' => $participant->id,
            'title' => null,
            'abstract' => null,
            'work_type' => Work::TYPE_RESUMO_SIMPLES,
            'file_path' => 'arquivo.pdf',
            'status' => Work::STATUS_SUBMITTED,
        ], $overrides));
    }
}
