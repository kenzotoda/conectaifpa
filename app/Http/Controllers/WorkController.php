<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Review;
use App\Models\ReviewScore;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkAuthor;
use App\Models\WorkReviewer;
use App\Models\WorkVersion;
use App\Services\WorkScoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WorkController extends Controller
{
    private const ACCEPTED_FILE_MIMES = 'pdf,doc,docx,odt';

    private const ACCEPTED_FILE_MAX_KB = 10240;

    private const WORK_LIST_PER_PAGE = 25;

    public function myWorks()
    {
        $user = auth()->user();

        $works = Work::with(['event', 'authors'])
            ->where('submitter_user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(self::WORK_LIST_PER_PAGE)
            ->withQueryString();

        return view('works.my', compact('works'));
    }

    public function indexByEvent(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);

        if (auth()->id() !== $event->user_id) {
            abort(403, 'Acesso negado.');
        }

        $worksQuery = Work::with(['submitter', 'authors', 'decisionByUser'])
            ->withCount([
                'reviewerAssignments',
                'reviewerAssignments as reviewer_assignments_completed_count' => static function ($q) {
                    $q->where('status', WorkReviewer::STATUS_COMPLETED);
                },
            ])
            ->where('event_id', $event->id);

        $selectedType = $request->query('work_type');
        if (! empty($selectedType)) {
            $worksQuery->where('work_type', $selectedType);
        }

        $works = $worksQuery->orderBy('id')
            ->paginate(self::WORK_LIST_PER_PAGE)
            ->withQueryString();

        $distributionAllowed = $event->workSubmissionDeadlinePassed();

        $eligibleForDistributionStatuses = [
            Work::STATUS_SUBMITTED,
            Work::STATUS_UNDER_REVIEW,
            Work::STATUS_CONFLICT,
            Work::STATUS_ACCEPTED_WITH_CORRECTIONS,
        ];

        $worksForDistribution = Work::query()
            ->with(['submitter'])
            ->withCount('reviewerAssignments')
            ->where('event_id', $event->id)
            ->whereIn('status', $eligibleForDistributionStatuses)
            ->orderBy('work_type')
            ->orderBy('id')
            ->get();

        $worksGroupedByType = $worksForDistribution->groupBy('work_type');

        $oldWorkIds = collect(old('work_ids', []))->map(fn ($id) => (int) $id)->all();

        $availableReviewers = User::query()
            ->where('role', User::ROLE_REVIEWER)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $availableTypes = Work::query()
            ->where('event_id', $event->id)
            ->distinct()
            ->orderBy('work_type')
            ->pluck('work_type')
            ->filter()
            ->values();

        $minReviewers = $event->minReviewersPerWork();
        $maxReviewers = $event->maxReviewersPerWork();

        return view('works.index', compact(
            'event',
            'works',
            'availableReviewers',
            'minReviewers',
            'maxReviewers',
            'availableTypes',
            'selectedType',
            'distributionAllowed',
            'worksGroupedByType',
            'oldWorkIds'
        ));
    }

    public function create($eventId)
    {
        $event = Event::findOrFail($eventId);
        $this->ensureCanSubmitToEvent($event);

        $workTypes = $this->availableWorkTypesForStudentSubmit($event, auth()->user());
        if (empty($workTypes)) {
            return redirect()->route('works.my')
                ->with('msg', 'Você já submeteu um trabalho para cada tipo disponível neste evento.');
        }

        return view('works.create', [
            'event' => $event,
            'workTypes' => $workTypes,
        ]);
    }

    public function store(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);
        $this->ensureCanSubmitToEvent($event);

        $data = $request->validate($this->studentSubmitRules(), $this->messages());
        $this->ensureAcceptedWorkType($event, $data['work_type']);
        $this->ensureSubmitterHasNoWorkOfType($event, auth()->id(), $data['work_type']);

        if (! $request->hasFile('file') || ! $request->file('file')->isValid()) {
            return back()->withErrors(['file' => 'Envie um arquivo válido (PDF, DOC, DOCX ou ODT).'])->withInput();
        }

        $filePath = $this->uploadWorkFile($request->file('file'));
        $submitter = auth()->user();
        $manualCoauthors = $this->normalizedManualCoauthors($request, $submitter);

        DB::transaction(function () use ($data, $event, $filePath, $submitter, $manualCoauthors) {
            $work = Work::create([
                'event_id' => $event->id,
                'submitter_user_id' => $submitter->id,
                'title' => null,
                'abstract' => null,
                'work_type' => $data['work_type'],
                'file_path' => $filePath,
                'status' => Work::STATUS_SUBMITTED,
            ]);

            $this->syncAuthorsFromSubmission($work, $submitter, $manualCoauthors);

            $this->createVersionSnapshot(
                work: $work,
                filePath: $filePath,
                changeLog: 'Versão inicial submetida pelo autor.',
                uploadedByUserId: auth()->id()
            );
        });

        return redirect()->route('works.my')->with('msg', 'Trabalho submetido com sucesso!');
    }

    public function show($workId, WorkScoreService $scoreService)
    {
        $work = Work::with([
            'event',
            'submitter',
            'authors',
            'presentation',
            'reviews.reviewer',
            'reviewerAssignments.reviewer',
            'decisionByUser',
        ])
            ->findOrFail($workId);

        [$isOwner, $isCoordinator, $isAssignedReviewer] = $this->resolveWorkAccess($work);

        $reviewRecommendations = $work->reviews
            ->whereNotNull('submitted_at')
            ->pluck('recommendation')
            ->filter()
            ->unique()
            ->values();

        $hasReviewConflict = $reviewRecommendations->count() > 1;

        $assignmentReviewerIds = $work->reviewerAssignments->pluck('reviewer_user_id')->all();
        $submittedReviewerIds = $work->reviews
            ->whereNotNull('submitted_at')
            ->pluck('reviewer_user_id')
            ->unique()
            ->all();
        $allReviewersCompleted = count($assignmentReviewerIds) > 0
            && count(array_diff($assignmentReviewerIds, $submittedReviewerIds)) === 0;

        $computedFinalScoreAverage = $scoreService->calculateFinalScore($work);

        $showCoordinatorDecisionForm = $this->shouldShowCoordinatorDecisionForm(
            $work,
            $allReviewersCompleted
        );

        return view('works.show', compact(
            'work',
            'isCoordinator',
            'isAssignedReviewer',
            'hasReviewConflict',
            'allReviewersCompleted',
            'computedFinalScoreAverage',
            'showCoordinatorDecisionForm'
        ));
    }

    public function download($workId)
    {
        $work = Work::with('event')->findOrFail($workId);
        [$isOwner, $isCoordinator, $isAssignedReviewer] = $this->resolveWorkAccess($work);

        if (! $isOwner && ! $isCoordinator && ! $isAssignedReviewer) {
            abort(403, 'Acesso negado.');
        }

        if ($isAssignedReviewer && ! $work->event->workSubmissionDeadlinePassed()) {
            abort(403, 'O download do trabalho para avaliação só é liberado após o encerramento do prazo de submissão.');
        }

        if ($isAssignedReviewer && ! $work->event->reviewersEvaluationWindowOpen()) {
            $completed = Review::query()
                ->where('work_id', $work->id)
                ->where('reviewer_user_id', auth()->id())
                ->whereNotNull('submitted_at')
                ->exists();
            if (! $completed) {
                abort(403, 'O período do evento encerrou. Não é mais possível baixar o trabalho para nova avaliação.');
            }
        }

        if (empty($work->file_path) && empty($work->final_version_file_path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        if (filled($work->final_version_file_path) && $work->final_version_validated_at !== null) {
            $officialBucket = (string) config('services.supabase.bucket_official_works');
            if ($officialBucket !== '') {
                $response = $this->fetchFileFromBuckets((string) $work->final_version_file_path, [$officialBucket]);
                if ($response !== null) {
                    $officialName = (string) $work->final_version_file_path;
                    $extension = $this->extensionFromFilename($officialName);
                    $downloadName = 'trabalho_'.$work->id.'_oficial.'.$extension;

                    return response($response->body(), 200, [
                        'Content-Type' => $this->mimeFromExtension($extension),
                        'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
                    ]);
                }
            }
        }

        if (empty($work->file_path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        $buckets = collect([
            config('services.supabase.bucket_corrected_works'),
            config('services.supabase.bucket_works'),
            config('services.supabase.bucket'),
            config('services.supabase.bucket_events'),
        ])->filter()->unique()->values()->all();

        $response = $this->fetchFileFromBuckets($work->file_path, $buckets);
        if ($response === null) {
            abort(404, 'Não foi possível baixar o arquivo do trabalho.');
        }

        $extension = $this->extensionFromFilename($work->file_path);
        $downloadName = 'trabalho_'.$work->id.'.'.$extension;

        return response($response->body(), 200, [
            'Content-Type' => $this->mimeFromExtension($extension),
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ]);
    }

    public function downloadCoordinatorFeedback($workId)
    {
        $work = Work::with('event')->findOrFail($workId);

        $isOwner = $work->submitter_user_id === auth()->id();
        $isCoordinator = $work->event->user_id === auth()->id();

        if (! $isOwner && ! $isCoordinator) {
            abort(403, 'Acesso negado.');
        }

        if (empty($work->coordinator_feedback_file_path)) {
            abort(404, 'Arquivo de feedback não encontrado.');
        }

        $bucket = (string) config('services.supabase.bucket_coordinator_to_participant');
        $response = $this->fetchFileFromBuckets($work->coordinator_feedback_file_path, [$bucket]);
        if ($response === null) {
            abort(404, 'Não foi possível baixar o arquivo de feedback.');
        }

        $extension = $this->extensionFromFilename($work->coordinator_feedback_file_path);
        $downloadName = 'feedback_coordenacao_trabalho_'.$work->id.'.'.$extension;

        return response($response->body(), 200, [
            'Content-Type' => $this->mimeFromExtension($extension),
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ]);
    }

    public function edit($workId)
    {
        $work = Work::with(['event', 'authors', 'submitter'])->findOrFail($workId);
        $this->ensureCanEdit($work);

        $manualCoauthorRows = $work->authors
            ->filter(fn (WorkAuthor $a) => ! $a->is_main_author)
            ->map(fn (WorkAuthor $a) => [
                'author_name' => $a->author_name,
                'author_email' => $a->author_email ?? '',
                'institution' => $a->institution ?? '',
            ])
            ->values()
            ->all();

        return view('works.edit', [
            'work' => $work,
            'event' => $work->event,
            'workTypes' => $this->availableWorkTypesForStudentEdit($work->event, $work),
            'manualCoauthorRows' => $manualCoauthorRows,
        ]);
    }

    public function update(Request $request, $workId)
    {
        $work = Work::with('authors', 'event', 'submitter')->findOrFail($workId);
        $this->ensureCanEdit($work);

        $data = $request->validate($this->studentSubmitRules($work->id), $this->messages());
        $this->ensureAcceptedWorkType($work->event, $data['work_type']);
        if ($data['work_type'] !== $work->work_type) {
            $this->ensureSubmitterHasNoWorkOfType($work->event, $work->submitter_user_id, $data['work_type'], $work->id);
        }

        $newPath = null;
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $newPath = $this->uploadWorkFile($request->file('file'));
        }

        $previousFilePath = $newPath ? $work->file_path : null;
        $manualCoauthors = $this->normalizedManualCoauthors($request, $work->submitter);

        DB::transaction(function () use ($work, $data, $newPath, $manualCoauthors) {
            if ($newPath) {
                $work->file_path = $newPath;
            }

            $work->work_type = $data['work_type'];
            $work->save();

            $this->syncAuthorsFromSubmission($work, $work->submitter, $manualCoauthors);

            if ($newPath) {
                $this->createVersionSnapshot(
                    work: $work,
                    filePath: $newPath,
                    changeLog: 'Arquivo atualizado pelo autor durante a janela de submissão.',
                    uploadedByUserId: auth()->id()
                );
            }
        });

        if ($previousFilePath !== null && $previousFilePath !== '') {
            $this->deleteFileFromStorage($previousFilePath);
        }

        return redirect()->route('works.show', $work->id)->with('msg', 'Trabalho atualizado com sucesso!');
    }

    public function submitCorrection(Request $request, $workId)
    {
        $work = Work::with(['event', 'reviewerAssignments'])->findOrFail($workId);
        $this->ensureCanSubmitCorrection($work);

        $data = $request->validate([
            'file' => 'required|file|mimes:'.self::ACCEPTED_FILE_MIMES.'|max:'.self::ACCEPTED_FILE_MAX_KB,
            'correction_change_log' => 'nullable|string|max:3000',
        ], [
            'file.required' => 'Envie a versão corrigida do trabalho.',
            'file.mimes' => 'O arquivo de correção deve estar em PDF, DOC, DOCX ou ODT.',
            'file.max' => 'O arquivo de correção deve ter no máximo 10MB.',
        ]);

        $newPath = $this->uploadCorrectedWorkFile($request->file('file'));
        $previousFilePath = $work->file_path;

        $reviewsByReviewer = Review::query()
            ->where('work_id', $work->id)
            ->get()
            ->keyBy(fn (Review $r) => (int) $r->reviewer_user_id);

        $storageCleanupPaths = [];
        $assignmentStates = [];

        foreach ($work->reviewerAssignments as $assignment) {
            $rid = (int) $assignment->reviewer_user_id;
            $review = $reviewsByReviewer->get($rid);
            $mustReevaluate = $review !== null
                && $review->submitted_at !== null
                && $review->recommendation === Review::RECOMMENDATION_ACCEPT_WITH_CORRECTIONS;

            $newStatus = WorkReviewer::STATUS_COMPLETED;
            if ($mustReevaluate) {
                if (filled($review->feedback_file_path)) {
                    $storageCleanupPaths[] = (string) $review->feedback_file_path;
                }
                if (filled($review->refined_correction_file_path)) {
                    $storageCleanupPaths[] = (string) $review->refined_correction_file_path;
                }
                $newStatus = WorkReviewer::STATUS_ASSIGNED;
                $priorSnapshot = $this->resolvePriorEvaluationScoreFromReview($review);
                $assignment->prior_evaluation_score = $priorSnapshot;
            } else {
                $assignment->prior_evaluation_score = null;
            }
            $assignmentStates[] = [$assignment, $mustReevaluate === true ? $review : null, $newStatus];
        }

        DB::transaction(function () use ($work, $newPath, $data, $assignmentStates) {
            foreach ($assignmentStates as [$assignment, $reviewToDrop, $newStatus]) {
                if ($reviewToDrop !== null) {
                    $reviewToDrop->scores()->delete();
                    $reviewToDrop->delete();
                }
                $assignment->status = $newStatus;
                $assignment->save();
            }

            $work->file_path = $newPath;
            $work->status = Work::STATUS_UNDER_REVIEW;
            $work->correction_submitted_at = now();
            $work->correction_status = 'submitted';
            $work->correction_change_log = $data['correction_change_log'] ?? null;
            // Mantém decision_at/decision_by para exibir o encaminhamento anterior (aceito com correções) durante a reavaliação.
            $work->author_feedback = null;
            $work->coordinator_feedback_file_path = null;
            $work->save();

            $this->createVersionSnapshot(
                work: $work,
                filePath: $newPath,
                changeLog: $data['correction_change_log'] ?? 'Versão corrigida enviada pelo autor.',
                uploadedByUserId: auth()->id()
            );
        });

        foreach (array_unique($storageCleanupPaths) as $path) {
            if ($path !== '') {
                $this->deleteFileFromStorage($path);
            }
        }

        if ($previousFilePath !== null && $previousFilePath !== '' && $previousFilePath !== $newPath) {
            $this->deleteFileFromStorage($previousFilePath);
        }

        return back()->with('msg', 'Versão corrigida enviada com sucesso. O trabalho voltou para reavaliação.');
    }

    /**
     * Nota global da primeira avaliação antes de remover o parecer na reavaliação de correções.
     * Cobre valores crus em SQLite e fallback à média de critérios, se existir.
     */
    private function resolvePriorEvaluationScoreFromReview(Review $review): ?float
    {
        $review->loadMissing('scores');

        $raw = $review->getRawOriginal('score');
        if ($raw !== null && $raw !== '' && is_numeric($raw)) {
            return round((float) $raw, 2);
        }

        $attr = $review->score;
        if ($attr !== null && $attr !== '' && is_numeric((string) $attr)) {
            return round((float) $attr, 2);
        }

        if ($review->scores->isNotEmpty()) {
            $avg = (float) $review->scores->avg(fn (ReviewScore $s) => (float) $s->score);

            return round($avg, 2);
        }

        return null;
    }

    public function destroy($workId)
    {
        $work = Work::with(['event', 'reviews', 'versions'])->findOrFail($workId);
        $event = $work->event;

        if (auth()->id() !== $event->user_id) {
            abort(403, 'Acesso negado.');
        }

        DB::transaction(function () use ($work) {
            foreach ($work->reviews as $rev) {
                if ($rev->feedback_file_path) {
                    $this->deleteFileFromStorage((string) $rev->feedback_file_path);
                }
                if ($rev->refined_correction_file_path) {
                    $this->deleteFileFromStorage((string) $rev->refined_correction_file_path);
                }
            }
            foreach ($work->versions as $version) {
                if ($version->file_path) {
                    $this->deleteFileFromStorage($version->file_path);
                }
            }
            $this->deleteFileFromStorage($work->file_path);
            $this->deleteFileFromStorage($work->coordinator_feedback_file_path);
            $this->deleteFileFromStorage($work->final_version_file_path);

            $work->deleted_by_coordinator_id = auth()->id();
            $work->save();
            $work->delete();
        });

        return redirect()->route('events.works.index', $event->id)
            ->with('msg', 'Trabalho excluído com sucesso.');
    }

    private function studentSubmitRules(?int $workId = null): array
    {
        $fileRule = $workId
            ? 'nullable|file|mimes:'.self::ACCEPTED_FILE_MIMES.'|max:'.self::ACCEPTED_FILE_MAX_KB
            : 'required|file|mimes:'.self::ACCEPTED_FILE_MIMES.'|max:'.self::ACCEPTED_FILE_MAX_KB;

        return [
            'work_type' => 'required|string|max:255',
            'file' => $fileRule,
            'coauthors_manual' => 'nullable|array|max:15',
            'coauthors_manual.*.author_name' => 'nullable|string|max:255',
            'coauthors_manual.*.author_email' => 'nullable|email|max:255',
            'coauthors_manual.*.institution' => 'nullable|string|max:255',
        ];
    }

    private function messages(): array
    {
        return [
            'work_type.required' => 'Selecione o tipo de trabalho.',
            'work_type.in' => 'Tipo de trabalho inválido.',
            'file.required' => 'O arquivo do trabalho é obrigatório.',
            'file.mimes' => 'O arquivo deve estar em PDF, DOC, DOCX ou ODT.',
            'file.max' => 'O arquivo deve ter no máximo 10MB.',
        ];
    }

    /**
     * @return list<array{author_name: string, author_email: ?string, institution: ?string}>
     */
    private function normalizedManualCoauthors(Request $request, User $submitter): array
    {
        $manual = [];
        foreach ($request->input('coauthors_manual', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = isset($row['author_name']) ? trim((string) $row['author_name']) : '';
            if ($name === '') {
                continue;
            }
            $email = isset($row['author_email']) ? trim((string) $row['author_email']) : '';
            if ($email !== '' && strcasecmp($email, $submitter->email) === 0) {
                continue;
            }
            $inst = isset($row['institution']) ? trim((string) $row['institution']) : '';
            $manual[] = [
                'author_name' => $name,
                'author_email' => $email !== '' ? $email : null,
                'institution' => $inst !== '' ? $inst : null,
            ];
        }

        return $manual;
    }

    private function syncAuthorsFromSubmission(
        Work $work,
        User $submitter,
        array $manualCoauthors
    ): void {
        $work->authors()->delete();

        $order = 1;
        WorkAuthor::create([
            'work_id' => $work->id,
            'user_id' => $submitter->id,
            'author_name' => $submitter->name,
            'author_email' => $submitter->email,
            'institution' => $submitter->participantAffiliationForWorks(),
            'is_main_author' => true,
            'author_order' => $order++,
        ]);

        $seenEmails = collect([strtolower($submitter->email)]);

        foreach ($manualCoauthors as $row) {
            $em = $row['author_email'] ?? null;
            if ($em !== null && $seenEmails->contains(strtolower($em))) {
                continue;
            }
            WorkAuthor::create([
                'work_id' => $work->id,
                'user_id' => null,
                'author_name' => $row['author_name'],
                'author_email' => $em,
                'institution' => $row['institution'] ?? null,
                'is_main_author' => false,
                'author_order' => $order++,
            ]);
            if ($em !== null) {
                $seenEmails->push(strtolower($em));
            }
        }
    }

    private function ensureCanSubmitToEvent(Event $event): void
    {
        $user = auth()->user();

        if (! $user->isParticipant()) {
            abort(403, 'Somente estudantes podem submeter trabalhos.');
        }

        if (! $event->users()->where('users.id', $user->id)->exists()) {
            abort(403, 'É necessário estar inscrito no evento para submeter trabalhos.');
        }

        if (! $event->acceptsSubmissions()) {
            abort(403, 'Este evento não está configurado para receber submissões.');
        }

        if ($event->isFinalized() || $event->calendarEnded()) {
            abort(403, 'Este evento não aceita novas submissões.');
        }

        if (! $event->workSubmissionWindowOpen()) {
            if ($event->submission_deadline_at === null) {
                abort(403, 'Este evento não possui prazo de submissão de trabalhos configurado.');
            }
            abort(403, 'O prazo de submissão deste evento já foi encerrado.');
        }
    }

    private function ensureCanEdit(Work $work): void
    {
        if ($work->submitter_user_id !== auth()->id()) {
            abort(403, 'Acesso negado.');
        }

        if ($work->status !== Work::STATUS_SUBMITTED) {
            abort(403, 'Somente trabalhos em status submetido podem ser editados.');
        }

        if (! $work->event->workSubmissionWindowOpen()) {
            abort(403, 'O prazo de submissão foi encerrado. Não é possível editar/substituir o arquivo.');
        }
    }

    private function ensureCanSubmitCorrection(Work $work): void
    {
        if ($work->submitter_user_id !== auth()->id()) {
            abort(403, 'Acesso negado.');
        }

        if ($work->status !== Work::STATUS_ACCEPTED_WITH_CORRECTIONS) {
            abort(403, 'Este trabalho não está com correção pendente.');
        }

        if ($work->correction_deadline_at === null) {
            abort(403, 'A coordenação ainda não definiu o prazo para envio da versão corrigida.');
        }

        if (Carbon::now()->greaterThanOrEqualTo(Carbon::parse($work->correction_deadline_at))) {
            abort(403, 'O prazo para envio da versão corrigida foi encerrado.');
        }
    }

    private function ensureAcceptedWorkType(Event $event, string $workType): void
    {
        $acceptedTypes = $event->acceptedWorkTypes();

        if ($acceptedTypes->isNotEmpty() && ! $acceptedTypes->contains($workType)) {
            abort(403, 'Este tipo de trabalho não está habilitado para o evento.');
        }
    }

    /**
     * Exibe o formulário "Decisão final" só quando o fluxo permite nova decisão e os pareceres necessários já existem.
     */
    private function shouldShowCoordinatorDecisionForm(Work $work, bool $allReviewersCompleted): bool
    {
        if (! $work->coordinatorCanRegisterNewDecision()) {
            return false;
        }

        if ($work->reviewerAssignments->isEmpty()) {
            return false;
        }

        if (! $work->event->workSubmissionDeadlinePassed()) {
            return false;
        }

        if (! $allReviewersCompleted) {
            return false;
        }

        if ($work->isAwaitingAuthorCorrection()) {
            return false;
        }

        return true;
    }

    private function resolveWorkAccess(Work $work): array
    {
        $user = auth()->user();
        $isOwner = $work->submitter_user_id === $user->id;
        $isCoordinator = $work->event->user_id === $user->id;
        // Usa a tabela de designações diretamente para evitar inconsistências de pivot.
        $isAssignedReviewer = $work->reviewerAssignments()
            ->where('reviewer_user_id', $user->id)
            ->exists();

        if (! $isOwner && ! $isCoordinator && ! $isAssignedReviewer) {
            abort(403, 'Acesso negado.');
        }

        return [$isOwner, $isCoordinator, $isAssignedReviewer];
    }

    private function availableWorkTypesForEvent(Event $event, ?string $currentType = null): array
    {
        if (! $event->acceptsSubmissions()) {
            return [];
        }

        $acceptedTypes = $event->acceptedWorkTypes()->values()->all();

        // Compatibilidade: enquanto não houver configuração, mantém lista padrão.
        if (empty($acceptedTypes)) {
            return Work::workTypeOptions();
        }

        if ($currentType && ! in_array($currentType, $acceptedTypes, true)) {
            $acceptedTypes[] = $currentType;
        }

        return $acceptedTypes;
    }

    /** Tipos que o aluno ainda não usou neste evento (uma submissão por tipo). */
    private function availableWorkTypesForStudentSubmit(Event $event, User $user): array
    {
        $all = $this->availableWorkTypesForEvent($event);
        $used = Work::query()
            ->where('event_id', $event->id)
            ->where('submitter_user_id', $user->id)
            ->pluck('work_type')
            ->unique()
            ->all();

        return array_values(array_diff($all, $used));
    }

    /** Tipos permitidos na edição: os ainda não usados em outra submissão + o tipo atual. */
    private function availableWorkTypesForStudentEdit(Event $event, Work $work): array
    {
        $all = $this->availableWorkTypesForEvent($event, $work->work_type);
        $usedElsewhere = Work::query()
            ->where('event_id', $event->id)
            ->where('submitter_user_id', $work->submitter_user_id)
            ->where('id', '!=', $work->id)
            ->pluck('work_type')
            ->unique()
            ->all();

        $available = array_values(array_diff($all, $usedElsewhere));
        if (! in_array($work->work_type, $available, true)) {
            $available[] = $work->work_type;
        }
        sort($available);

        return $available;
    }

    private function ensureSubmitterHasNoWorkOfType(Event $event, int $submitterUserId, string $workType, ?int $exceptWorkId = null): void
    {
        $query = Work::query()
            ->where('event_id', $event->id)
            ->where('submitter_user_id', $submitterUserId)
            ->where('work_type', $workType);

        if ($exceptWorkId !== null) {
            $query->where('id', '!=', $exceptWorkId);
        }

        if ($query->exists()) {
            abort(403, 'Você já possui uma submissão deste tipo neste evento. Cada tipo só pode ser enviado uma vez.');
        }
    }

    private function createVersionSnapshot(Work $work, string $filePath, ?string $changeLog, int $uploadedByUserId): void
    {
        $nextVersion = ((int) $work->versions()->max('version_number')) + 1;

        WorkVersion::create([
            'work_id' => $work->id,
            'version_number' => $nextVersion,
            'file_path' => $filePath,
            'change_log' => $changeLog,
            'uploaded_by_user_id' => $uploadedByUserId,
            'submitted_at' => now(),
        ]);
    }

    private function uploadWorkFile($file): string
    {
        return $this->uploadFileToBucket(
            file: $file,
            bucket: (string) config('services.supabase.bucket_works')
        );
    }

    private function uploadCorrectedWorkFile($file): string
    {
        return $this->uploadFileToBucket(
            file: $file,
            bucket: (string) config('services.supabase.bucket_corrected_works')
        );
    }

    private function uploadFileToBucket($file, string $bucket): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $fileName = md5($file->getClientOriginalName().now()->timestamp.rand()).'.'.$extension;
        $path = "works/{$fileName}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.supabase.service_role'),
            'apikey' => config('services.supabase.service_role'),
            'Content-Type' => $file->getMimeType(),
        ])->withBody(
            file_get_contents($file->getRealPath()),
            $file->getMimeType()
        )->post(
            config('services.supabase.url')."/storage/v1/object/{$bucket}/{$path}"
        );

        if (! $response->successful()) {
            abort(500, 'Erro ao enviar arquivo para o Supabase.');
        }

        return $fileName;
    }

    private function deleteFileFromStorage(?string $fileName): void
    {
        if (! $fileName) {
            return;
        }

        $paths = [
            "works/{$fileName}",
            $fileName,
        ];

        $buckets = collect([
            config('services.supabase.bucket_works'),
            config('services.supabase.bucket_corrected_works'),
            config('services.supabase.bucket_evaluator_to_coordinator'),
            config('services.supabase.bucket_coordinator_to_participant'),
            config('services.supabase.bucket_official_works'),
            config('services.supabase.bucket'),
            config('services.supabase.bucket_events'),
        ])->filter()->unique()->values();

        foreach ($buckets as $bucket) {
            foreach ($paths as $path) {
                Http::withHeaders([
                    'Authorization' => 'Bearer '.config('services.supabase.service_role'),
                    'apikey' => config('services.supabase.service_role'),
                ])->delete(
                    config('services.supabase.url')."/storage/v1/object/{$bucket}/{$path}"
                );
            }
        }
    }

    private function fetchFileFromBuckets(string $fileName, array $buckets): ?\Illuminate\Http\Client\Response
    {
        $serviceRole = config('services.supabase.service_role');
        $baseUrl = rtrim((string) config('services.supabase.url'), '/');
        $paths = [
            "works/{$fileName}",
            $fileName,
        ];

        foreach ($buckets as $bucket) {
            if (empty($bucket)) {
                continue;
            }

            foreach ($paths as $path) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$serviceRole,
                    'apikey' => $serviceRole,
                ])->get("{$baseUrl}/storage/v1/object/{$bucket}/{$path}");

                if ($response->successful()) {
                    return $response;
                }
            }
        }

        return null;
    }

    private function extensionFromFilename(string $fileName): string
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return strtolower($extension ?: 'pdf');
    }

    private function mimeFromExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'odt' => 'application/vnd.oasis.opendocument.text',
            default => 'application/octet-stream',
        };
    }
}
