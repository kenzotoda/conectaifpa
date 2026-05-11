<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventPresence;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkAuthor;
use App\Models\WorkPresentation;
use Barryvdh\DomPDF\Facade\Pdf;
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
            ->exists();

        if (! $present) {
            return false;
        }

        if (Certificate::certificateExists($user->id, Certificate::TYPE_PARTICIPATION, $event->id, null, null)) {
            return false;
        }

        $code = $this->uniqueValidationCode();
        $relativePath = $this->pdfRelativePath($event->id, $code);

        $hours = $event->certificate_total_hours;
        $data = [
            'recipientName' => $user->name,
            'eventTitle' => $event->title,
            'organizer' => $event->certificateOrganizerDisplay(),
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => $hours !== null ? (string) $hours : '—',
            'activityTitle' => null,
            'activityTypeLabel' => null,
            'workTitle' => null,
            'issuedDate' => now()->format('d/m/Y'),
            'validationCode' => $code,
            'signatures' => $this->signatureBlocks($event),
        ];

        $this->writePdf('certificates.pdf.participacao', $data, $relativePath);

        Certificate::create([
            'user_id' => $user->id,
            'tipo' => Certificate::TYPE_PARTICIPATION,
            'event_id' => $event->id,
            'activity_id' => null,
            'work_id' => null,
            'arquivo_pdf' => $relativePath,
            'codigo_validacao' => $code,
            'data_emissao' => now(),
        ]);

        return true;
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

        $present = $activity->activityPresences()
            ->where('user_id', $user->id)
            ->where('presente', true)
            ->exists();

        if (! $present) {
            return false;
        }

        if (Certificate::certificateExists($user->id, Certificate::TYPE_ACTIVITY, $event->id, $activity->id, null)) {
            return false;
        }

        $code = $this->uniqueValidationCode();
        $relativePath = $this->pdfRelativePath($event->id, $code);

        $hours = $activity->workload_hours;
        $typeLabel = Activity::typeLabels()[$activity->type] ?? $activity->type;

        $data = [
            'recipientName' => $user->name,
            'eventTitle' => $event->title,
            'organizer' => $event->certificateOrganizerDisplay(),
            'institution' => $event->certificateInstitutionDisplay(),
            'hours' => $hours !== null ? (string) $hours : '—',
            'activityTitle' => $activity->title,
            'activityTypeLabel' => $typeLabel,
            'workTitle' => null,
            'issuedDate' => now()->format('d/m/Y'),
            'validationCode' => $code,
            'signatures' => $this->signatureBlocks($event),
        ];

        $this->writePdf('certificates.pdf.atividade', $data, $relativePath);

        Certificate::create([
            'user_id' => $user->id,
            'tipo' => Certificate::TYPE_ACTIVITY,
            'event_id' => $event->id,
            'activity_id' => $activity->id,
            'work_id' => null,
            'arquivo_pdf' => $relativePath,
            'codigo_validacao' => $code,
            'data_emissao' => now(),
        ]);

        return true;
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

        $this->writePdf('certificates.pdf.apresentacao', $data, $relativePath);

        if ($existing) {
            $existing->arquivo_pdf = $relativePath;
            $existing->codigo_validacao = $code;
            $existing->data_emissao = now();
            $existing->conteudo_hash = $contentFingerprint;
            $existing->save();
        } else {
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
        }

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
