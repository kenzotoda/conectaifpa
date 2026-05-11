<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Models\WorkAuthor;
use App\Models\WorkPresentation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PresentationController extends Controller
{
    /** @return list<array{value: string, label: string}> */
    public static function presentationTypeOptions(): array
    {
        return [
            ['value' => WorkPresentation::TYPE_ORAL, 'label' => 'Oral'],
            ['value' => WorkPresentation::TYPE_POSTER, 'label' => 'Pôster'],
            ['value' => WorkPresentation::TYPE_ONLINE, 'label' => 'Online'],
        ];
    }

    public function upsert(Request $request, Work $work)
    {
        $work->loadMissing('event');

        $this->ensureEventCoordinator($work->event);

        if (! Work::whereKey($work->id)->eligibleForCoordinatorPresentationScheduling()->exists()) {
            return back()->with('msg', 'Este trabalho ainda não está apto ao agendamento da apresentação.');
        }

        $request->merge([
            'location' => trim((string) $request->input('location', '')),
        ]);

        $data = $request->validate([
            '_presentation_work_row' => ['nullable', Rule::in([$work->id])],
            'presentation_type' => ['required', Rule::in([WorkPresentation::TYPE_ORAL, WorkPresentation::TYPE_POSTER, WorkPresentation::TYPE_ONLINE])],
            'session_name' => 'nullable|string|max:255',
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date'],
            'location' => 'required|string|max:255',
        ], [
            'presentation_type.required' => 'Selecione o tipo de apresentação.',
            'presentation_type.in' => 'Tipo de apresentação inválido.',
            'scheduled_start.required' => 'Informe a data e horário de início da apresentação.',
            'scheduled_end.required' => 'Informe a data e horário de fim da apresentação.',
            'location.required' => 'Informe o local físico ou o link da apresentação.',
        ]);

        $start = Carbon::parse((string) $data['scheduled_start']);
        $end = Carbon::parse((string) $data['scheduled_end']);

        if ($end->lt($start)) {
            return back()->with('msg', 'O horário de fim deve ser igual ou posterior ao início.')->withInput();
        }

        try {
            $this->assertPresentationWithinEventWindow($work->event, $start, $end);
        } catch (\InvalidArgumentException $e) {
            return back()->with('msg', $e->getMessage())->withInput();
        }

        if ($this->authorScheduleConflict($work, $start, $end)) {
            return back()->with('msg', 'Conflito: há autor deste trabalho com outra apresentação neste horário.')->withInput();
        }

        $existing = WorkPresentation::firstOrNew(['work_id' => $work->id]);

        WorkPresentation::updateOrCreate(
            ['work_id' => $work->id],
            array_merge([
                'event_session_id' => null,
                'presentation_order' => null,
                'presentation_type' => $data['presentation_type'],
                'session_name' => $data['session_name'] ?? null,
                'scheduled_start' => $start,
                'scheduled_end' => $end,
                'location' => $data['location'],
                'attendance_status' => $existing->exists ? $existing->attendance_status : WorkPresentation::ATTENDANCE_AUSENTE,
            ])
        );

        $work->refresh();

        $this->syncWorkStatusAfterPresentationSave($work, true);

        return back()->with('msg', 'Dados da apresentação atualizados.');
    }

    public function destroy(Work $work)
    {
        $work->loadMissing('event');
        $this->ensureEventCoordinator($work->event);

        if (! in_array($work->status, [Work::STATUS_FINAL_VALIDATED, Work::STATUS_SCHEDULED], true)) {
            return back()->with('msg', 'Não é possível remover o agendamento neste estado do trabalho.');
        }

        WorkPresentation::where('work_id', $work->id)->delete();

        if ($work->status === Work::STATUS_SCHEDULED) {
            $work->status = Work::STATUS_FINAL_VALIDATED;
            $work->save();
        }

        return back()->with('msg', 'Apresentação removida.');
    }

    private function ensureEventCoordinator(\App\Models\Event $event): void
    {
        if (auth()->id() !== $event->user_id) {
            abort(403, 'Acesso negado.');
        }
    }

    private function assertPresentationWithinEventWindow(\App\Models\Event $event, Carbon $slotStart, Carbon $slotEnd): void
    {
        $evtStart = $event->calendarStartAt();
        $evtEnd = $event->calendarEndAt();
        if ($slotStart->lt($evtStart) || $slotEnd->gt($evtEnd)) {
            throw new \InvalidArgumentException('O horário da apresentação deve estar dentro do período oficial do evento.');
        }
    }

    /** Conflitos por sobreposição de horário usando e-mail dos autores no mesmo evento. */
    private function authorScheduleConflict(Work $work, Carbon $slotStart, Carbon $slotEnd): bool
    {
        $work->loadMissing('authors');

        $emails = $work->authors->pluck('author_email')->map(fn ($e) => strtolower(trim((string) $e)))->filter()->unique();
        if ($emails->isEmpty()) {
            return false;
        }

        $otherWorkIds = Work::query()
            ->where('event_id', $work->event_id)
            ->where('id', '!=', $work->id)
            ->pluck('id');

        foreach ($otherWorkIds as $wid) {
            $authorsOther = WorkAuthor::where('work_id', $wid)->pluck('author_email')->map(fn ($e) => strtolower(trim((string) $e)))->filter();
            if ($authorsOther->intersect($emails)->isEmpty()) {
                continue;
            }
            $other = WorkPresentation::where('work_id', $wid)->first();
            if (! $other || ! $other->scheduled_start || ! $other->scheduled_end) {
                continue;
            }
            $oStart = Carbon::parse($other->scheduled_start);
            $oEnd = Carbon::parse($other->scheduled_end);
            if ($slotStart->lt($oEnd) && $oStart->lt($slotEnd)) {
                return true;
            }
        }

        return false;
    }

    /** Ajusta status do trabalho em função da presença ou ausência de janela de horário definida. */
    private function syncWorkStatusAfterPresentationSave(Work $work, bool $hasScheduleWindow): void
    {
        if ($hasScheduleWindow && $work->status === Work::STATUS_FINAL_VALIDATED) {
            $work->status = Work::STATUS_SCHEDULED;
            $work->save();

            return;
        }

        if (! $hasScheduleWindow && $work->status === Work::STATUS_SCHEDULED) {
            $work->status = Work::STATUS_FINAL_VALIDATED;
            $work->save();
        }
    }
}
