<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityPresence;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventPresence;
use App\Models\Signature;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkAuthor;
use App\Models\WorkPresentation;
use App\Services\CertificateIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventCertificateController extends Controller
{
    public function __construct(
        private CertificateIssuer $certificateIssuer
    ) {}

    public function index(Event $event)
    {
        $this->authorizeCoordinator($event);

        $this->ensureEventPresenceRows($event);

        $participantIds = $event->participantUsers()->pluck('users.id');
        $presences = EventPresence::query()
            ->where('event_id', $event->id)
            ->whereIn('user_id', $participantIds)
            ->get()
            ->keyBy('user_id');

        $certParticipation = Certificate::query()
            ->where('event_id', $event->id)
            ->where('tipo', Certificate::TYPE_PARTICIPATION)
            ->count();
        $certActivity = Certificate::query()
            ->where('event_id', $event->id)
            ->where('tipo', Certificate::TYPE_ACTIVITY)
            ->count();
        $certPresentation = Certificate::query()
            ->where('event_id', $event->id)
            ->where('tipo', Certificate::TYPE_PRESENTATION)
            ->count();

        $activities = $event->activities()->whereNull('parent_activity_id')->orderBy('start_at')->get();

        $presentationWorks = $this->presentationWorksForCertificates($event);

        $event->loadMissing('certificateSignatures');
        $attachedSignatureIds = $event->certificateSignatures->pluck('id')->all();

        $signatures = Signature::query()->orderBy('nome')->get();

        $participantUsers = $event->participantUsers()->get();

        return view('events.certificates.index', [
            'event' => $event,
            'participantUsers' => $participantUsers,
            'presences' => $presences,
            'activities' => $activities,
            'certCounts' => [
                'participation' => $certParticipation,
                'activity' => $certActivity,
                'presentation' => $certPresentation,
            ],
            'signatures' => $signatures,
            'attachedSignatureIds' => $attachedSignatureIds,
            'presentationWorks' => $presentationWorks,
            'globalCertificatesBlockMessage' => $this->globalCertificateBlockedMessage($event),
            'participationBatchBlock' => $this->participationBatchReadyMessage($event),
            'presentationsBatchBlock' => $event->acceptsSubmissions()
                ? $this->presentationsBatchReadyMessage($event)
                : null,
            'activitiesBatchBlock' => $this->activitiesBatchReadyMessage($event),
            'activityAttendanceCompleteById' => $this->activityAttendanceCompleteMap($participantIds, $activities),
            'optionalCertificateBlock' => $this->optionalCertificateReadyMessage($event),
            'optionalEligible' => [
                'reviewers' => $this->certificateIssuer->eligibleReviewers($event)->count(),
                'guests' => $this->certificateIssuer->eligibleGuests($event)->count(),
                'organization' => $event->organizers()->count(),
                'coauthors' => $this->certificateIssuer->eligibleCoauthors($event)->count(),
            ],
            'optionalIssued' => [
                'reviewers' => Certificate::query()->where('event_id', $event->id)->where('tipo', Certificate::TYPE_REVIEWER)->count(),
                'guests' => Certificate::query()->where('event_id', $event->id)->where('tipo', Certificate::TYPE_GUEST)->count(),
                'organization' => Certificate::query()->where('event_id', $event->id)->where('tipo', Certificate::TYPE_ORGANIZATION)->count(),
                'coauthors' => Certificate::query()->where('event_id', $event->id)->where('tipo', Certificate::TYPE_COAUTHOR)->count(),
            ],
        ]);
    }

    /**
     * Presença na apresentação (por trabalho) e carga horária do certificado de apresentação, em lote nesta tela.
     */
    public function updatePresentationCertificateRows(Request $request, Event $event)
    {
        $this->authorizeCoordinator($event);

        if (! $event->acceptsSubmissions()) {
            abort(404);
        }

        $data = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.work_id' => ['required', 'integer', Rule::exists('works', 'id')->where('event_id', $event->id)],
            'rows.*.attendance_status' => ['required', Rule::in([
                WorkPresentation::ATTENDANCE_APRESENTADO,
                WorkPresentation::ATTENDANCE_AUSENTE,
            ])],
            'rows.*.certificate_presentation_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'rows.*.certificate_presentation_title' => ['nullable', 'string', 'max:1200'],
        ]);

        foreach ($data['rows'] as $idx => $row) {
            if (($row['attendance_status'] ?? '') === WorkPresentation::ATTENDANCE_APRESENTADO) {
                $titleTrim = trim((string) ($this->normalizePresentationTitleSingleLine((string) ($row['certificate_presentation_title'] ?? ''))));
                if ($titleTrim === '') {
                    throw ValidationException::withMessages([
                        "rows.$idx.certificate_presentation_title" => 'Informe o título do trabalho quando a presença na apresentação for “Apresentado”.',
                    ]);
                }
            }
        }

        $updated = 0;

        foreach ($data['rows'] as $row) {
            $work = Work::query()
                ->where('event_id', $event->id)
                ->whereKey($row['work_id'])
                ->with('presentation')
                ->first();

            if (! $work || ! $work->presentation) {
                continue;
            }

            if (! in_array($work->status, [
                Work::STATUS_SCHEDULED,
                Work::STATUS_PRESENTED,
                Work::STATUS_ABSENT,
                Work::STATUS_PUBLISHED_ANNALS,
            ], true)) {
                continue;
            }

            $work->presentation->attendance_status = $row['attendance_status'];
            $work->presentation->save();

            if ($row['attendance_status'] === WorkPresentation::ATTENDANCE_APRESENTADO) {
                $work->status = Work::STATUS_PRESENTED;
            } else {
                $work->status = Work::STATUS_ABSENT;
            }

            $hoursRaw = $row['certificate_presentation_hours'] ?? null;
            if ($hoursRaw === '' || $hoursRaw === null) {
                $work->certificate_presentation_hours = null;
            } else {
                $work->certificate_presentation_hours = $hoursRaw;
            }

            $work->certificate_presentation_title = $this->normalizePresentationTitleSingleLine(
                (string) ($row['certificate_presentation_title'] ?? '')
            );

            $work->save();

            $updated++;
        }

        return back()->with([
            'msg' => $updated > 0
                ? "Gravado com sucesso. {$updated} trabalho(s) atualizado(s): título, presença e CH."
                : 'Nenhum cartão foi atualizado. Confira se o status da submissão permite edição ou se os dados já estavam iguais.',
            'saved_ok' => $updated > 0,
        ]);
    }

    public function updateEventPresence(Request $request, Event $event)
    {
        $this->authorizeCoordinator($event);

        $participantIds = $event->participantUsers()->pluck('users.id')->all();
        $isHybrid = $event->modality === 'Híbrido';
        $isOnline = $event->modality === 'Online';
        $presentIds = collect($request->input('presente', []))->map(fn ($id) => (int) $id)->all();
        $inPersonIds = collect($request->input('presente_presencial', []))->map(fn ($id) => (int) $id)->all();
        $onlineIds = collect($request->input('presente_online', []))->map(fn ($id) => (int) $id)->all();

        foreach ($participantIds as $uid) {
            $uid = (int) $uid;
            if ($isHybrid) {
                $inPerson = in_array($uid, $inPersonIds, true);
                $online = in_array($uid, $onlineIds, true);
            } elseif ($isOnline) {
                $inPerson = false;
                $online = in_array($uid, $presentIds, true);
            } else {
                $inPerson = in_array($uid, $presentIds, true);
                $online = false;
            }

            EventPresence::updateOrCreate(
                ['event_id' => $event->id, 'user_id' => $uid],
                [
                    'presente' => $inPerson || $online,
                    'presente_presencial' => $inPerson,
                    'presente_online' => $online,
                    'marcado_por' => auth()->id(),
                    'data_marcacao' => now(),
                ]
            );
        }

        return back()->with([
            'msg' => 'Gravado com sucesso. Lista de presença geral do evento.',
            'saved_ok' => true,
        ]);
    }

    public function activityAttendance(Event $event, Activity $activity)
    {
        $this->authorizeCoordinator($event);
        if ((int) $activity->event_id !== (int) $event->id) {
            abort(404);
        }

        $participantUsers = $event->participantUsers()->get(['users.id', 'users.name', 'users.email']);
        $presences = ActivityPresence::query()
            ->where('activity_id', $activity->id)
            ->whereIn('user_id', $participantUsers->pluck('id'))
            ->get()
            ->keyBy('user_id');

        return view('events.certificates.activity-presence', [
            'event' => $event,
            'activity' => $activity,
            'participantUsers' => $participantUsers,
            'presences' => $presences,
        ]);
    }

    public function updateActivityPresence(Request $request, Event $event, Activity $activity)
    {
        $this->authorizeCoordinator($event);
        if ((int) $activity->event_id !== (int) $event->id) {
            abort(404);
        }

        $participantIds = $event->participantUsers()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $allowed = array_fill_keys($participantIds, true);
        $splitIds = preg_split('/\s*,\s*/', (string) $request->input('presentes'), -1, PREG_SPLIT_NO_EMPTY);
        $rawIds = $request->exists('presentes')
            ? (is_array($splitIds) ? $splitIds : [])
            : $request->input('presente', []);
        $presentSet = [];
        foreach ($rawIds as $id) {
            $id = (int) $id;
            if (isset($allowed[$id])) {
                $presentSet[$id] = true;
            }
        }

        if ($participantIds !== []) {
            $now = now();
            $actor = auth()->id();
            $rows = [];
            foreach ($participantIds as $uid) {
                $rows[] = [
                    'user_id' => $uid,
                    'activity_id' => $activity->id,
                    'presente' => isset($presentSet[$uid]),
                    'marcado_por' => $actor,
                    'data_marcacao' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                ActivityPresence::query()->upsert(
                    $chunk,
                    ['user_id', 'activity_id'],
                    ['presente', 'marcado_por', 'data_marcacao', 'updated_at']
                );
            }
        }

        return back()->with([
            'presence_msg' => 'Presença salva.',
            'saved_ok' => true,
        ]);
    }

    public function syncSignatures(Request $request, Event $event)
    {
        $this->authorizeCoordinator($event);

        $data = $request->validate([
            'assinatura_ids' => ['required', 'array', 'min:1'],
            'assinatura_ids.*' => ['integer', Rule::exists('assinaturas', 'id')],
        ], [
            'assinatura_ids.required' => 'Selecione pelo menos uma assinatura antes de salvar.',
            'assinatura_ids.min' => 'Selecione pelo menos uma assinatura antes de salvar.',
        ]);

        $ordered = array_values(array_unique(array_map('intval', $data['assinatura_ids'] ?? [])));
        $sync = [];
        foreach ($ordered as $index => $signatureId) {
            $sync[$signatureId] = ['sort_order' => $index];
        }

        $event->certificateSignatures()->sync($sync);

        return back()->with([
            'msg' => 'Gravado com sucesso. Assinaturas marcadas neste evento.',
            'saved_ok' => true,
        ]);
    }

    public function generateParticipation(Event $event)
    {
        $this->authorizeCoordinator($event);

        if ($msg = $this->participationBatchReadyMessage($event)) {
            return $this->redirectWhenBatchBlocked($event, $msg);
        }

        $count = $this->certificateIssuer->batchParticipation($event);

        return back()->with('msg', $count > 0
            ? "Geração em lote (participação): {$count} certificado(s) emitido(s) ou atualizado(s). O e-mail sai só na primeira emissão."
            : 'Geração em lote (participação): nenhum PDF novo. O certificado já existente só é regravado quando o texto muda.');
    }

    public function generateActivity(Event $event, Activity $activity)
    {
        $this->authorizeCoordinator($event);
        if ((int) $activity->event_id !== (int) $event->id) {
            abort(404);
        }

        return back()->with('msg', 'Os certificados de atividade só podem ser emitidos em lote. Use o botão «Gerar certificados de atividades» na mesma página.');
    }

    public function generateActivitiesAll(Event $event)
    {
        $this->authorizeCoordinator($event);

        if ($msg = $this->activitiesBatchReadyMessage($event)) {
            return $this->redirectWhenBatchBlocked($event, $msg);
        }

        $count = $this->certificateIssuer->batchActivitiesForEvent($event);

        return back()->with('msg', $count > 0
            ? "Geração em lote (atividades): {$count} certificado(s) emitido(s) ou atualizado(s). O e-mail sai só na primeira emissão."
            : 'Geração em lote (atividades): nenhum PDF novo. O certificado já existente só é regravado quando o texto muda, e só sai para quem teve a presença marcada.');
    }

    public function generatePresentations(Event $event)
    {
        $this->authorizeCoordinator($event);

        if (! $event->acceptsSubmissions()) {
            return back()->with('msg', 'Este evento não aceita submissão de trabalhos.');
        }

        if ($msg = $this->presentationsBatchReadyMessage($event)) {
            return $this->redirectWhenBatchBlocked($event, $msg);
        }

        $count = $this->certificateIssuer->batchPresentations($event);

        return back()->with('msg', $count > 0
            ? "Geração em lote (apresentação): {$count} certificado(s) emitido(s) ou PDF atualizado(s)."
            : 'Geração em lote (apresentação): nada a emitir ou atualizar. Verifique trabalhos «Apresentado», dados salvos na lista ou se o conteúdo já coincide com o certificado emitido.');
    }

    public function updateOptionalCertificates(Request $request, Event $event)
    {
        $this->authorizeCoordinator($event);

        $data = $request->validate([
            'certificate_reviewer_enabled' => ['required', 'boolean'],
            'certificate_guest_enabled' => ['required', 'boolean'],
            'certificate_organization_enabled' => ['required', 'boolean'],
            'certificate_coauthor_enabled' => ['required', 'boolean'],
            'certificate_reviewer_hours' => ['required', 'numeric', 'min:0.5', 'max:99999'],
            'certificate_guest_hours' => ['required', 'numeric', 'min:0.5', 'max:99999'],
            'certificate_organization_hours' => ['required', 'numeric', 'min:0.5', 'max:99999'],
        ], [
            'certificate_reviewer_hours.required' => 'Informe a carga horária do certificado de avaliador.',
            'certificate_guest_hours.required' => 'Informe a carga horária do certificado de convidado.',
            'certificate_organization_hours.required' => 'Informe a carga horária do certificado de organização.',
            'certificate_reviewer_hours.min' => 'A carga horária do certificado de avaliador precisa ser de pelo menos 0,5.',
            'certificate_guest_hours.min' => 'A carga horária do certificado de convidado precisa ser de pelo menos 0,5.',
            'certificate_organization_hours.min' => 'A carga horária do certificado de organização precisa ser de pelo menos 0,5.',
        ]);

        $event->fill($data);
        $event->save();

        return back()->with([
            'msg' => 'Gravado com sucesso. Certificados opcionais deste evento.',
            'saved_ok' => true,
        ]);
    }

    public function generateReviewers(Event $event)
    {
        if ($event->certificate_reviewer_hours === null) {
            return back()->with('msg', 'Informe a carga horária do certificado de avaliador e salve antes de gerar.');
        }

        return $this->generateOptional(
            $event,
            (bool) $event->certificate_reviewer_enabled,
            'avaliador',
            fn () => $this->certificateIssuer->batchReviewers($event)
        );
    }

    public function generateGuests(Event $event)
    {
        if ($event->certificate_guest_hours === null) {
            return back()->with('msg', 'Informe a carga horária do certificado de convidado e salve antes de gerar.');
        }

        return $this->generateOptional(
            $event,
            (bool) $event->certificate_guest_enabled,
            'convidado',
            fn () => $this->certificateIssuer->batchGuests($event)
        );
    }

    public function generateOrganization(Event $event)
    {
        if ($event->certificate_organization_hours === null) {
            return back()->with('msg', 'Informe a carga horária do certificado de organização e salve antes de gerar.');
        }

        return $this->generateOptional(
            $event,
            (bool) $event->certificate_organization_enabled,
            'organização',
            fn () => $this->certificateIssuer->batchOrganization($event)
        );
    }

    public function generateCoauthors(Event $event)
    {
        return $this->generateOptional(
            $event,
            (bool) $event->certificate_coauthor_enabled,
            'coautor',
            fn () => $this->certificateIssuer->batchCoauthors($event)
        );
    }

    public function issuedList(Event $event)
    {
        $this->authorizeCoordinator($event);

        $certificates = Certificate::query()
            ->where('event_id', $event->id)
            ->with(['user', 'eventGuest', 'workAuthor'])
            ->orderByDesc('data_emissao')
            ->paginate(40);

        return view('events.certificates.issued', compact('event', 'certificates'));
    }

    public function updateCertificateMeta(Request $request, Event $event)
    {
        $this->authorizeCoordinator($event);

        $rules = [];

        if ($request->has('certificate_total_hours')) {
            $rules['certificate_total_hours'] = ['nullable', 'numeric', 'min:0', 'max:99999'];
        }
        if ($request->has('certificate_organizer')) {
            $rules['certificate_organizer'] = ['nullable', 'string', 'max:255'];
        }
        if ($request->has('certificate_institution')) {
            $rules['certificate_institution'] = ['nullable', 'string', 'max:500'];
        }

        if ($rules === []) {
            return back()->with([
                'msg' => 'Nenhum campo enviado para atualização. Marque pelo menos uma alteração antes de Salvar.',
                'saved_ok' => false,
            ]);
        }

        /** @var array<string, mixed> $data */
        $data = $request->validate($rules);

        if (array_key_exists('certificate_total_hours', $data)) {
            $event->certificate_total_hours = $data['certificate_total_hours'] ?? null;
        }
        if (array_key_exists('certificate_organizer', $data)) {
            $event->certificate_organizer = $data['certificate_organizer'] ?? null;
        }
        if (array_key_exists('certificate_institution', $data)) {
            $event->certificate_institution = $data['certificate_institution'] ?? null;
        }

        $event->save();

        return back()->with([
            'msg' => 'Gravado com sucesso. '.$this->certificateMetaSavedMessage($request),
            'saved_ok' => true,
        ]);
    }

    private function certificateMetaSavedMessage(Request $request): string
    {
        $hours = $request->has('certificate_total_hours');
        $texts = $request->has('certificate_organizer') || $request->has('certificate_institution');

        if ($hours && $texts) {
            return 'Textos de organização, instituição e carga horária de participação.';
        }

        if ($hours) {
            return 'Carga horária da participação geral.';
        }

        return 'Organização e instituição nos PDFs.';
    }

    public function updateActivityWorkload(Request $request, Event $event, Activity $activity)
    {
        $this->authorizeCoordinator($event);
        if ((int) $activity->event_id !== (int) $event->id) {
            abort(404);
        }

        $data = $request->validate([
            'workload_hours' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ]);

        $activity->workload_hours = $data['workload_hours'] ?? null;
        $activity->save();

        return back()->with([
            'msg' => 'Gravado com sucesso. Carga horária do certificado desta atividade.',
            'saved_ok' => true,
        ]);
    }

    private function optionalCertificateReadyMessage(Event $event): ?string
    {
        if ($m = $this->globalCertificateBlockedMessage($event)) {
            return $m;
        }

        if (! $event->isFinalized()) {
            return 'Conclua a finalização do evento antes de emitir avaliador, convidado, organização ou coautor.';
        }

        return null;
    }

    private function generateOptional(Event $event, bool $enabled, string $label, callable $batch)
    {
        $this->authorizeCoordinator($event);

        if (! $enabled) {
            return back()->with('msg', 'Ligue o certificado de '.$label.' e salve antes de gerar.');
        }

        if ($msg = $this->optionalCertificateReadyMessage($event)) {
            return $this->redirectWhenBatchBlocked($event, $msg);
        }

        $count = $batch();

        return back()->with('msg', $count > 0
            ? "Geração ({$label}): {$count} certificado(s) emitido(s) ou PDF atualizado(s)."
            : "Geração ({$label}): nenhum certificado novo. Quem já tem o PDF atual fica como está; confira a lista de elegíveis.");
    }

    private function participationBatchReadyMessage(Event $event): ?string
    {
        if ($m = $this->globalCertificateBlockedMessage($event)) {
            return $m;
        }

        if (! $event->isFinalized()) {
            return 'Para manter conformidade institucional, conclua a etapa de finalização do evento antes de emitir os certificados de participação geral.';
        }

        if ($event->certificate_total_hours === null) {
            return 'Informe a carga horária do certificado de participação na criação ou na edição do evento.';
        }

        if (! $this->eventPresenceSavedForAllParticipants($event)) {
            return 'Clique em «Salvar presença geral» na lista da participação (mesma seção da carga horária) para registrar todas as marcações antes de emitir.';
        }

        $hasPresentParticipant = EventPresence::query()
            ->where('event_id', $event->id)
            ->where('presente', true)
            ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_PARTICIPANT))
            ->exists();

        if (! $hasPresentParticipant) {
            return 'Para prosseguir com a emissão, é obrigatório registrar como presente pelo menos um participante devidamente inscrito neste evento.';
        }

        return null;
    }

    private function presentationsBatchReadyMessage(Event $event): ?string
    {
        if ($m = $this->globalCertificateBlockedMessage($event)) {
            return $m;
        }

        if (! $event->isFinalized()) {
            return 'A emissão em lote de certificados de apresentação de trabalhos fica disponível somente após a finalização formal do evento pelo coordenador.';
        }

        return $this->presentationCertificatesDataIncompleteMessage($event);
    }

    private function activitiesBatchReadyMessage(Event $event): ?string
    {
        if ($m = $this->globalCertificateBlockedMessage($event)) {
            return $m;
        }

        if (! $event->isFinalized()) {
            return 'Os certificados por atividade somente podem ser emitidos depois da finalização do evento pelo coordenador.';
        }

        if ($this->rootActivitiesCount($event) > 0 && ! $this->allRootActivitiesHaveWorkloadForCertificate($event)) {
            return 'Informe a carga horária do certificado em cada atividade, na criação ou na edição do evento, antes de gerar.';
        }

        if (! $this->activityPresencesSavedForAllRootActivities($event)) {
            return 'Para liberar a emissão por atividade, confirme que a presença de todos os participantes foi registrada e salva em cada atividade do cronograma.';
        }

        return null;
    }

    private function redirectWhenBatchBlocked(Event $event, string $message)
    {
        if ($this->globalCertificateBlockedMessage($event) !== null) {
            return back();
        }

        return back()->with('msg', $message);
    }

    /** Requisito comum antes de gerar qualquer tipo de certificado deste evento. */
    private function globalCertificateBlockedMessage(Event $event): ?string
    {
        $event->loadMissing('certificateSignatures');

        if ($event->certificateSignatures->isEmpty()) {
            return 'Selecione e salve pelo menos uma assinatura aplicada ao evento antes de gerar qualquer certificado.';
        }

        return null;
    }

    private function eventPresenceSavedForAllParticipants(Event $event): bool
    {
        $participantIds = $event->participantUsers()->pluck('users.id');

        if ($participantIds->isEmpty()) {
            return true;
        }

        foreach ($participantIds as $userId) {
            $row = EventPresence::query()
                ->where('event_id', $event->id)
                ->where('user_id', $userId)
                ->first();

            if (! $row || $row->data_marcacao === null) {
                return false;
            }
        }

        return true;
    }

    private function presentationCertificatesDataIncompleteMessage(Event $event): ?string
    {
        foreach ($this->presentationWorksForCertificates($event) as $work) {
            $pres = $work->presentation;
            if (! $pres) {
                continue;
            }

            $status = $pres->attendance_status;

            if ($status === WorkPresentation::ATTENDANCE_APRESENTADO) {
                if (trim((string) ($work->certificate_presentation_title ?? '')) === '') {
                    return 'Para cada trabalho como «Apresentado», preencha o título no certificado e clique em «Salvar presença e CH dos trabalhos» antes de gerar.';
                }

                $presenterId = $this->presentationMainPresenterUserId($work);

                if ($presenterId === null) {
                    return 'Revise os trabalhos «Apresentado»: há registro sem autor principal vinculado a usuário no sistema.';
                }

                $user = User::find($presenterId);

                if (! $user || ! $user->isParticipant()) {
                    return 'Um ou mais trabalhos «Apresentado» indicam autor principal que não está inscrito como participante.';
                }
            }
        }

        return null;
    }

    private function rootActivitiesCount(Event $event): int
    {
        return $event->activities()->whereNull('parent_activity_id')->count();
    }

    private function allRootActivitiesHaveWorkloadForCertificate(Event $event): bool
    {
        foreach ($event->activities()->whereNull('parent_activity_id')->cursor() as $activity) {
            if ($activity->workload_hours === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Trabalhos com apresentação agendada e status compatível com certificado (lista da tela e do emissor).
     *
     * @return Collection<int, Work>
     */
    private function presentationWorksForCertificates(Event $event): Collection
    {
        if (! $event->acceptsSubmissions()) {
            return collect();
        }

        return Work::query()
            ->where('event_id', $event->id)
            ->whereIn('status', [
                Work::STATUS_SCHEDULED,
                Work::STATUS_PRESENTED,
                Work::STATUS_ABSENT,
                Work::STATUS_PUBLISHED_ANNALS,
            ])
            ->whereHas('presentation')
            ->with(['presentation', 'submitter'])
            ->orderByRaw("CASE WHEN title IS NOT NULL AND title <> '' THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->get();
    }

    private function presentationMainPresenterUserId(Work $work): ?int
    {
        $main = WorkAuthor::query()
            ->where('work_id', $work->id)
            ->where('is_main_author', true)
            ->first();

        if ($main && $main->user_id !== null) {
            return (int) $main->user_id;
        }

        return $work->submitter_user_id ? (int) $work->submitter_user_id : null;
    }

    private function activityPresencesSavedForAllRootActivities(Event $event): bool
    {
        $participantIds = $event->participantUsers()->pluck('users.id');

        if ($participantIds->isEmpty()) {
            return true;
        }

        $rootIds = $event->activities()->whereNull('parent_activity_id')->pluck('id');

        if ($rootIds->isEmpty()) {
            return true;
        }

        foreach ($rootIds as $activityId) {
            if (! $this->participantActivityPresenceRowsMarked($participantIds, (int) $activityId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, int|string>  $participantIds
     * @param  Collection<int, Activity>  $activities
     * @return array<int, bool>
     */
    private function activityAttendanceCompleteMap(Collection $participantIds, Collection $activities): array
    {
        $map = [];

        foreach ($activities as $activity) {
            $activityId = (int) $activity->id;

            if ($participantIds->isEmpty()) {
                $map[$activityId] = true;

                continue;
            }

            $map[$activityId] = $this->participantActivityPresenceRowsMarked($participantIds, $activityId);
        }

        return $map;
    }

    /**
     * @param  Collection<int, int|string>  $participantIds
     */
    private function participantActivityPresenceRowsMarked(Collection $participantIds, int $activityId): bool
    {
        foreach ($participantIds as $userId) {
            $row = ActivityPresence::query()
                ->where('activity_id', $activityId)
                ->where('user_id', $userId)
                ->first();

            if (! $row || $row->data_marcacao === null) {
                return false;
            }
        }

        return true;
    }

    private function normalizePresentationTitleSingleLine(string $raw): ?string
    {
        $collapsed = trim(preg_replace('/\s+/u', ' ', preg_replace('/[\r\n\t]+/', ' ', $raw)));

        return $collapsed === '' ? null : $collapsed;
    }

    private function authorizeCoordinator(Event $event): void
    {
        if (! auth()->user()?->managesEvent($event)) {
            abort(403);
        }
    }

    private function ensureEventPresenceRows(Event $event): void
    {
        foreach ($event->participantUsers()->pluck('users.id') as $uid) {
            EventPresence::firstOrCreate(
                ['event_id' => $event->id, 'user_id' => $uid],
                ['presente' => false]
            );
        }
    }

}
