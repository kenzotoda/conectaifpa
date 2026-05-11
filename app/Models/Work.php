<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Work extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'submitter_user_id',
        'title',
        'abstract',
        'work_type',
        'file_path',
        'status',
        'final_score',
        'final_score_is_manual',
        'decision_at',
        'published_in_annals_at',
        'annals_url',
        'annals_note',
        'decision_by',
        'author_feedback',
        'coordinator_feedback_file_path',
        'withdrawal_requested_at',
        'withdrawal_requested_reason',
        'workflow_note',
        'deleted_by_coordinator_id',
        'correction_requested_at',
        'correction_deadline_at',
        'correction_submitted_at',
        'correction_change_log',
        'correction_status',
        'final_version_submitted_at',
        'final_version_validated_at',
        'final_version_validated_by',
        'final_version_file_path',
        'final_version_source',
        'certificate_presentation_hours',
        'certificate_presentation_title',
    ];

    protected $casts = [
        'final_score' => 'decimal:2',
        'final_score_is_manual' => 'boolean',
        'decision_at' => 'datetime',
        'published_in_annals_at' => 'datetime',
        'withdrawal_requested_at' => 'datetime',
        'correction_requested_at' => 'datetime',
        'correction_deadline_at' => 'datetime',
        'correction_submitted_at' => 'datetime',
        'final_version_submitted_at' => 'datetime',
        'final_version_validated_at' => 'datetime',
        'certificate_presentation_hours' => 'decimal:2',
    ];

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    /** Aprovado pelo coordenador na decisão final (fluxo pós-avaliação). Antigo `accepted`. */
    public const STATUS_APPROVED_FINAL = 'approved_final';

    public const STATUS_ACCEPTED_WITH_CORRECTIONS = 'accepted_with_corrections';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CONFLICT = 'conflict';

    public const STATUS_WITHDRAWAL_REQUESTED = 'withdrawal_requested';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_DESK_REJECTED = 'desk_rejected';

    /** Origem do arquivo considerado versão final na decisão de aceite (`direct` ou `corrected`). */
    public const FINAL_VERSION_SOURCE_DIRECT = 'direct';

    public const FINAL_VERSION_SOURCE_CORRECTED = 'corrected';

    public const STATUS_FINAL_VALIDATED = 'final_validated';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PRESENTED = 'presented';

    public const STATUS_ABSENT = 'absent';

    /** Publicado nos anais (catálogo). */
    public const STATUS_PUBLISHED_ANNALS = 'published_annals';

    public const TYPE_RESUMO_SIMPLES = 'resumo_simples';

    public const TYPE_RESUMO_EXPANDIDO = 'resumo_expandido';

    public const TYPE_ARTIGO_COMPLETO = 'artigo_completo';

    public const TYPE_RELATO_EXPERIENCIA = 'relato_experiencia';

    public const TYPE_POSTER = 'poster';

    public const TYPE_ESTUDO_CASO = 'estudo_caso';

    public const TYPE_ENSAIO_ACADEMICO = 'ensaio_academico';

    public const TYPE_REVISAO_LITERATURA = 'revisao_literatura';

    public const TYPE_RELATORIO_TECNICO = 'relatorio_tecnico';

    public const TYPE_TCC_MONOGRAFIA = 'tcc_monografia';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_APPROVED_FINAL,
            self::STATUS_ACCEPTED_WITH_CORRECTIONS,
            self::STATUS_REJECTED,
            self::STATUS_CONFLICT,
            self::STATUS_WITHDRAWAL_REQUESTED,
            self::STATUS_WITHDRAWN,
            self::STATUS_CANCELLED,
            self::STATUS_DESK_REJECTED,
            self::STATUS_FINAL_VALIDATED,
            self::STATUS_SCHEDULED,
            self::STATUS_PRESENTED,
            self::STATUS_ABSENT,
            self::STATUS_PUBLISHED_ANNALS,
        ];
    }

    public static function workTypeOptions(): array
    {
        return array_keys(self::workTypeLabels());
    }

    public static function workTypeLabels(): array
    {
        return [
            self::TYPE_RESUMO_SIMPLES => 'Resumo Simples',
            self::TYPE_RESUMO_EXPANDIDO => 'Resumo Expandido',
            self::TYPE_ARTIGO_COMPLETO => 'Artigo Completo',
            self::TYPE_RELATO_EXPERIENCIA => 'Relato de Experiência',
            self::TYPE_POSTER => 'Pôster (Painel)',
            self::TYPE_ESTUDO_CASO => 'Estudo de Caso',
            self::TYPE_ENSAIO_ACADEMICO => 'Ensaio Acadêmico',
            self::TYPE_REVISAO_LITERATURA => 'Revisão de Literatura',
            self::TYPE_RELATORIO_TECNICO => 'Relatório Técnico',
            self::TYPE_TCC_MONOGRAFIA => 'TCC/Monografia',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_SUBMITTED => 'Submetido',
            self::STATUS_UNDER_REVIEW => 'Em avaliação',
            self::STATUS_APPROVED_FINAL => 'Aceito (valor enviado pelo formulário)',
            self::STATUS_ACCEPTED_WITH_CORRECTIONS => 'Correção necessária',
            self::STATUS_REJECTED => 'Reprovado',
            self::STATUS_CONFLICT => 'Conflito entre pareceres',
            self::STATUS_WITHDRAWAL_REQUESTED => 'Retirada solicitada',
            self::STATUS_WITHDRAWN => 'Retirado',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_DESK_REJECTED => 'Rejeitado na triagem',
            self::STATUS_FINAL_VALIDATED => 'Aprovado — versão final definida',
            self::STATUS_SCHEDULED => 'Agendado',
            self::STATUS_PRESENTED => 'Apresentado',
            self::STATUS_ABSENT => 'Ausente',
            self::STATUS_PUBLISHED_ANNALS => 'Publicado nos anais',
        ];
    }

    /**
     * Versão corrigida já enviada pelo autor; avaliadores estão na segunda rodada (somente aceitar/rejeitar).
     */
    public function isAwaitingCorrectionReReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW
            && $this->correction_submitted_at !== null;
    }

    /**
     * Autor ainda não enviou a versão corrigida após decisão "Aceito com correções".
     */
    public function isAwaitingAuthorCorrection(): bool
    {
        return $this->status === self::STATUS_ACCEPTED_WITH_CORRECTIONS
            && $this->correction_submitted_at === null;
    }

    /**
     * Estados em que não há nova decisão da coordenação (aceite/reprovação/correções).
     */
    public function coordinatorDecisionIsTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_FINAL_VALIDATED,
            self::STATUS_SCHEDULED,
            self::STATUS_PRESENTED,
            self::STATUS_ABSENT,
            self::STATUS_PUBLISHED_ANNALS,
            self::STATUS_REJECTED,
            self::STATUS_DESK_REJECTED,
            self::STATUS_WITHDRAWN,
            self::STATUS_CANCELLED,
        ], true);
    }

    /**
     * A coordenação ainda pode registrar uma decisão (primeira rodada, conflito ou pós-reavaliação da correção).
     */
    public function coordinatorCanRegisterNewDecision(): bool
    {
        if ($this->coordinatorDecisionIsTerminal()) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_CONFLICT,
            self::STATUS_ACCEPTED_WITH_CORRECTIONS,
        ], true);
    }

    public function statusLabel(): string
    {
        if ($this->isAwaitingCorrectionReReview()) {
            return 'Reavaliação das correções';
        }

        return self::statusLabels()[$this->status] ?? str_replace('_', ' ', ucfirst((string) $this->status));
    }

    /**
     * O participante ainda pode abrir nova submissão neste evento (falta algum tipo de trabalho não usado).
     */
    public static function submitterCanSubmitAnotherWork(Event $event, int $userId): bool
    {
        if (! $event->acceptsSubmissions()) {
            return false;
        }

        $pool = $event->acceptedWorkTypes()->values()->all();
        if (empty($pool)) {
            $pool = self::workTypeOptions();
        }

        $used = self::query()
            ->where('event_id', $event->id)
            ->where('submitter_user_id', $userId)
            ->pluck('work_type')
            ->unique()
            ->all();

        return count(array_diff($pool, $used)) > 0;
    }

    /**
     * Título para listas e telas quando o aluno não informou título na submissão.
     * O sufixo #id aparece apenas para visão do coordenador (controle interno).
     */
    public function listTitle(bool $includeCoordinatorNumber = false): string
    {
        $raw = trim((string) ($this->title ?? ''));
        if ($raw !== '') {
            return $raw;
        }

        $typeLabel = self::workTypeLabels()[$this->work_type] ?? $this->work_type;
        $base = 'Submissão — '.$typeLabel;

        return $includeCoordinatorNumber ? $base.' #'.$this->id : $base;
    }

    /**
     * Rótulo curto para tabelas/cabeçalhos: sem o prefixo "Submissão —" quando só há tipo.
     * Evita redundância com colunas já nomeadas "Submissão" ou "Trabalho".
     */
    public function listTitleCompact(bool $includeCoordinatorNumber = false): string
    {
        $raw = trim((string) ($this->title ?? ''));
        if ($raw !== '') {
            return $raw;
        }

        $typeLabel = self::workTypeLabels()[$this->work_type] ?? $this->work_type;

        return $includeCoordinatorNumber ? $typeLabel.' #'.$this->id : $typeLabel;
    }

    /**
     * Texto do trabalho no certificado de apresentação e na validação: preferencialmente uma linha, definido na tela de certificados (seção 4),
     * depois o título da submissão; se vazios, apenas o rótulo do tipo (sem prefixo "Submissão —").
     */
    public function displayTitleForPresentationCertificate(): string
    {
        foreach ([$this->certificate_presentation_title, $this->title] as $candidate) {
            $t = trim((string) ($candidate ?? ''));
            if ($t !== '') {
                return $t;
            }
        }

        return $this->listTitleCompact(false);
    }

    /**
     * Compatibilidade com blades/views compiladas antigas. Não pré-preenche com título da submissão — só repete o valor já salvo para o certificado.
     */
    public function defaultCertificatePresentationTitleForCoordinatorForm(): string
    {
        return trim((string) ($this->certificate_presentation_title ?? ''));
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitter_user_id');
    }

    public function decisionByUser()
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function finalVersionValidatedByUser()
    {
        return $this->belongsTo(User::class, 'final_version_validated_by');
    }

    public function deletedByCoordinator()
    {
        return $this->belongsTo(User::class, 'deleted_by_coordinator_id');
    }

    public function authors()
    {
        return $this->hasMany(WorkAuthor::class)->orderBy('author_order');
    }

    public function reviewers()
    {
        return $this->belongsToMany(User::class, 'work_reviewers', 'work_id', 'reviewer_user_id')
            ->withPivot(['status', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function reviewerAssignments()
    {
        return $this->hasMany(WorkReviewer::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function versions()
    {
        return $this->hasMany(WorkVersion::class)->orderByDesc('version_number');
    }

    /**
     * Trabalhos já aceitos na decisão final (incluindo fluxo com correção / versão final definida)
     * e estado posterior — aptos a ter data, horário e local da apresentação definidos pelo coordenador.
     */
    public function scopeEligibleForCoordinatorPresentationScheduling(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_FINAL_VALIDATED,
            self::STATUS_SCHEDULED,
            self::STATUS_PRESENTED,
            self::STATUS_ABSENT,
            self::STATUS_PUBLISHED_ANNALS,
        ]);
    }

    public function presentation()
    {
        return $this->hasOne(WorkPresentation::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Caminho no storage do arquivo oficial (anais/apresentações) após decisão positiva da coordenação.
     */
    public function canonicalFinalDocumentPath(): ?string
    {
        if (filled($this->final_version_file_path) && $this->final_version_validated_at !== null) {
            return (string) $this->final_version_file_path;
        }

        return $this->file_path ?: null;
    }

    public function canonicalFinalVersionDescription(): string
    {
        $hasOfficial = filled($this->final_version_file_path) && $this->final_version_validated_at !== null;

        if (! $hasOfficial) {
            return match ($this->final_version_source) {
                self::FINAL_VERSION_SOURCE_CORRECTED => 'Versão final: arquivo corrigido enviado para reavaliação e aprovado pelo coordenador.',
                self::FINAL_VERSION_SOURCE_DIRECT => 'Versão final: arquivo da submissão original aceito diretamente.',
                default => 'Versão final: arquivo atualmente registrado na submissão.',
            };
        }

        return match ($this->final_version_source) {
            self::FINAL_VERSION_SOURCE_CORRECTED => 'Versão final: arquivo oficial enviado pela coordenação após a reavaliação (armazenamento dedicado de versões oficiais).',
            self::FINAL_VERSION_SOURCE_DIRECT => 'Versão final: arquivo oficial enviado pela coordenação na aceitação direta (armazenamento dedicado de versões oficiais).',
            default => 'Versão final: arquivo oficial enviado pela coordenação (armazenamento dedicado de versões oficiais).',
        };
    }
}
