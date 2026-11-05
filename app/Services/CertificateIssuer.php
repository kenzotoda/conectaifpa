<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventPresence;
use App\Models\Review;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkAuthor;
use App\Models\WorkPresentation;
use App\Notifications\CertificateAvailable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateIssuer
{
    public function __construct(
        private SupabaseObjectClient $objects
    ) {}

    public function batchParticipation(Event $event): int
    {
        $event->loadMissing('certificateSignatures');

        $count = 0;
        $presences = EventPresence::query()
            ->where('event_id', $event->id)
            ->where('presente', true)
            ->get();

        foreach ($presences as $presence) {
            $user = User::find($presence->user_id);
            if (! $user || ! $user->isParticipant()) {
                continue;
            }
            if ($this->issueParticipation($user, $event)) {
                $count++;
            }
        }

        return $count;
    }

    public function batchActivity(Activity $activity): int
    {
        $event = $activity->event;
        $event->loadMissing('certificateSignatures');

        $count = 0;
        foreach ($activity->activityPresences()->where('presente', true)->cursor() as $presence) {
            $user = User::find($presence->user_id);
            if (! $user || ! $user->isParticipant()) {
                continue;
            }
            if ($this->issueActivity($user, $activity, $event)) {
                $count++;
            }
        }

        return $count;
    }

    public function batchPresentations(Event $event): int
    {
        $event->loadMissing(['certificateSignatures', 'works.presentation']);

        $count = 0;
        $works = Work::query()
            ->where('event_id', $event->id)
            ->whereHas('presentation', fn ($q) => $q->where('attendance_status', WorkPresentation::ATTENDANCE_APRESENTADO))
            ->with(['presentation', 'authors'])
            ->get();

        foreach ($works as $work) {
            $userId = $this->mainPresenterUserId($work);
            if ($userId === null) {
                continue;
            }

            $user = User::find($userId);
            if (! $user || ! $user->isParticipant()) {
                continue;
            }

            if ($this->issuePresentation($user, $work, $event)) {
                $count++;
            }
        }

        return $count;
    }

    public function issueParticipation(User $user, Event $event): bool
    {
        if (! $user->isParticipant()) {
            return false;
        }

        if (! $event->isFinalized()) {
            return false;
        }

        $present = EventPresence::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('presente', true)
            ->first();

        if (! $present) {
            return false;
        }

        $channel = CertificateDocument::attendanceChannel($present, $event);
        $contentFingerprint = $this->participationCertificateContentFingerprint($user, $event, $channel);

        $existing = Certificate::query()
            ->where('user_id', $user->id)
            ->where('tipo', Certificate::TYPE_PARTICIPATION)
            ->where('event_id', $event->id)
            ->whereNull('activity_id')
            ->whereNull('work_id')
            ->first();

        if ($existing && (string) ($existing->conteudo_hash ?? '') === $contentFingerprint) {
            return false;
        }

        if ($existing && filled($existing->codigo_validacao)) {
            $code = (string) $existing->codigo_validacao;
            $relativePath = filled($existing->arquivo_pdf)
                ? (string) $existing->arquivo_pdf
                : $this->pdfRelativePath($event->id, $code);
        } else {
            $code = $this->uniqueValidationCode();
            $relativePath = $this->pdfRelativePath($event->id, $code);
        }

        $hours = $event->certificate_total_hours;
        $data = [
            'recipientName' => $user->name,
            'eventTitle' => $event->title,
            'organizer' => $event->certificateOrganizerDisplay(),
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => $hours !== null ? (string) $hours : '-',
            'activityTitle' => null,
            'activityTypeLabel' => null,
            'workTitle' => null,
            'issuedDate' => now()->format('d/m/Y'),
            'validationCode' => $code,
            'signatures' => $this->signatureBlocks($event),
        ];
        $data = array_merge($data, CertificateDocument::pdfFields($user, $event, $code, Certificate::TYPE_PARTICIPATION, null, $channel));

        $this->writePdf('certificates.pdf.participacao', $data, $relativePath);

        if ($existing) {
            $existing->arquivo_pdf = $relativePath;
            $existing->codigo_validacao = $code;
            $existing->conteudo_hash = $contentFingerprint;
            $existing->save();

            return true;
        }

        Certificate::create([
            'user_id' => $user->id,
            'tipo' => Certificate::TYPE_PARTICIPATION,
            'event_id' => $event->id,
            'activity_id' => null,
            'work_id' => null,
            'arquivo_pdf' => $relativePath,
            'codigo_validacao' => $code,
            'conteudo_hash' => $contentFingerprint,
            'data_emissao' => now(),
        ]);

        $this->notifyCertificateAvailable($user, $event, Certificate::TYPE_PARTICIPATION, null, null);

        return true;
    }

    private function participationCertificateContentFingerprint(User $user, Event $event, ?string $channel): string
    {
        $event->loadMissing('certificateSignatures');
        $hours = $event->certificate_total_hours;

        $payload = [
            'recipient' => $user->name,
            'eventTitle' => $event->title,
            'organizer' => $event->certificateOrganizerDisplay(),
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => $hours !== null ? (string) $hours : '',
            'period' => CertificateDocument::period($event),
            'place' => CertificateDocument::attendancePlace($event, $channel),
            'modality' => CertificateDocument::modality($event),
            'matricula' => CertificateDocument::matricula($user),
            'presence' => CertificateDocument::presenceText(Certificate::TYPE_PARTICIPATION, $channel),
            'signatureIds' => $event->certificateSignatures->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function issueActivity(User $user, Activity $activity, Event $event): bool
    {
        if (! $user->isParticipant()) {
            return false;
        }

        if ((int) $activity->event_id !== (int) $event->id) {
            return false;
        }

        if (! $event->isFinalized()) {
            return false;
        }

        $presence = $activity->activityPresences()
            ->where('user_id', $user->id)
            ->first();
        $frequency = null;

        if ($activity->is_extension) {
            $frequency = $presence?->frequencia_percent !== null ? (int) $presence->frequencia_percent : null;
            if ($frequency === null || $frequency < Activity::EXTENSION_MINIMUM_FREQUENCY) {
                return false;
            }
        } elseif (! $presence || ! $presence->presente) {
            return false;
        }

        $contentFingerprint = $this->activityCertificateContentFingerprint($user, $activity, $event, $frequency);

        $existing = Certificate::query()
            ->where('user_id', $user->id)
            ->where('tipo', Certificate::TYPE_ACTIVITY)
            ->where('event_id', $event->id)
            ->where('activity_id', $activity->id)
            ->whereNull('work_id')
            ->first();

        if ($existing && (string) ($existing->conteudo_hash ?? '') === $contentFingerprint) {
            return false;
        }

        if ($existing && filled($existing->codigo_validacao)) {
            $code = (string) $existing->codigo_validacao;
            $relativePath = filled($existing->arquivo_pdf)
                ? (string) $existing->arquivo_pdf
                : $this->pdfRelativePath($event->id, $code);
        } else {
            $code = $this->uniqueValidationCode();
            $relativePath = $this->pdfRelativePath($event->id, $code);
        }

        $hours = $activity->workload_hours;
        $typeLabel = Activity::typeLabels()[$activity->type] ?? $activity->type;

        $data = [
            'recipientName' => $user->name,
            'eventTitle' => $event->title,
            'organizer' => $event->certificateOrganizerDisplay(),
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => $hours !== null ? (string) $hours : '-',
            'activityTitle' => $activity->title,
            'activityTypeLabel' => $typeLabel,
            'workTitle' => null,
            'issuedDate' => now()->format('d/m/Y'),
            'validationCode' => $code,
            'signatures' => $this->signatureBlocks($event),
        ];
        $data = array_merge($data, CertificateDocument::pdfFields($user, $event, $code, Certificate::TYPE_ACTIVITY, $activity, null, $frequency));

        $this->writePdf('certificates.pdf.atividade', $data, $relativePath);

        if ($existing) {
            $existing->arquivo_pdf = $relativePath;
            $existing->codigo_validacao = $code;
            $existing->conteudo_hash = $contentFingerprint;
            $existing->save();

            return true;
        }

        Certificate::create([
            'user_id' => $user->id,
            'tipo' => Certificate::TYPE_ACTIVITY,
            'event_id' => $event->id,
            'activity_id' => $activity->id,
            'work_id' => null,
            'arquivo_pdf' => $relativePath,
            'codigo_validacao' => $code,
            'conteudo_hash' => $contentFingerprint,
            'data_emissao' => now(),
        ]);

        $this->notifyCertificateAvailable($user, $event, Certificate::TYPE_ACTIVITY, $activity->id, null);

        return true;
    }

    private function activityCertificateContentFingerprint(User $user, Activity $activity, Event $event, ?int $frequency): string
    {
        $event->loadMissing('certificateSignatures');

        $payload = [
            'recipient' => $user->name,
            'activity' => $activity->title,
            'eventTitle' => $event->title,
            'organizer' => $event->certificateOrganizerDisplay(),
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => $activity->workload_hours !== null ? (string) $activity->workload_hours : '',
            'frequency' => $frequency,
            'period' => CertificateDocument::period($event),
            'activityPeriod' => CertificateDocument::activityPeriod($activity),
            'place' => CertificateDocument::activityPlace($activity, $event),
            'modality' => CertificateDocument::modality($event),
            'matricula' => CertificateDocument::matricula($user),
            'presence' => CertificateDocument::presenceText(Certificate::TYPE_ACTIVITY, null, $frequency),
            'signatureIds' => $event->certificateSignatures->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function issuePresentation(User $user, Work $work, Event $event): bool
    {
        if (! $user->isParticipant()) {
            return false;
        }

        if ((int) $work->event_id !== (int) $event->id) {
            return false;
        }

        if (! $event->isFinalized()) {
            return false;
        }

        $presentation = $work->presentation;
        if (! $presentation || $presentation->attendance_status !== WorkPresentation::ATTENDANCE_APRESENTADO) {
            return false;
        }

        if (! $this->userIsMainPresenter($user, $work)) {
            return false;
        }

        if (trim((string) ($work->certificate_presentation_title ?? '')) === '') {
            return false;
        }

        $contentFingerprint = $this->presentationCertificateContentFingerprint($user, $work, $event);

        $existing = Certificate::query()
            ->where('user_id', $user->id)
            ->where('tipo', Certificate::TYPE_PRESENTATION)
            ->where('event_id', $event->id)
            ->whereNull('activity_id')
            ->where('work_id', $work->id)
            ->first();

        if ($existing && (string) ($existing->conteudo_hash ?? '') === $contentFingerprint) {
            return false;
        }

        if ($existing) {
            $code = filled($existing->codigo_validacao)
                ? (string) $existing->codigo_validacao
                : $this->uniqueValidationCode();
            $relativePath = filled($existing->arquivo_pdf)
                ? (string) $existing->arquivo_pdf
                : $this->pdfRelativePath($event->id, $code);
        } else {
            $code = $this->uniqueValidationCode();
            $relativePath = $this->pdfRelativePath($event->id, $code);
        }

        $hours = $work->certificate_presentation_hours ?? 2;
        $mainAuthor = WorkAuthor::query()
            ->where('work_id', $work->id)
            ->where('is_main_author', true)
            ->first();
        $recipientName = $mainAuthor?->author_name ?: $user->name;

        $data = [
            'recipientName' => $recipientName,
            'eventTitle' => $event->title,
            'organizer' => $event->certificateOrganizerDisplay(),
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => (string) $hours,
            'activityTitle' => null,
            'activityTypeLabel' => null,
            'workTitle' => $work->displayTitleForPresentationCertificate(),
            'issuedDate' => now()->format('d/m/Y'),
            'validationCode' => $code,
            'signatures' => $this->signatureBlocks($event),
        ];
        $data = array_merge($data, CertificateDocument::pdfFields($user, $event, $code, Certificate::TYPE_PRESENTATION));

        $this->writePdf('certificates.pdf.apresentacao', $data, $relativePath);

        if ($existing) {
            $existing->arquivo_pdf = $relativePath;
            $existing->codigo_validacao = $code;
            $existing->data_emissao = now();
            $existing->conteudo_hash = $contentFingerprint;
            $existing->save();

            return true;
        }

        Certificate::create([
            'user_id' => $user->id,
            'tipo' => Certificate::TYPE_PRESENTATION,
            'event_id' => $event->id,
            'activity_id' => null,
            'work_id' => $work->id,
            'arquivo_pdf' => $relativePath,
            'codigo_validacao' => $code,
            'conteudo_hash' => $contentFingerprint,
            'data_emissao' => now(),
        ]);

        $this->notifyCertificateAvailable($user, $event, Certificate::TYPE_PRESENTATION, null, $work->id);

        return true;
    }

    /**
     * Identifica o texto e metadados que entram no corpo do PDF de apresentação (exceto data de emissão e imagens das assinaturas).
     * Usado para não regenerar nem contar como "processado" quando nada mudou.
     */
    private function presentationCertificateContentFingerprint(User $user, Work $work, Event $event): string
    {
        $event->loadMissing('certificateSignatures');

        $hours = $work->certificate_presentation_hours ?? 2;
        $mainAuthor = WorkAuthor::query()
            ->where('work_id', $work->id)
            ->where('is_main_author', true)
            ->first();
        $recipientName = $mainAuthor?->author_name ?: $user->name;

        $payload = [
            'recipient' => $recipientName,
            'eventTitle' => $event->title,
            'organizer' => $event->certificateOrganizerDisplay(),
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => (string) $hours,
            'workTitle' => $work->displayTitleForPresentationCertificate(),
            'period' => CertificateDocument::period($event),
            'place' => CertificateDocument::place($event),
            'modality' => CertificateDocument::modality($event),
            'matricula' => CertificateDocument::matricula($user),
            'presence' => CertificateDocument::presence(Certificate::TYPE_PRESENTATION),
            'signatureIds' => $event->certificateSignatures->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Gerar/atualizar certificados de todas as atividades raiz deste evento (um lote cada).
     */
    public function batchActivitiesForEvent(Event $event): int
    {
        $activities = $event->activities()->whereNull('parent_activity_id')->orderBy('start_at')->get();
        $total = 0;
        foreach ($activities as $activity) {
            $total += $this->batchActivity($activity);
        }

        return $total;
    }

    public function batchPublications(Event $event): int
    {
        if (! $event->isFinalized() || ! $event->hasAnnalsPublication()) {
            return 0;
        }

        $event->loadMissing('certificateSignatures');
        $works = Work::query()
            ->where('event_id', $event->id)
            ->whereIn('status', Work::annalsCertificateStatuses())
            ->get();

        $count = 0;
        foreach ($works as $work) {
            $userId = $this->mainPresenterUserId($work);
            $user = $userId ? User::query()->find($userId) : null;
            if ($user && $this->issuePublication($user, $work, $event)) {
                $count++;
            }
        }

        return $count;
    }

    public function issuePublication(User $user, Work $work, Event $event): bool
    {
        if (! $user->isParticipant() || ! $event->isFinalized() || ! $event->hasAnnalsPublication()) {
            return false;
        }

        if ((int) $work->event_id !== (int) $event->id || ! $work->qualifiesForAnnalsCertificate()) {
            return false;
        }

        if (! $this->userIsMainPresenter($user, $work)) {
            return false;
        }

        $workTitle = trim($work->displayTitleForPresentationCertificate());
        if ($workTitle === '') {
            return false;
        }

        $hours = $event->certificate_publication_hours;
        $hoursText = ($hours === null || $hours === '') ? null : (string) $hours;
        $kind = $event->annals_kind === Event::ANNALS_KIND_EBOOK ? 'e-book' : 'anais';
        $fingerprint = $this->publicationFingerprint($user, $work, $event, $hoursText, $kind, $workTitle);

        $existing = Certificate::query()
            ->where('user_id', $user->id)
            ->where('tipo', Certificate::TYPE_PUBLICATION)
            ->where('event_id', $event->id)
            ->where('work_id', $work->id)
            ->first();

        if ($existing && (string) ($existing->conteudo_hash ?? '') === $fingerprint) {
            return false;
        }

        if ($existing && filled($existing->codigo_validacao)) {
            $code = (string) $existing->codigo_validacao;
            $relativePath = filled($existing->arquivo_pdf)
                ? (string) $existing->arquivo_pdf
                : $this->pdfRelativePath($event->id, $code);
        } else {
            $code = $this->uniqueValidationCode();
            $relativePath = $this->pdfRelativePath($event->id, $code);
        }

        $data = array_merge([
            'recipientName' => $user->name,
            'eventTitle' => $event->title,
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => $hoursText,
            'workTitle' => $workTitle,
            'publicationKind' => $kind,
            'issuedDate' => now()->format('d/m/Y'),
            'validationCode' => $code,
            'signatures' => $this->signatureBlocks($event),
        ], CertificateDocument::pdfFields($user, $event, $code, Certificate::TYPE_PUBLICATION));

        $this->writePdf('certificates.pdf.publicacao', $data, $relativePath);

        if ($existing) {
            $existing->arquivo_pdf = $relativePath;
            $existing->codigo_validacao = $code;
            $existing->conteudo_hash = $fingerprint;
            $existing->save();

            return true;
        }

        Certificate::create([
            'user_id' => $user->id,
            'recipient_name' => $user->name,
            'tipo' => Certificate::TYPE_PUBLICATION,
            'event_id' => $event->id,
            'work_id' => $work->id,
            'arquivo_pdf' => $relativePath,
            'codigo_validacao' => $code,
            'conteudo_hash' => $fingerprint,
            'data_emissao' => now(),
        ]);

        $this->notifyCertificateAvailable($user, $event, Certificate::TYPE_PUBLICATION, null, $work->id);

        return true;
    }

    private function publicationFingerprint(User $user, Work $work, Event $event, ?string $hours, string $kind, string $workTitle): string
    {
        $event->loadMissing('certificateSignatures');

        $payload = [
            'recipient' => $user->name,
            'workTitle' => $workTitle,
            'kind' => $kind,
            'eventTitle' => $event->title,
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => (string) ($hours ?? ''),
            'uploadedAt' => optional($event->annals_uploaded_at)->toDateString(),
            'period' => CertificateDocument::period($event),
            'place' => CertificateDocument::place($event),
            'modality' => CertificateDocument::modality($event),
            'matricula' => CertificateDocument::matricula($user),
            'signatureIds' => $event->certificateSignatures->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function eligibleReviewers(Event $event): Collection
    {
        $ids = Review::query()
            ->whereNotNull('submitted_at')
            ->whereHas('work', fn ($query) => $query->where('event_id', $event->id))
            ->distinct()
            ->pluck('reviewer_user_id');

        return User::query()
            ->whereIn('id', $ids)
            ->where('role', User::ROLE_REVIEWER)
            ->orderBy('name')
            ->get();
    }

    public function eligibleGuests(Event $event): Collection
    {
        return EventGuest::query()
            ->where('event_id', $event->id)
            ->whereIn('role_type', EventGuest::GUEST_CERTIFICATE_ROLES)
            ->orderBy('name')
            ->get();
    }

    public function eligibleOrganization(Event $event): Collection
    {
        return EventGuest::query()
            ->where('event_id', $event->id)
            ->whereIn('role_type', EventGuest::ORGANIZATION_CERTIFICATE_ROLES)
            ->orderBy('name')
            ->get();
    }

    public function eligibleCoauthors(Event $event): Collection
    {
        return WorkAuthor::query()
            ->where('is_main_author', false)
            ->where('author_name', '!=', '')
            ->whereHas('work', function ($query) use ($event) {
                $query->where('event_id', $event->id)
                    ->where('status', Work::STATUS_PRESENTED);
            })
            ->with('work')
            ->orderBy('author_name')
            ->get();
    }

    public function batchReviewers(Event $event): int
    {
        if (! $event->certificate_reviewer_enabled || ! $event->isFinalized()) {
            return 0;
        }

        $event->loadMissing('certificateSignatures');
        $count = 0;
        foreach ($this->eligibleReviewers($event) as $user) {
            if ($this->issueReviewer($user, $event)) {
                $count++;
            }
        }

        return $count;
    }

    public function batchGuests(Event $event): int
    {
        if (! $event->certificate_guest_enabled || ! $event->isFinalized()) {
            return 0;
        }

        $event->loadMissing('certificateSignatures');
        $count = 0;
        foreach ($this->eligibleGuests($event) as $guest) {
            if ($this->issueNamedGuest($guest, $event, Certificate::TYPE_GUEST)) {
                $count++;
            }
        }

        return $count;
    }

    public function batchOrganization(Event $event): int
    {
        if (! $event->certificate_organization_enabled || ! $event->isFinalized()) {
            return 0;
        }

        $event->loadMissing('certificateSignatures');
        $count = 0;
        foreach ($this->eligibleOrganization($event) as $guest) {
            if ($this->issueNamedGuest($guest, $event, Certificate::TYPE_ORGANIZATION)) {
                $count++;
            }
        }

        return $count;
    }

    public function batchCoauthors(Event $event): int
    {
        if (! $event->certificate_coauthor_enabled || ! $event->isFinalized()) {
            return 0;
        }

        $event->loadMissing('certificateSignatures');
        $count = 0;
        foreach ($this->eligibleCoauthors($event) as $author) {
            if ($this->issueCoauthor($author, $event)) {
                $count++;
            }
        }

        return $count;
    }

    public function issueReviewer(User $user, Event $event): bool
    {
        $hours = $this->optionalEventHours($event, Certificate::TYPE_REVIEWER);
        $body = 'Certificamos que <strong>'.e($user->name).'</strong> atuou como <strong>avaliador</strong> dos trabalhos submetidos ao evento <strong>'.e($event->title).'</strong>'
            .$this->hoursClause($hours)
            .', sob a organização de <strong>'.e($event->certificateOrganizerDisplay()).'</strong>, na instituição <strong>'.e($event->certificateInstitutionDisplay()).'</strong>.';

        return $this->persistOptional(
            $event,
            Certificate::TYPE_REVIEWER,
            'Certificado de avaliador',
            $body,
            ['user_id' => $user->id, 'event_guest_id' => null, 'work_author_id' => null, 'work_id' => null],
            [
                'user_id' => $user->id,
                'recipient_name' => $user->name,
                'recipient_role' => 'Avaliador',
            ],
            $user,
            $user->name,
            'Avaliador',
            $hours,
            null
        );
    }

    public function issueNamedGuest(EventGuest $guest, Event $event, string $tipo): bool
    {
        $role = EventGuest::roleTypeLabels()[$guest->role_type] ?? $guest->role_type;
        $hours = $this->optionalEventHours($event, $tipo);
        $function = e($role);

        if ($tipo === Certificate::TYPE_ORGANIZATION) {
            $title = 'Certificado de organização';
            $body = 'Certificamos que <strong>'.e($guest->name).'</strong> integrou a organização do evento <strong>'.e($event->title).'</strong>, na função de <strong>'.$function.'</strong>'
                .$this->hoursClause($hours)
                .', na instituição <strong>'.e($event->certificateInstitutionDisplay()).'</strong>.';
        } else {
            $title = 'Certificado de convidado';
            $body = 'Certificamos que <strong>'.e($guest->name).'</strong> participou como <strong>'.$function.'</strong> do evento <strong>'.e($event->title).'</strong>'
                .$this->hoursClause($hours)
                .', sob a organização de <strong>'.e($event->certificateOrganizerDisplay()).'</strong>, na instituição <strong>'.e($event->certificateInstitutionDisplay()).'</strong>.';
        }

        return $this->persistOptional(
            $event,
            $tipo,
            $title,
            $body,
            ['event_guest_id' => $guest->id],
            [
                'user_id' => null,
                'event_guest_id' => $guest->id,
                'recipient_name' => $guest->name,
                'recipient_role' => $role,
            ],
            null,
            $guest->name,
            $role,
            $hours,
            null
        );
    }

    public function issueCoauthor(WorkAuthor $author, Event $event): bool
    {
        $work = $author->work ?? $author->work()->first();
        if (! $work) {
            return false;
        }

        $user = $this->accountForCoauthor($author);
        $name = trim((string) $author->author_name);
        $hours = (string) ($work->certificate_presentation_hours ?? 2);
        $workTitle = nl2br(e($work->displayTitleForPresentationCertificate()));
        $body = 'Certificamos que <strong>'.e($name).'</strong> é coautor do trabalho intitulado <strong>'.$workTitle.'</strong>'
            .$this->hoursClause($hours)
            .', apresentado no evento <strong>'.e($event->title).'</strong>, por <strong>'.e($event->certificateInstitutionDisplay()).'</strong>.';

        return $this->persistOptional(
            $event,
            Certificate::TYPE_COAUTHOR,
            'Certificado de coautoria',
            $body,
            ['work_author_id' => $author->id],
            [
                'user_id' => $user?->id,
                'work_id' => $work->id,
                'work_author_id' => $author->id,
                'recipient_name' => $name,
                'recipient_role' => 'Coautor',
            ],
            $user,
            $name,
            'Coautor',
            $hours,
            $work->displayTitleForPresentationCertificate()
        );
    }

    /**
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $attributes
     */
    private function persistOptional(
        Event $event,
        string $tipo,
        string $documentTitle,
        string $bodyHtml,
        array $identity,
        array $attributes,
        ?User $notifyUser,
        string $recipientName,
        string $recipientRole,
        ?string $hours,
        ?string $workTitle
    ): bool {
        $query = Certificate::query()
            ->where('event_id', $event->id)
            ->where('tipo', $tipo);

        foreach ($identity as $column => $value) {
            if ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        $existing = $query->first();
        $fingerprint = $this->optionalFingerprint($event, $tipo, $recipientName, $recipientRole, $hours, $workTitle, $notifyUser);

        if ($existing && $existing->conteudo_hash === $fingerprint) {
            return false;
        }

        $code = $existing?->codigo_validacao ?: $this->uniqueValidationCode();
        $relativePath = $this->pdfRelativePath($event->id, $code);
        $viewData = array_merge([
            'title' => $documentTitle,
            'bodyHtml' => $bodyHtml,
            'issuedDate' => now()->format('d/m/Y'),
            'validationCode' => $code,
            'institution' => $event->certificateInstitutionDisplay(),
            'signatures' => $this->signatureBlocks($event),
        ], CertificateDocument::pdfFields($notifyUser, $event, $code, $tipo));

        $this->writePdf('certificates.pdf.opcional', $viewData, $relativePath);

        $payload = array_merge($attributes, [
            'tipo' => $tipo,
            'event_id' => $event->id,
            'arquivo_pdf' => $relativePath,
            'codigo_validacao' => $code,
            'conteudo_hash' => $fingerprint,
            'data_emissao' => now(),
        ]);

        if ($existing) {
            $existing->fill($payload);
            $existing->save();
        } else {
            Certificate::create($payload);
        }

        if ($notifyUser && ! $existing) {
            $this->notifyCertificateAvailable($notifyUser, $event, $tipo, null, $attributes['work_id'] ?? null);
        }

        return true;
    }

    private function optionalEventHours(Event $event, string $tipo): ?string
    {
        $value = match ($tipo) {
            Certificate::TYPE_REVIEWER => $event->certificate_reviewer_hours,
            Certificate::TYPE_GUEST => $event->certificate_guest_hours,
            Certificate::TYPE_ORGANIZATION => $event->certificate_organization_hours,
            default => null,
        };

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function hoursClause(?string $hours): string
    {
        if ($hours === null || $hours === '' || $hours === '-') {
            return '';
        }

        return ', com carga horária de <strong>'.e($hours).' hora(s)</strong>';
    }

    private function optionalFingerprint(
        Event $event,
        string $tipo,
        string $recipientName,
        string $recipientRole,
        ?string $hours,
        ?string $workTitle,
        ?User $user
    ): string {
        $event->loadMissing('certificateSignatures');

        $payload = [
            'tipo' => $tipo,
            'recipient' => $recipientName,
            'role' => $recipientRole,
            'eventTitle' => $event->title,
            'organizer' => $event->certificateOrganizerDisplay(),
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => (string) ($hours ?? ''),
            'workTitle' => (string) ($workTitle ?? ''),
            'period' => CertificateDocument::period($event),
            'place' => CertificateDocument::place($event),
            'modality' => CertificateDocument::modality($event),
            'matricula' => $user ? CertificateDocument::matricula($user) : null,
            'presence' => CertificateDocument::presence($tipo),
            'linkedUserId' => $user?->id,
            'signatureIds' => $event->certificateSignatures->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function accountForCoauthor(WorkAuthor $author): ?User
    {
        if ($author->user_id) {
            $linked = User::query()->find($author->user_id);
            if ($linked) {
                return $linked;
            }
        }

        $email = strtolower(trim((string) $author->author_email));
        if ($email === '') {
            return null;
        }

        return User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
    }

    /**
     * Usuário que recebe o certificado de apresentação: apenas o autor principal (quem submeteu / is_main_author).
     */
    private function mainPresenterUserId(Work $work): ?int
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

    private function userIsMainPresenter(User $user, Work $work): bool
    {
        $mainId = $this->mainPresenterUserId($work);

        return $mainId !== null && (int) $user->id === $mainId;
    }

    private function notifyCertificateAvailable(User $user, Event $event, string $tipo, ?int $activityId, ?int $workId): void
    {
        $certificate = Certificate::query()
            ->where('user_id', $user->id)
            ->where('tipo', $tipo)
            ->where('event_id', $event->id)
            ->when($activityId !== null, fn ($q) => $q->where('activity_id', $activityId))
            ->when($activityId === null, fn ($q) => $q->whereNull('activity_id'))
            ->when($workId !== null, fn ($q) => $q->where('work_id', $workId))
            ->when($workId === null, fn ($q) => $q->whereNull('work_id'))
            ->latest('id')
            ->first();

        if (! $certificate) {
            return;
        }

        $certificate->setRelation('event', $event);
        EventMailer::notify($user, new CertificateAvailable($certificate));
    }

    private function uniqueValidationCode(): string
    {
        do {
            $code = strtoupper(Str::random(14));
        } while (Certificate::where('codigo_validacao', $code)->exists());

        return $code;
    }

    private function pdfRelativePath(int $eventId, string $code): string
    {
        return 'certificates/'.$eventId.'/'.$code.'.pdf';
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function writePdf(string $view, array $viewData, string $relativePath): void
    {
        $pdf = Pdf::loadView($view, $viewData);
        $pdf->setPaper('a4', 'landscape');

        $binary = $pdf->output();

        if ($this->objects->isConfigured()) {
            $bucket = (string) config('services.supabase.bucket_certificates');
            $this->objects->upload($bucket, $relativePath, $binary, 'application/pdf');

            return;
        }

        Storage::disk('public')->makeDirectory(dirname($relativePath));
        Storage::disk('public')->put($relativePath, $binary);
    }

    /**
     * @return list<array{nome: string, cargo: string, src: ?string}>
     */
    private function signatureBlocks(Event $event): array
    {
        $blocks = [];
        foreach ($event->certificateSignatures as $sig) {
            $blocks[] = [
                'nome' => $sig->nome,
                'cargo' => $sig->cargo,
                'src' => $this->signatureImageDataUri($sig->imagem_assinatura),
            ];
        }

        return $blocks;
    }

    private function signatureImageDataUri(?string $relativePath): ?string
    {
        if (! $relativePath) {
            return null;
        }

        $binary = null;
        $mime = null;

        if (Storage::disk('public')->exists($relativePath)) {
            $full = Storage::disk('public')->path($relativePath);
            if (is_readable($full)) {
                $mime = @mime_content_type($full) ?: 'image/png';
                $binary = (string) file_get_contents($full);
            }
        } elseif ($this->objects->isConfigured()) {
            $response = $this->objects->fetch(
                (string) config('services.supabase.bucket_signatures'),
                $relativePath
            );
            if ($response && $response->successful()) {
                $binary = $response->body();
                $mime = $response->header('Content-Type')
                    ?: $this->guessImageMime(pathinfo($relativePath, PATHINFO_EXTENSION));
            }
        }

        if ($binary === null || $mime === null) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function guessImageMime(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}
