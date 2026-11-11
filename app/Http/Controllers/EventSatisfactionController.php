<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventSatisfactionResponse;
use App\Notifications\SatisfactionSurveyAvailable;
use App\Services\EventMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventSatisfactionController extends Controller
{
    public function form(Event $event): View|RedirectResponse
    {
        if ($redirect = $this->notYetOpen($event)) {
            return $redirect;
        }

        $this->authorizeParticipant($event);

        $response = EventSatisfactionResponse::query()
            ->where('event_id', $event->id)
            ->where('user_id', auth()->id())
            ->first();

        return view('events.satisfaction.form', [
            'event' => $event,
            'response' => $response,
            'questions' => $this->questions(),
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        if ($redirect = $this->notYetOpen($event)) {
            return $redirect;
        }

        $this->authorizeParticipant($event);

        $already = EventSatisfactionResponse::query()
            ->where('event_id', $event->id)
            ->where('user_id', auth()->id())
            ->exists();

        if ($already) {
            return redirect()
                ->route('events.satisfaction.form', $event)
                ->with('msg', 'Sua resposta deste evento já foi registrada.');
        }

        $data = $request->validate([
            'organization_score' => ['required', 'integer', 'between:1,5'],
            'content_score' => ['required', 'integer', 'between:1,5'],
            'environment_score' => ['required', 'integer', 'between:1,5'],
            'overall_score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $comment = trim((string) ($data['comment'] ?? ''));

        EventSatisfactionResponse::create([
            'event_id' => $event->id,
            'user_id' => auth()->id(),
            'organization_score' => $data['organization_score'],
            'content_score' => $data['content_score'],
            'environment_score' => $data['environment_score'],
            'overall_score' => $data['overall_score'],
            'comment' => $comment !== '' ? $comment : null,
        ]);

        return redirect()
            ->route('events.satisfaction.form', $event)
            ->with('msg', 'Pesquisa registrada. Obrigado.');
    }

    public function results(Event $event): View|RedirectResponse
    {
        if ($redirect = $this->notYetOpen($event)) {
            return $redirect;
        }

        $this->authorizeCoordinator($event);

        $responses = EventSatisfactionResponse::query()
            ->where('event_id', $event->id)
            ->get();

        $enrolled = $event->participantUsers()->count();

        return view('events.satisfaction.results', [
            'event' => $event,
            'enrolled' => $enrolled,
            'count' => $responses->count(),
            'averages' => [
                'organization_score' => $this->average($responses, 'organization_score'),
                'content_score' => $this->average($responses, 'content_score'),
                'environment_score' => $this->average($responses, 'environment_score'),
                'overall_score' => $this->average($responses, 'overall_score'),
            ],
            'comments' => $responses->pluck('comment')->filter(fn ($comment) => filled($comment))->values(),
            'questions' => $this->questions(),
        ]);
    }

    public function invite(Event $event): RedirectResponse
    {
        if ($redirect = $this->notYetOpen($event)) {
            return $redirect;
        }

        $this->authorizeCoordinator($event);

        if ($event->satisfaction_invited_at) {
            return back()->with('msg', 'O convite desta pesquisa já foi enviado.');
        }

        $participants = $event->participantUsers()->get();

        foreach ($participants as $participant) {
            EventMailer::notify($participant, new SatisfactionSurveyAvailable($event));
        }

        $event->satisfaction_invited_at = now();
        $event->save();

        return back()->with('msg', 'Convite enviado para '.$participants->count().' participante(s) inscrito(s).');
    }

    /**
     * @return array<string, string>
     */
    public static function questions(): array
    {
        return [
            'organization_score' => 'Organização',
            'content_score' => 'Programação',
            'environment_score' => 'Local ou ambiente',
            'overall_score' => 'Satisfação geral',
        ];
    }

    private function authorizeParticipant(Event $event): void
    {
        $user = auth()->user();
        abort_unless($user && $user->isParticipant(), 403);
        abort_unless(
            $event->users()->where('users.id', $user->id)->exists(),
            403
        );
    }

    private function authorizeCoordinator(Event $event): void
    {
        $user = auth()->user();
        abort_unless($user && $user->managesEvent($event), 403);
    }

    private function notYetOpen(Event $event): ?RedirectResponse
    {
        if ($event->isFinalized()) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('msg', 'A pesquisa abre depois que o evento é encerrado.');
    }

    private function average($responses, string $column): ?string
    {
        if ($responses->isEmpty()) {
            return null;
        }

        return number_format((float) $responses->avg($column), 1, ',', '');
    }
};
