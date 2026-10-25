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
}
