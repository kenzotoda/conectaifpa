<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Work;

class EventPresentationScheduleController extends Controller
{
    public function manage(Event $event)
    {
        $userId = (int) (auth()->id() ?? 0);
        abort_unless($event->user_id === $userId && auth()->user()->isCoordinator(), 403);

        $works = Work::with(['authors.user', 'event', 'presentation'])
            ->where('event_id', $event->id)
            ->eligibleForCoordinatorPresentationScheduling()
            ->orderByRaw("CASE WHEN title IS NOT NULL AND title <> '' THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->get();

        $types = PresentationController::presentationTypeOptions();

        return view('events.presentations.manage', [
            'event' => $event,
            'works' => $works,
            'types' => $types,
        ]);
    }
}
