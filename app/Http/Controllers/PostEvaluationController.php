<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Models\WorkPresentation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostEvaluationController extends Controller
{
    public function setAttendance(Request $request, $workId)
    {
        $work = Work::with(['event', 'presentation'])->findOrFail($workId);
        $this->ensureCoordinator($work);

        $presentation = $work->presentation;
        if (! $presentation) {
            return back()->with('msg', 'Este trabalho não possui agendamento.');
        }

        $data = $request->validate([
            'attendance_status' => ['required', Rule::in([
                WorkPresentation::ATTENDANCE_APRESENTADO,
                WorkPresentation::ATTENDANCE_AUSENTE,
            ])],
        ]);

        $presentation->attendance_status = $data['attendance_status'];
        $presentation->save();

        if ($data['attendance_status'] === WorkPresentation::ATTENDANCE_APRESENTADO) {
            $work->status = Work::STATUS_PRESENTED;
        } else {
            $work->status = Work::STATUS_ABSENT;
        }
        $work->save();

        return back()->with('msg', 'Situação da apresentação atualizada.');
    }

    public function myPresentationSchedule()
    {
        $works = Work::with(['event', 'presentation'])
            ->where('submitter_user_id', auth()->id())
            ->whereIn('status', [
                Work::STATUS_SCHEDULED,
                Work::STATUS_PRESENTED,
                Work::STATUS_ABSENT,
                Work::STATUS_PUBLISHED_ANNALS,
            ])
            ->orderByDesc('updated_at')
            ->get();

        return view('works.my-presentation', compact('works'));
    }

    private function ensureCoordinator(Work $work): void
    {
        if (auth()->id() !== $work->event->user_id) {
            abort(403);
        }
    }
}
