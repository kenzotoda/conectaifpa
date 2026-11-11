<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Models\Work;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagesEvent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $event = $this->eventFrom($request);

        if (! $user || ! $event || ! $user->managesEvent($event)) {
            abort(403, 'Acesso negado. Você só gerencia os eventos em que está designado.');
        }

        return $next($request);
    }

    private function eventFrom(Request $request): ?Event
    {
        $event = $request->route('event');
        if ($event instanceof Event) {
            return $event;
        }
        if (is_numeric($event)) {
            return Event::query()->find((int) $event);
        }

        $eventId = $request->route('eventId');
        if ($eventId === null && $request->route('id') !== null && ! $request->route('work')) {
            $eventId = $request->route('id');
        }
        if (is_numeric($eventId)) {
            return Event::query()->find((int) $eventId);
        }

        $work = $request->route('work');
        if ($work instanceof Work) {
            return $work->relationLoaded('event') ? $work->event : $work->event()->first();
        }
        if (is_numeric($work)) {
            return Work::query()->find((int) $work)?->event;
        }

        return null;
    }
}
