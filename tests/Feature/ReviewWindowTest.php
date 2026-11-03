<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkReviewer;
use App\Notifications\ReviewerAssigned;
use App\Notifications\WorkDecisionRecorded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReviewWindowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
    }

    public function test_create_form_asks_for_the_review_deadline(): void
    {
        $coordinator = User::factory()->create();

        $this->actingAs($coordinator)
            ->get('/events/create')
            ->assertOk()
            ->assertSee('Prazo de avaliação')
            ->assertSee('name="review_deadline_at"', false);
    }

    public function test_review_deadline_must_sit_between_submission_and_event_start(): void
    {
        $coordinator = User::factory()->create();
        $event = $this->makeEvent(['user_id' => $coordinator->id]);
        $start = $event->start_date->copy()->setTime(10, 0);
        $submission = $start->copy()->subDays(10);
        $review = $start->copy()->subDays(2);

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->payload($event, [
                'accepts_submissions' => 1,
                'submission_deadline_at' => $submission->format('Y-m-d H:i:s'),
                'accepted_work_types' => [Work::TYPE_RESUMO_SIMPLES],
                'reviewers_min_per_work' => 1,
                'reviewers_max_per_work' => 1,
            ]))
            ->assertSessionHasErrors('review_deadline_at');

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->payload($event, [
                'accepts_submissions' => 1,
                'submission_deadline_at' => $submission->format('Y-m-d H:i:s'),
                'review_deadline_at' => $submission->copy()->subHour()->format('Y-m-d H:i:s'),
                'accepted_work_types' => [Work::TYPE_RESUMO_SIMPLES],
                'reviewers_min_per_work' => 1,
                'reviewers_max_per_work' => 1,
            ]))
            ->assertSessionHasErrors('review_deadline_at');

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->payload($event, [
                'accepts_submissions' => 1,
                'submission_deadline_at' => $submission->format('Y-m-d H:i:s'),
                'review_deadline_at' => $start->format('Y-m-d H:i:s'),
                'accepted_work_types' => [Work::TYPE_RESUMO_SIMPLES],
                'reviewers_min_per_work' => 1,
                'reviewers_max_per_work' => 1,
            ]))
            ->assertSessionHasErrors('review_deadline_at');

        $this->actingAs($coordinator)
            ->put('/events/update/'.$event->id, $this->payload($event, [
                'accepts_submissions' => 1,
                'submission_deadline_at' => $submission->format('Y-m-d H:i:s'),
                'review_deadline_at' => $review->format('Y-m-d H:i:s'),
                'accepted_work_types' => [Work::TYPE_RESUMO_SIMPLES],
                'reviewers_min_per_work' => 1,
                'reviewers_max_per_work' => 1,
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $event->refresh();
        $this->assertTrue($event->accepts_submissions);
        $this->assertSame($review->format('Y-m-d H:i'), $event->review_deadline_at->format('Y-m-d H:i'));

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('Prazo de avaliação')
            ->assertSee($review->format('d/m/Y'));
    }

    public function test_explicit_review_deadline_closes_the_window_before_the_event_ends(): void
    {
        $open = $this->makeEvent([
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->subDay(),
            'review_deadline_at' => now()->addDays(3),
        ]);
        $this->assertTrue($open->reviewersEvaluationWindowOpen());

        $closed = $this->makeEvent([
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->subDays(4),
            'review_deadline_at' => now()->subHour(),
        ]);
        $this->assertFalse($closed->calendarEnded());
        $this->assertTrue($closed->reviewDeadlinePassed());
        $this->assertFalse($closed->reviewersEvaluationWindowOpen());
    }

    public function test_event_without_review_deadline_still_closes_when_the_calendar_ends(): void
    {
        $legacy = $this->makeEvent([
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->subDays(10),
            'start_date' => now()->subDays(3)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'datetime_registration' => now()->subDays(20),
        ]);

        $this->assertFalse($legacy->hasReviewDeadline());
        $this->assertTrue($legacy->calendarEnded());
        $this->assertFalse($legacy->reviewersEvaluationWindowOpen());
    }

    public function test_coordinator_can_desk_reject_only_before_reviewers_are_assigned(): void
    {
        $coordinator = User::factory()->create();
        $author = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->subDay(),
            'review_deadline_at' => now()->addDays(4),
        ]);
        $work = $this->makeWork($event, $author);

        $this->actingAs($coordinator)
            ->get(route('events.works.index', $event))
            ->assertOk()
            ->assertSee('Recusar na triagem');

        $this->actingAs($author)
            ->post(route('works.desk-reject', $work), ['author_feedback' => 'Fora de escopo'])
            ->assertForbidden();

        $this->actingAs($coordinator)
            ->post(route('works.desk-reject', $work), ['author_feedback' => '   '])
            ->assertSessionHasErrors('author_feedback');
        $this->assertSame(Work::STATUS_SUBMITTED, $work->fresh()->status);

        $this->actingAs($coordinator)
            ->post(route('works.desk-reject', $work), [
                'author_feedback' => 'O trabalho está fora do escopo do evento.',
            ])
            ->assertRedirect()
            ->assertSessionHas('msg');

        $work->refresh();
        $this->assertSame(Work::STATUS_DESK_REJECTED, $work->status);
        $this->assertSame('O trabalho está fora do escopo do evento.', $work->author_feedback);
        $this->assertSame($coordinator->id, (int) $work->decision_by);

        Notification::assertSentTo(
            $author,
            WorkDecisionRecorded::class,
            function (WorkDecisionRecorded $notification) use ($author, $event) {
                $mail = $notification->toMail($author);

                return $notification->outcome === WorkDecisionRecorded::DESK_REJECTED
                    && $mail->subject === 'Trabalho recusado na triagem: '.$event->title;
            }
        );

        $assigned = $this->makeWork($event, $author, ['work_type' => Work::TYPE_POSTER]);
        WorkReviewer::create([
            'work_id' => $assigned->id,
            'reviewer_user_id' => User::factory()->create(['role' => User::ROLE_REVIEWER])->id,
            'assigned_by' => $coordinator->id,
            'assigned_at' => now(),
            'status' => WorkReviewer::STATUS_ASSIGNED,
        ]);

        $this->actingAs($coordinator)
            ->post(route('works.desk-reject', $assigned), ['author_feedback' => 'Tarde demais'])
            ->assertSessionHas('msg');
        $this->assertSame(Work::STATUS_SUBMITTED, $assigned->fresh()->status);
    }

    public function test_distribution_stops_when_the_review_deadline_has_passed(): void
    {
        $coordinator = User::factory()->create();
        $author = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->subDays(3),
            'review_deadline_at' => now()->subHour(),
            'reviewers_min_per_work' => 1,
            'reviewers_max_per_work' => 1,
        ]);
        $work = $this->makeWork($event, $author);

        $this->actingAs($coordinator)
            ->post(route('events.works.reviewers.distribute', $event), [
                'work_ids' => [$work->id],
                'reviewer_ids' => [$reviewer->id],
                'reviewers_per_work' => 1,
            ])
            ->assertSessionHas('msg', 'A distribuição de avaliadores só é permitida dentro do prazo de avaliação.');

        $this->assertSame(0, WorkReviewer::query()->where('work_id', $work->id)->count());
        Notification::assertNothingSent();
    }

    public function test_distribution_assigns_reviewers_inside_the_review_window(): void
    {
        $coordinator = User::factory()->create();
        $author = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $event = $this->makeEvent([
            'user_id' => $coordinator->id,
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->subDay(),
            'review_deadline_at' => now()->addDays(4),
            'reviewers_min_per_work' => 1,
            'reviewers_max_per_work' => 1,
        ]);
        $work = $this->makeWork($event, $author);

        $this->actingAs($coordinator)
            ->post(route('events.works.reviewers.distribute', $event), [
                'work_ids' => [$work->id],
                'reviewer_ids' => [$reviewer->id],
                'reviewers_per_work' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(Work::STATUS_UNDER_REVIEW, $work->fresh()->status);
        $this->assertDatabaseHas('work_reviewers', [
            'work_id' => $work->id,
            'reviewer_user_id' => $reviewer->id,
        ]);
        Notification::assertSentTo($reviewer, ReviewerAssigned::class);
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
        ], $overrides));
    }

    private function payload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'title' => $event->title,
            'category' => $event->category,
            'modality' => $event->modality,
            'event_type' => $event->event_type,
            'natureza' => 'cientifico',
            'abrangencia' => 'nacional',
            'periodicidade' => 'unico',
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

    private function makeWork(Event $event, User $author, array $overrides = []): Work
    {
        return Work::create(array_merge([
            'event_id' => $event->id,
            'submitter_user_id' => $author->id,
            'title' => null,
            'abstract' => null,
            'work_type' => Work::TYPE_RESUMO_SIMPLES,
            'file_path' => 'arquivo.pdf',
            'status' => Work::STATUS_SUBMITTED,
        ], $overrides));
    }
}
