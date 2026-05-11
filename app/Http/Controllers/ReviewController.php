<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkReviewer;
use App\Models\WorkVersion;
use App\Services\WorkScoreService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    private const FEEDBACK_FILE_MIMES = 'pdf,doc,docx,odt';

    private const FEEDBACK_FILE_MAX_KB = 10240;

    private const ASSIGNED_REVIEWS_PER_PAGE = 25;

    public function assigned()
    {
        $user = auth()->user();

        $assignments = WorkReviewer::with([
            'work.event',
            'work.reviews' => static function ($q) use ($user) {
                $q->where('reviewer_user_id', $user->id);
            },
        ])
            ->where('reviewer_user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(self::ASSIGNED_REVIEWS_PER_PAGE)
            ->withQueryString();

        return view('reviews.assigned', compact('assignments'));
    }

    public function form($workId)
    {
        $work = Work::with(['event', 'reviewerAssignments'])
            ->findOrFail($workId);

        if (in_array($work->status, [
            Work::STATUS_WITHDRAWAL_REQUESTED,
            Work::STATUS_WITHDRAWN,
            Work::STATUS_CANCELLED,
            Work::STATUS_DESK_REJECTED,
        ], true)) {
            return redirect()->route('reviews.assigned')
                ->with('msg', 'Este trabalho não está disponível para avaliação no momento.');
        }

        $reviewerId = auth()->id();
        $assignment = $work->reviewerAssignments()
            ->where('reviewer_user_id', $reviewerId)
            ->first();

        if (! $assignment) {
            abort(403, 'Acesso negado.');
        }

        $review = Review::query()
            ->where('work_id', $work->id)
            ->where('reviewer_user_id', $reviewerId)
            ->first();

        if ($review && $review->submitted_at !== null) {
            return view('reviews.summary', compact('work', 'review', 'assignment'));
        }

        if (! $this->submissionDeadlinePassed($work->event)) {
            return redirect()->route('reviews.assigned')
                ->with('msg', 'A avaliação só pode ser realizada após o encerramento do prazo de submissão.');
        }

        if ($work->event->calendarEnded()) {
            return redirect()->route('reviews.assigned')
                ->with('msg', 'O período do evento já encerrou. Não é mais possível enviar avaliações (incluindo reavaliação após correções).');
        }

        $isCorrectionReReview = $work->isAwaitingCorrectionReReview();
        $carryOverScore = ($isCorrectionReReview && $assignment->prior_evaluation_score !== null)
            ? (float) $assignment->prior_evaluation_score
            : null;

        return view('reviews.form', compact('work', 'review', 'assignment', 'isCorrectionReReview', 'carryOverScore'));
    }

    public function submit(Request $request, $workId, WorkScoreService $scoreService)
    {
        $work = Work::with('event')->findOrFail($workId);
        $reviewerId = auth()->id();

        $assignment = $work->reviewerAssignments()
            ->where('reviewer_user_id', $reviewerId)
            ->first();

        if (! $assignment) {
            abort(403, 'Acesso negado.');
        }

        if (in_array($work->status, [
            Work::STATUS_WITHDRAWAL_REQUESTED,
            Work::STATUS_WITHDRAWN,
            Work::STATUS_CANCELLED,
            Work::STATUS_DESK_REJECTED,
        ], true)) {
            return back()->with('msg', 'Este trabalho não aceita novas avaliações.');
        }

        if (! $this->submissionDeadlinePassed($work->event)) {
            return back()->with('msg', 'A avaliação só pode ser enviada após o encerramento do prazo de submissão.');
        }

        if ($work->event->calendarEnded()) {
            return back()->with('msg', 'O período do evento já encerrou. Não é mais possível enviar avaliações (incluindo reavaliação após correções).');
        }

        $alreadySubmitted = Review::query()
            ->where('work_id', $work->id)
            ->where('reviewer_user_id', $reviewerId)
            ->whereNotNull('submitted_at')
            ->exists();
        if ($alreadySubmitted) {
            return redirect()->route('reviews.assigned')
                ->with('msg', 'Você já enviou sua avaliação para este trabalho. Não é permitido avaliar novamente.');
        }

        $isCorrectionReReview = $work->isAwaitingCorrectionReReview();

        if ($isCorrectionReReview && $assignment->prior_evaluation_score !== null) {
            $request->merge([
                'score' => number_format((float) $assignment->prior_evaluation_score, 2, '.', ''),
            ]);
        }

        $allowedRecommendations = $isCorrectionReReview
            ? [Review::RECOMMENDATION_ACCEPT, Review::RECOMMENDATION_REJECT]
            : [
                Review::RECOMMENDATION_ACCEPT,
                Review::RECOMMENDATION_ACCEPT_WITH_CORRECTIONS,
                Review::RECOMMENDATION_REJECT,
            ];

        $data = $request->validate([
            'recommendation' => ['required', Rule::in($allowedRecommendations)],
            'score' => 'required|numeric',
            'general_comment' => 'nullable|string|max:5000',
            'comment_to_author' => 'nullable|string|max:5000',
            'feedback_file' => [
                Rule::requiredIf(fn () => $isCorrectionReReview || (
                    ! $isCorrectionReReview
                        && $request->input('recommendation') === Review::RECOMMENDATION_ACCEPT_WITH_CORRECTIONS
                )),
                'nullable',
                'file',
                'mimes:'.self::FEEDBACK_FILE_MIMES,
                'max:'.self::FEEDBACK_FILE_MAX_KB,
            ],
        ], [
            'recommendation.required' => 'A recomendação final é obrigatória.',
            'recommendation.in' => 'A recomendação selecionada não é válida para esta etapa do trabalho.',
            'score.required' => 'A nota da avaliação é obrigatória.',
            'score.numeric' => 'A nota deve conter apenas números.',
            'feedback_file.required' => $isCorrectionReReview
                ? 'Na reavaliação da versão corrigida é obrigatório enviar o arquivo refinado para a coordenação (fica registado como documento separado da versão enviada pelo autor).'
                : 'Ao aceitar com correções, o arquivo de feedback para a coordenação é obrigatório.',
            'feedback_file.mimes' => 'O arquivo deve estar em PDF, DOC, DOCX ou ODT.',
            'feedback_file.max' => 'O arquivo deve ter no máximo 10MB.',
        ]);

        $feedbackUploaded = $request->hasFile('feedback_file') && $request->file('feedback_file')->isValid();

        $refinedCorrectionFileName = null;
        if ($isCorrectionReReview) {
            if (! $feedbackUploaded || blank($work->file_path)) {
                return back()
                    ->withErrors(['feedback_file' => 'Arquivo obrigatório inválido ou trabalho sem arquivo registrado na submissão.'])
                    ->withInput();
            }
            $canonicalExt = strtolower(pathinfo((string) $work->file_path, PATHINFO_EXTENSION));
            $uploadExt = strtolower($request->file('feedback_file')->getClientOriginalExtension() ?: '');
            if ($canonicalExt === '') {
                $canonicalExt = 'pdf';
            }
            if ($uploadExt !== $canonicalExt) {
                return back()
                    ->withErrors([
                        'feedback_file' => 'Use a mesma extensão do arquivo da correção atual do trabalho (.'
                            .$canonicalExt.'), para manter o formato alinhado ao documento do autor.',
                    ])
                    ->withInput();
            }
            try {
                $refinedCorrectionFileName = $this->uploadReviewerRefinedCorrectionFile(
                    $request->file('feedback_file'),
                    $work,
                    $reviewerId
                );
            } catch (\RuntimeException $e) {
                return back()->withErrors([
                    'feedback_file' => 'Falha ao gravar no armazenamento: '.$e->getMessage(),
                ])->withInput();
            }
        }

        $feedbackFilePath = null;
        if ($feedbackUploaded && ! $isCorrectionReReview) {
            try {
                $feedbackFilePath = $this->uploadFileToBucket(
                    $request->file('feedback_file'),
                    (string) config('services.supabase.bucket_corrected_works')
                );
            } catch (\RuntimeException $e) {
                return back()->withErrors([
                    'feedback_file' => 'Falha ao enviar o arquivo: '.$e->getMessage(),
                ])->withInput();
            }
        }

        DB::transaction(function () use (
            $data,
            $work,
            $reviewerId,
            $assignment,
            $scoreService,
            $feedbackFilePath,
            $isCorrectionReReview,
            $refinedCorrectionFileName,
        ) {
            $payload = [
                'recommendation' => $data['recommendation'],
                'score' => $data['score'],
                'general_comment' => $data['general_comment'] ?? null,
                'comment_to_author' => $data['comment_to_author'] ?? null,
                'submitted_at' => now(),
                'is_blind' => true,
            ];

            if ($feedbackFilePath !== null) {
                $payload['feedback_file_path'] = $feedbackFilePath;
            }
            if ($isCorrectionReReview && $refinedCorrectionFileName !== null) {
                $payload['refined_correction_file_path'] = $refinedCorrectionFileName;
            }

            $review = Review::updateOrCreate(
                [
                    'work_id' => $work->id,
                    'reviewer_user_id' => $reviewerId,
                ],
                $payload
            );
            // Compatibilidade: remove notas antigas por critério se houver.
            $review->scores()->delete();

            $assignment->status = WorkReviewer::STATUS_COMPLETED;
            if ($isCorrectionReReview) {
                $assignment->prior_evaluation_score = null;
            }
            $assignment->save();

            if ($isCorrectionReReview) {
                $nextVersion = ((int) WorkVersion::query()->where('work_id', $work->id)->max('version_number')) + 1;
                $refinedByName = User::query()->find($reviewerId)?->name ?? 'Avaliador';
                WorkVersion::create([
                    'work_id' => $work->id,
                    'version_number' => $nextVersion,
                    'file_path' => $work->file_path,
                    'change_log' => 'Reavaliação: arquivo refinado enviado pelo avaliador '.$refinedByName.' à coordenação (documento à parte da versão corrigida pelo autor; cada avaliador reconvocado pode ter o seu registro).',
                    'uploaded_by_user_id' => $reviewerId,
                    'submitted_at' => now(),
                ]);
            }

            if ($work->status === Work::STATUS_SUBMITTED) {
                $work->status = Work::STATUS_UNDER_REVIEW;
            }

            $work->refresh();
            if (! $work->final_score_is_manual) {
                $work->final_score = $scoreService->calculateFinalScore($work);
            }
            $work->save();
        });

        return redirect()->route('reviews.assigned')->with('msg', 'Avaliação enviada com sucesso!');
    }

    public function downloadReviewerRefinedCorrection(int $workId, int $reviewId)
    {
        $work = Work::with('event')->findOrFail($workId);
        $review = Review::where('work_id', $work->id)->where('id', $reviewId)->firstOrFail();

        $userId = auth()->id();
        $isCoordinator = $userId === $work->event->user_id;
        $isOwnerReviewer = (int) $review->reviewer_user_id === (int) $userId;
        if (! $isCoordinator && ! $isOwnerReviewer) {
            abort(403, 'Acesso negado.');
        }

        if (empty($review->refined_correction_file_path)) {
            abort(404, 'Versão refinada não encontrada.');
        }

        if (! $isCoordinator && ! $work->event->workSubmissionDeadlinePassed()) {
            abort(403, 'Este arquivo ficará disponível após o encerramento do prazo de submissão de trabalhos.');
        }

        $bucket = (string) config('services.supabase.bucket_corrected_works');
        $response = $this->fetchFileFromBucket((string) $review->refined_correction_file_path, $bucket);
        if ($response === null) {
            abort(404, 'Não foi possível baixar o arquivo refinado.');
        }

        $extension = $this->extensionFromFilename((string) $review->refined_correction_file_path);
        $downloadName = 'correcao_refinada_'.$work->id.'_avaliador_'.$review->reviewer_user_id.'.'.$extension;

        return response($response->body(), 200, [
            'Content-Type' => $this->mimeFromExtension($extension),
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ]);
    }

    /**
     * @throws \RuntimeException
     */
    private function uploadReviewerRefinedCorrectionFile(\Illuminate\Http\UploadedFile $file, Work $work, int $reviewerUserId): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $fileName = 'corr-w'.$work->id.'-r'.$reviewerUserId.'-'.md5($file->getClientOriginalName().now()->timestamp.rand()).'.'.$extension;
        $path = 'works/'.$fileName;
        $bucket = (string) config('services.supabase.bucket_corrected_works');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('services.supabase.service_role'),
                'apikey' => config('services.supabase.service_role'),
                'Content-Type' => $file->getMimeType(),
            ])->withBody(
                file_get_contents($file->getRealPath()) ?: '',
                $file->getMimeType()
            )->post(
                config('services.supabase.url')."/storage/v1/object/{$bucket}/{$path}"
            );
        } catch (ConnectionException $e) {
            throw new \RuntimeException($this->friendlySupabaseConnectionMessage($e), 0, $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException($response->body() ?: ('HTTP '.$response->status()));
        }

        return $fileName;
    }

    /**
     * @throws \RuntimeException
     */
    private function uploadOfficialWorkFile(\Illuminate\Http\UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $fileName = 'official-'.md5($file->getClientOriginalName().now()->timestamp.rand()).'.'.$extension;
        $path = 'works/'.$fileName;
        $bucket = (string) config('services.supabase.bucket_official_works');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('services.supabase.service_role'),
                'apikey' => config('services.supabase.service_role'),
                'Content-Type' => $file->getMimeType(),
            ])->withBody(
                file_get_contents($file->getRealPath()) ?: '',
                $file->getMimeType()
            )->post(
                config('services.supabase.url')."/storage/v1/object/{$bucket}/{$path}"
            );
        } catch (ConnectionException $e) {
            throw new \RuntimeException($this->friendlySupabaseConnectionMessage($e), 0, $e);
        }

        if (! $response->successful()) {
            throw new \RuntimeException($response->body() ?: ('HTTP '.$response->status()));
        }

        return $fileName;
    }

    private function deleteOfficialWorkObject(string $fileName): void
    {
        if (blank($fileName)) {
            return;
        }

        $bucket = (string) config('services.supabase.bucket_official_works');
        if ($bucket === '') {
            return;
        }

        $serviceRole = config('services.supabase.service_role');
        $baseUrl = rtrim((string) config('services.supabase.url'), '/');
        foreach (['works/'.$fileName, $fileName] as $path) {
            try {
                Http::withHeaders([
                    'Authorization' => 'Bearer '.$serviceRole,
                    'apikey' => $serviceRole,
                ])->delete("{$baseUrl}/storage/v1/object/{$bucket}/{$path}");
            } catch (ConnectionException $e) {
                Log::warning('Supabase: não foi possível remover objeto antigo (rede/DNS).', [
                    'path' => $path,
                    'bucket' => $bucket,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function fetchEvaluatorFeedbackFromStorage(string $fileName): ?\Illuminate\Http\Client\Response
    {
        foreach ([
            (string) config('services.supabase.bucket_corrected_works'),
            (string) config('services.supabase.bucket_evaluator_to_coordinator'),
        ] as $bucket) {
            if ($bucket === '') {
                continue;
            }
            $response = $this->fetchFileFromBucket($fileName, $bucket);
            if ($response !== null) {
                return $response;
            }
        }

        return null;
    }

    public function assignReviewer(Request $request, $workId)
    {
        $work = Work::with('event')->findOrFail($workId);
        $this->ensureCoordinatorOwner($work);

        return redirect()
            ->route('events.works.index', $work->event_id)
            ->with('msg', 'A vinculação individual foi desativada. Use a distribuição geral na página de gerenciar trabalhos.');
    }

    public function removeReviewer($workId, $reviewerId)
    {
        $work = Work::with('event')->findOrFail($workId);
        $this->ensureCoordinatorOwner($work);

        return redirect()
            ->route('events.works.index', $work->event_id)
            ->with('msg', 'A remoção individual foi desativada. Use a distribuição geral na página de gerenciar trabalhos.');
    }

    public function distributeReviewersForEvent(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);

        if (auth()->id() !== $event->user_id) {
            abort(403, 'Acesso negado.');
        }

        if (! $event->acceptsSubmissions()) {
            return back()->with('msg', 'O evento não está configurado para fluxo de submissões.');
        }

        if (! $event->workSubmissionDeadlinePassed()) {
            return back()->with(
                'msg',
                'A distribuição de avaliadores só é permitida após o encerramento do prazo de submissão de trabalhos.'
            );
        }

        $minReviewers = $event->minReviewersPerWork();
        $maxReviewers = $event->maxReviewersPerWork();

        $eligibleStatuses = [
            Work::STATUS_SUBMITTED,
            Work::STATUS_UNDER_REVIEW,
            Work::STATUS_CONFLICT,
            Work::STATUS_ACCEPTED_WITH_CORRECTIONS,
        ];

        $data = $request->validate([
            'work_ids' => ['required', 'array', 'min:1'],
            'work_ids.*' => ['integer', Rule::exists('works', 'id')],
            'reviewer_ids' => ['required', 'array', 'min:1'],
            'reviewer_ids.*' => ['integer', Rule::exists('users', 'id')],
            'reviewers_per_work' => ['required', 'integer', 'min:'.$minReviewers, 'max:'.$maxReviewers],
        ], [
            'work_ids.required' => 'Selecione pelo menos um trabalho para receber avaliadores.',
            'work_ids.min' => 'Selecione pelo menos um trabalho para receber avaliadores.',
            'reviewer_ids.required' => 'Selecione pelo menos um avaliador.',
            'reviewer_ids.min' => 'Selecione pelo menos um avaliador.',
            'reviewers_per_work.required' => 'Informe quantos avaliadores serão vinculados por trabalho.',
            'reviewers_per_work.min' => "A distribuição exige no mínimo {$minReviewers} avaliadores por trabalho.",
            'reviewers_per_work.max' => "A distribuição permite no máximo {$maxReviewers} avaliadores por trabalho.",
        ]);

        $selectedReviewerIds = collect($data['reviewer_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $validReviewerIds = User::query()
            ->whereIn('id', $selectedReviewerIds)
            ->where('role', User::ROLE_REVIEWER)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($validReviewerIds->count() !== $selectedReviewerIds->count()) {
            return back()->withErrors([
                'reviewer_ids' => 'Alguns usuários selecionados não possuem papel de avaliador.',
            ])->withInput();
        }

        $reviewersPerWork = (int) $data['reviewers_per_work'];

        if ($validReviewerIds->count() !== $reviewersPerWork) {
            return back()->withErrors([
                'reviewer_ids' => "Selecione exatamente {$reviewersPerWork} avaliadores para distribuir essa quantidade por trabalho.",
            ])->withInput();
        }

        $requestedWorkIds = collect($data['work_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $works = Work::query()
            ->with(['reviewerAssignments'])
            ->where('event_id', $event->id)
            ->whereIn('id', $requestedWorkIds)
            ->whereIn('status', $eligibleStatuses)
            ->orderBy('id')
            ->get();

        if ($works->count() !== $requestedWorkIds->count()) {
            return back()->withErrors([
                'work_ids' => 'Alguns trabalhos selecionados não pertencem a este evento ou não estão elegíveis para distribuição.',
            ])->withInput();
        }

        if ($works->isEmpty()) {
            return back()->with('msg', 'Não há trabalhos elegíveis para distribuição de avaliadores.');
        }

        foreach ($works as $work) {
            $eligibleForWork = $validReviewerIds
                ->reject(fn (int $id) => $id === (int) $work->submitter_user_id)
                ->values();

            if ($eligibleForWork->count() < $reviewersPerWork) {
                return back()->withErrors([
                    'reviewer_ids' => "O trabalho \"{$work->listTitle(true)}\" não possui avaliadores elegíveis suficientes para atingir {$reviewersPerWork} vínculos.",
                ])->withInput();
            }
        }

        $rotationOrder = $validReviewerIds->values();
        $rotationIndex = 0;
        DB::transaction(function () use ($works, $rotationOrder, $reviewersPerWork, &$rotationIndex) {
            foreach ($works as $work) {
                $eligibleForWork = $rotationOrder
                    ->reject(fn (int $id) => $id === (int) $work->submitter_user_id)
                    ->values();

                $selectedForWork = collect();
                $attempts = 0;
                $maxAttempts = max($rotationOrder->count() * 3, $reviewersPerWork * 2);

                while ($selectedForWork->count() < $reviewersPerWork && $attempts < $maxAttempts) {
                    $candidate = $rotationOrder[$rotationIndex % $rotationOrder->count()];
                    $rotationIndex++;
                    $attempts++;

                    if ($candidate === (int) $work->submitter_user_id) {
                        continue;
                    }

                    if (! $selectedForWork->contains($candidate)) {
                        $selectedForWork->push($candidate);
                    }
                }

                if ($selectedForWork->count() < $reviewersPerWork) {
                    $selectedForWork = $eligibleForWork->take($reviewersPerWork)->values();
                }

                $selectedIds = $selectedForWork->values()->all();

                $toRemoveIds = $work->reviewerAssignments
                    ->pluck('reviewer_user_id')
                    ->map(fn ($id) => (int) $id)
                    ->reject(fn ($id) => in_array($id, $selectedIds, true))
                    ->values();

                if ($toRemoveIds->isNotEmpty()) {
                    WorkReviewer::query()
                        ->where('work_id', $work->id)
                        ->whereIn('reviewer_user_id', $toRemoveIds)
                        ->delete();

                    Review::query()
                        ->where('work_id', $work->id)
                        ->whereIn('reviewer_user_id', $toRemoveIds)
                        ->delete();
                }

                foreach ($selectedIds as $reviewerId) {
                    $assignment = WorkReviewer::query()
                        ->where('work_id', $work->id)
                        ->where('reviewer_user_id', $reviewerId)
                        ->first();

                    if (! $assignment) {
                        WorkReviewer::create([
                            'work_id' => $work->id,
                            'reviewer_user_id' => $reviewerId,
                            'assigned_by' => auth()->id(),
                            'assigned_at' => now(),
                            'status' => WorkReviewer::STATUS_ASSIGNED,
                        ]);

                        continue;
                    }

                    $assignment->assigned_by = auth()->id();
                    $assignment->assigned_at = now();
                    if ($assignment->status !== WorkReviewer::STATUS_COMPLETED) {
                        $assignment->status = WorkReviewer::STATUS_ASSIGNED;
                    }
                    $assignment->save();
                }

                if ($work->status === Work::STATUS_SUBMITTED) {
                    $work->status = Work::STATUS_UNDER_REVIEW;
                    $work->save();
                }
            }
        });

        $worksCount = $works->count();
        $label = $worksCount > 1 ? 'trabalhos' : 'trabalho';

        return back()->with(
            'msg',
            "Distribuição concluída para {$worksCount} {$label}. Cada trabalho ficou com {$reviewersPerWork} avaliadores."
        );
    }

    public function decide(Request $request, $workId)
    {
        $work = Work::with(['event', 'reviews'])->findOrFail($workId);
        $this->ensureCoordinatorOwner($work);

        /** Indica se o aceite ocorre na rodada após envio da versão corrigida pelo autor. */
        $approvalAfterCorrectionReReview = $work->isAwaitingCorrectionReReview();

        $allowedDecisionStatuses = $approvalAfterCorrectionReReview
            ? [
                Work::STATUS_APPROVED_FINAL,
                Work::STATUS_REJECTED,
            ]
            : [
                Work::STATUS_APPROVED_FINAL,
                Work::STATUS_ACCEPTED_WITH_CORRECTIONS,
                Work::STATUS_REJECTED,
            ];

        if (! $work->event->workSubmissionDeadlinePassed()) {
            return back()->with(
                'msg',
                'A decisão final e o envio de feedback ao participante só são permitidos após o encerramento do prazo de submissão de trabalhos.'
            );
        }

        $assignmentCount = $work->reviewerAssignments()->count();
        if ($assignmentCount === 0) {
            return back()->with(
                'msg',
                'Designe avaliadores ao trabalho antes de registrar a decisão final.'
            );
        }

        $pendingAssignments = $work->reviewerAssignments()
            ->where('status', '!=', WorkReviewer::STATUS_COMPLETED)
            ->exists();
        if ($pendingAssignments) {
            return back()->with(
                'msg',
                'Todos os avaliadores designados devem enviar o parecer antes da coordenação registrar a nota final e a decisão.'
            );
        }

        $data = $request->validate([
            'status' => ['required', Rule::in($allowedDecisionStatuses)],
            'author_feedback' => 'nullable|string|max:5000',
            'correction_deadline_at' => 'required_if:status,'.Work::STATUS_ACCEPTED_WITH_CORRECTIONS.'|nullable|date|after:now',
            'coordinator_feedback_file' => 'nullable|file|mimes:'.self::FEEDBACK_FILE_MIMES.'|max:'.self::FEEDBACK_FILE_MAX_KB,
            'coordinator_final_score' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'official_final_file' => [
                Rule::requiredIf(fn () => $request->input('status') === Work::STATUS_APPROVED_FINAL),
                'nullable',
                'file',
                'mimes:'.self::FEEDBACK_FILE_MIMES,
                'max:'.self::FEEDBACK_FILE_MAX_KB,
            ],
        ], [
            'status.required' => 'Selecione a decisão final.',
            'correction_deadline_at.required_if' => 'Informe o prazo para o envio da versão corrigida.',
            'correction_deadline_at.after' => 'O prazo de correção deve ser uma data/hora futura.',
            'coordinator_feedback_file.mimes' => 'O arquivo de correções deve estar em PDF, DOC, DOCX ou ODT.',
            'coordinator_feedback_file.max' => 'O arquivo de correções deve ter no máximo 10MB.',
            'official_final_file.required' => 'Para aceitar o trabalho é obrigatório enviar a versão oficial final em PDF, DOC, DOCX ou ODT.',
            'official_final_file.mimes' => 'A versão oficial deve estar em PDF, DOC, DOCX ou ODT.',
            'official_final_file.max' => 'A versão oficial deve ter no máximo 10MB.',
            'coordinator_final_score.required' => 'Informe a nota final do trabalho.',
            'coordinator_final_score.numeric' => 'A nota final deve ser um número.',
            'coordinator_final_score.min' => 'A nota final não pode ser negativa.',
            'coordinator_final_score.max' => 'A nota final não pode ultrapassar 999,99.',
        ]);

        if ($data['status'] === Work::STATUS_ACCEPTED_WITH_CORRECTIONS) {
            $hasNewCoordinatorFile = $request->hasFile('coordinator_feedback_file')
                && $request->file('coordinator_feedback_file')->isValid();
            if (! $hasNewCoordinatorFile && empty($work->coordinator_feedback_file_path)) {
                return back()
                    ->withErrors([
                        'coordinator_feedback_file' => 'Para decisão "Aceito com correções", o arquivo com correções consolidadas para o aluno é obrigatório.',
                    ])
                    ->withInput();
            }
        }

        if ($request->hasFile('coordinator_feedback_file') && $request->file('coordinator_feedback_file')->isValid()) {
            try {
                $work->coordinator_feedback_file_path = $this->uploadFileToBucket(
                    $request->file('coordinator_feedback_file'),
                    (string) config('services.supabase.bucket_coordinator_to_participant')
                );
            } catch (\RuntimeException $e) {
                return back()
                    ->withErrors(['coordinator_feedback_file' => 'Falha ao enviar o arquivo: '.$e->getMessage()])
                    ->withInput();
            }
        }

        if ($data['status'] === Work::STATUS_APPROVED_FINAL) {
            $hasOfficial = $request->hasFile('official_final_file') && $request->file('official_final_file')->isValid();
            if (! $hasOfficial) {
                return back()
                    ->withErrors(['official_final_file' => 'Envie a versão oficial final do trabalho (obrigatória ao aceitar).'])
                    ->withInput();
            }
            if (! empty($work->final_version_file_path)) {
                $this->deleteOfficialWorkObject((string) $work->final_version_file_path);
            }
            try {
                $work->final_version_file_path = $this->uploadOfficialWorkFile($request->file('official_final_file'));
            } catch (\RuntimeException $e) {
                return back()
                    ->withErrors(['official_final_file' => 'Falha ao enviar a versão oficial: '.$e->getMessage()])
                    ->withInput();
            }

            $nextOfficialVersion = ((int) WorkVersion::query()->where('work_id', $work->id)->max('version_number')) + 1;
            WorkVersion::create([
                'work_id' => $work->id,
                'version_number' => $nextOfficialVersion,
                'file_path' => $work->final_version_file_path,
                'change_log' => $approvalAfterCorrectionReReview
                    ? 'Versão oficial final validada pela coordenação após correções do autor e reavaliação (apresentações e anais).'
                    : 'Versão oficial final validada pela coordenação no aceite direto na decisão final (apresentações e anais).',
                'uploaded_by_user_id' => auth()->id(),
                'submitted_at' => now(),
            ]);

            $work->final_version_source = $approvalAfterCorrectionReReview
                ? Work::FINAL_VERSION_SOURCE_CORRECTED
                : Work::FINAL_VERSION_SOURCE_DIRECT;
            $work->final_version_validated_at = now();
            $work->final_version_validated_by = auth()->id();
            $work->status = Work::STATUS_FINAL_VALIDATED;
        } else {
            $work->status = $data['status'];
        }

        $work->author_feedback = $data['author_feedback'] ?? null;
        $work->decision_at = now();
        $work->decision_by = auth()->id();

        if ($data['status'] === Work::STATUS_ACCEPTED_WITH_CORRECTIONS) {
            $work->correction_requested_at = now();
            $work->correction_deadline_at = $data['correction_deadline_at'];
            $work->correction_submitted_at = null;
            $work->correction_status = 'pending';
        } else {
            $work->correction_requested_at = null;
            $work->correction_deadline_at = null;
            $work->correction_submitted_at = null;
            $work->correction_change_log = null;
            $work->correction_status = null;
        }

        $work->final_score = round((float) $data['coordinator_final_score'], 2);
        $work->final_score_is_manual = true;

        $work->save();

        $successMsg = 'Decisão final registrada com sucesso.';
        if ($data['status'] === Work::STATUS_APPROVED_FINAL) {
            $successMsg .= ' '.$work->canonicalFinalVersionDescription();
        }

        return back()->with('msg', $successMsg);
    }

    public function downloadMyEvaluatorFeedback($workId)
    {
        $work = Work::with('event')->findOrFail($workId);
        $reviewerId = auth()->id();

        $assignment = $work->reviewerAssignments()
            ->where('reviewer_user_id', $reviewerId)
            ->first();
        if (! $assignment) {
            abort(403, 'Acesso negado.');
        }

        $review = Review::where('work_id', $work->id)
            ->where('reviewer_user_id', $reviewerId)
            ->firstOrFail();

        if ($review->submitted_at === null) {
            abort(403, 'A avaliação ainda não foi enviada.');
        }

        if (empty($review->feedback_file_path)) {
            abort(404, 'Arquivo de feedback não encontrado.');
        }

        $response = $this->fetchEvaluatorFeedbackFromStorage($review->feedback_file_path);
        if ($response === null) {
            abort(404, 'Não foi possível baixar o arquivo de feedback.');
        }

        $extension = $this->extensionFromFilename($review->feedback_file_path);
        $downloadName = 'meu_feedback_trabalho_'.$work->id.'.'.$extension;

        return response($response->body(), 200, [
            'Content-Type' => $this->mimeFromExtension($extension),
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ]);
    }

    public function downloadEvaluatorFeedback($workId, $reviewId)
    {
        $work = Work::with('event')->findOrFail($workId);
        $review = Review::where('work_id', $work->id)->where('id', $reviewId)->firstOrFail();

        if (auth()->id() !== $work->event->user_id) {
            abort(403, 'Acesso negado.');
        }

        if (! $work->event->workSubmissionDeadlinePassed()) {
            abort(403, 'Arquivos de feedback dos avaliadores só ficam disponíveis após o encerramento do prazo de submissão.');
        }

        if (empty($review->feedback_file_path)) {
            abort(404, 'Arquivo de feedback não encontrado.');
        }

        $response = $this->fetchEvaluatorFeedbackFromStorage($review->feedback_file_path);
        if ($response === null) {
            abort(404, 'Não foi possível baixar o arquivo de feedback.');
        }

        $extension = $this->extensionFromFilename($review->feedback_file_path);
        $downloadName = 'feedback_avaliador_'.$review->id.'.'.$extension;

        return response($response->body(), 200, [
            'Content-Type' => $this->mimeFromExtension($extension),
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ]);
    }

    private function uploadFileToBucket($file, string $bucket): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $fileName = md5($file->getClientOriginalName().now()->timestamp.rand()).'.'.$extension;
        $path = "works/{$fileName}";

        try {
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
        } catch (ConnectionException $e) {
            throw new \RuntimeException($this->friendlySupabaseConnectionMessage($e), 0, $e);
        }

        if (! $response->successful()) {
            abort(500, 'Erro ao enviar arquivo de feedback para o Supabase.');
        }

        return $fileName;
    }

    /** Mensagem amigável quando o cliente HTTP não alcança o Supabase (DNS, rede, firewall, etc.). */
    private function friendlySupabaseConnectionMessage(\Throwable $e): string
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Could not resolve host') || str_contains($msg, 'cURL error 6')) {
            return 'Não foi possível contatar o armazenamento de arquivos (falha ao resolver o endereço do servidor). Verifique a internet, o DNS e se SUPABASE_URL no .env está correta; em ambiente local, veja se não precisa de VPN ou se o projeto Supabase ainda existe.';
        }
        if (str_contains($msg, 'Connection timed out') || str_contains($msg, 'cURL error 28')) {
            return 'O armazenamento de arquivos não respondeu a tempo. Tente novamente em instantes.';
        }
        if (str_contains($msg, 'Could not connect') || str_contains($msg, 'cURL error 7')) {
            return 'Não foi possível conectar ao armazenamento de arquivos. Verifique rede, firewall e se o serviço está disponível.';
        }

        return 'Falha de conexão com o armazenamento de arquivos. Tente novamente ou entre em contato com o suporte técnico.';
    }

    private function fetchFileFromBucket(string $fileName, string $bucket): ?\Illuminate\Http\Client\Response
    {
        if (empty($bucket)) {
            return null;
        }

        $serviceRole = config('services.supabase.service_role');
        $baseUrl = rtrim((string) config('services.supabase.url'), '/');
        $paths = [
            "works/{$fileName}",
            $fileName,
        ];

        foreach ($paths as $path) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$serviceRole,
                'apikey' => $serviceRole,
            ])->get("{$baseUrl}/storage/v1/object/{$bucket}/{$path}");

            if ($response->successful()) {
                return $response;
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

    public function markAnnalsPublication(Request $request, $workId)
    {
        $work = Work::with('event')->findOrFail($workId);
        $this->ensureCoordinatorOwner($work);

        $manage = redirect()->route('events.annals.manage', $work->event_id);

        if (! in_array($work->status, [Work::STATUS_PRESENTED, Work::STATUS_PUBLISHED_ANNALS], true)) {
            return $manage->with('msg', 'Somente trabalhos já registrados como apresentados podem ser incluídos nos anais.');
        }

        if ($work->final_version_validated_at === null) {
            return $manage->with('msg', 'A versão final precisa estar validada antes da publicação nos anais.');
        }

        $data = $request->validate([
            'annals_url' => 'nullable|url|max:255',
            'annals_note' => 'nullable|string|max:2000',
        ], [
            'annals_url.url' => 'Informe uma URL válida para o registro nos anais.',
            'annals_note.max' => 'A observação dos anais deve ter no máximo 2000 caracteres.',
        ]);

        if ($work->status === Work::STATUS_PUBLISHED_ANNALS) {
            $work->annals_url = $data['annals_url'] ?? null;
            $work->annals_note = $data['annals_note'] ?? null;
            $work->save();

            return $manage->with('msg', 'Dados da publicação nos anais foram atualizados.');
        }

        $work->published_in_annals_at = now();
        $work->annals_url = $data['annals_url'] ?? null;
        $work->annals_note = $data['annals_note'] ?? null;
        $work->status = Work::STATUS_PUBLISHED_ANNALS;
        $work->save();

        return $manage->with('msg', 'Publicação nos anais registrada com sucesso.');
    }

    public function clearAnnalsPublication($workId)
    {
        $work = Work::with('event')->findOrFail($workId);
        $this->ensureCoordinatorOwner($work);

        $work->published_in_annals_at = null;
        $work->annals_url = null;
        $work->annals_note = null;
        if ($work->status === Work::STATUS_PUBLISHED_ANNALS) {
            $work->status = Work::STATUS_PRESENTED;
        }
        $work->save();

        return redirect()
            ->route('events.annals.manage', $work->event_id)
            ->with('msg', 'Registro de publicação nos anais removido. O trabalho permanece como apresentado.');
    }

    public function exportEventReportCsv($eventId)
    {
        $event = Event::findOrFail($eventId);
        if (auth()->id() !== $event->user_id) {
            abort(403, 'Acesso negado.');
        }

        $works = Work::with(['submitter', 'reviews.reviewer', 'presentation'])
            ->where('event_id', $event->id)
            ->orderBy('created_at')
            ->get();

        $fileName = 'relatorio_avaliacoes_evento_'.$event->id.'.csv';
        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($works) {
            $file = fopen('php://output', 'w');
            echo "\xEF\xBB\xBF";
            fputcsv($file, [
                'Trabalho',
                'Autor principal',
                'Tipo',
                'Status',
                'Nota final',
                'Qtd avaliações',
                'Recomendacoes',
                'Apresentacao',
                'Publicado em anais',
                'URL anais',
            ]);

            foreach ($works as $work) {
                $recommendations = $work->reviews
                    ->pluck('recommendation')
                    ->filter()
                    ->implode(' | ');

                fputcsv($file, [
                    $work->listTitle(true),
                    $work->submitter->name ?? '',
                    $work->work_type,
                    $work->status,
                    $work->final_score,
                    $work->reviews->count(),
                    $recommendations,
                    $work->presentation?->presentation_type ?? '',
                    $work->published_in_annals_at ? 'Sim' : 'Não',
                    $work->annals_url ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function ensureCoordinatorOwner(Work $work): void
    {
        if (auth()->id() !== $work->event->user_id) {
            abort(403, 'Acesso negado.');
        }
    }

    private function submissionDeadlinePassed(Event $event): bool
    {
        return $event->workSubmissionDeadlinePassed();
    }
}
