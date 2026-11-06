<?php

namespace Tests\Unit;

use App\Models\Event;
use Carbon\Carbon;
use Tests\TestCase;

class EventScheduleTest extends TestCase
{
    public function test_incomplete_schedule_does_not_start_or_end_the_event(): void
    {
        $event = new Event;
        $event->start_date = '2020-01-01';
        $event->end_date = '2020-01-02';
        $event->start_time = null;
        $event->end_time = null;

        $this->assertFalse($event->scheduleIsComplete());
        $this->assertFalse($event->calendarStarted());
        $this->assertFalse($event->calendarEnded());
    }

    public function test_complete_schedule_uses_the_informed_times(): void
    {
        $past = new Event;
        $past->start_date = '2020-01-01';
        $past->end_date = '2020-01-01';
        $past->start_time = '08:00:00';
        $past->end_time = '12:00:00';

        $this->assertTrue($past->scheduleIsComplete());
        $this->assertTrue($past->calendarStarted());
        $this->assertTrue($past->calendarEnded());

        $future = new Event;
        $future->start_date = '2099-01-01';
        $future->end_date = '2099-01-02';
        $future->start_time = '18:00';
        $future->end_time = '10:00';

        $this->assertTrue($future->scheduleIsComplete());
        $this->assertFalse($future->calendarStarted());
        $this->assertFalse($future->calendarEnded());
        $this->assertTrue($future->calendarEndAt()->greaterThan($future->calendarStartAt()));
    }

    public function test_deadline_limit_uses_end_of_start_day_when_start_time_is_missing(): void
    {
        $limit = Event::deadlineLimitFromSchedule('2026-11-10', null);

        $this->assertSame('2026-11-10 23:59:59', $limit->format('Y-m-d H:i:s'));
    }

    public function test_deadline_limit_uses_start_instant_when_start_time_exists(): void
    {
        $limit = Event::deadlineLimitFromSchedule('2026-11-10', '09:30');

        $this->assertSame('2026-11-10 09:30:00', $limit->format('Y-m-d H:i:s'));
    }

    public function test_multi_day_event_may_end_earlier_on_the_clock_than_it_starts(): void
    {
        $this->assertFalse(Event::eventEndIsBeforeStart('2026-11-01', '18:00', '2026-11-02', '10:00'));
        $this->assertTrue(Event::eventEndIsBeforeStart('2026-11-01', '18:00', '2026-11-01', '10:00'));
        $this->assertFalse(Event::eventEndIsBeforeStart('2026-11-01', '18:00', '2026-11-01', '18:00'));
        $this->assertFalse(Event::eventEndIsBeforeStart('2026-11-01', null, '2026-11-02', null));
    }

    public function test_missing_time_does_not_count_as_started_even_when_the_date_has_passed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 15:00:00', config('app.timezone')));

        $event = new Event;
        $event->start_date = '2026-11-10';
        $event->end_date = '2026-11-10';
        $event->start_time = null;
        $event->end_time = '18:00';

        $this->assertFalse($event->calendarStarted());
        $this->assertFalse($event->calendarEnded());

        Carbon::setTestNow();
    }

    public function test_flow_unlock_is_ignored_outside_the_local_environment(): void
    {
        config(['app.flow_unlocked' => true]);

        $event = $this->closedEvent();

        $this->assertFalse(Event::flowUnlocked());
        $this->assertSame('ended', $event->registrationsBlockedReason());
        $this->assertFalse($event->workSubmissionWindowOpen());
        $this->assertFalse($event->reviewersEvaluationWindowOpen());
    }

    public function test_local_flow_unlock_opens_dates_deadlines_and_capacity(): void
    {
        $this->app['env'] = 'local';
        config(['app.flow_unlocked' => true]);

        $event = $this->closedEvent();
        $event->capacity = 1;
        $event->reviewers_min_per_work = 3;

        $this->assertTrue(Event::flowUnlocked());
        $this->assertNull($event->registrationsBlockedReason());
        $this->assertTrue($event->workSubmissionWindowOpen());
        $this->assertTrue($event->workSubmissionDeadlinePassed());
        $this->assertTrue($event->reviewersEvaluationWindowOpen());
        $this->assertFalse($event->reviewDeadlinePassed());
        $this->assertSame(1, $event->minReviewersPerWork());

        $event->finalized_at = now();
        $this->assertSame('finalized', $event->registrationsBlockedReason());
        $this->assertFalse($event->workSubmissionWindowOpen());
    }

    private function closedEvent(): Event
    {
        $event = new Event;
        $event->accepts_submissions = true;
        $event->start_date = '2020-01-01';
        $event->end_date = '2020-01-01';
        $event->start_time = '08:00:00';
        $event->end_time = '12:00:00';
        $event->datetime_registration = '2020-01-01 07:00:00';
        $event->submission_deadline_at = '2020-01-01 07:30:00';
        $event->review_deadline_at = '2020-01-01 09:00:00';

        return $event;
    }
}
