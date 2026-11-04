<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $table = 'certificados';

    public const TYPE_PARTICIPATION = 'participacao';

    public const TYPE_ACTIVITY = 'atividade';

    public const TYPE_PRESENTATION = 'apresentacao';

    public const TYPE_REVIEWER = 'avaliador';

    public const TYPE_GUEST = 'convidado';

    public const TYPE_ORGANIZATION = 'organizacao';

    public const TYPE_COAUTHOR = 'coautor';

    public const TYPE_PUBLICATION = 'publicacao';

    protected $fillable = [
        'user_id',
        'recipient_name',
        'recipient_role',
        'tipo',
        'event_id',
        'activity_id',
        'work_id',
        'event_guest_id',
        'work_author_id',
        'arquivo_pdf',
        'codigo_validacao',
        'conteudo_hash',
        'data_emissao',
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function eventGuest(): BelongsTo
    {
        return $this->belongsTo(EventGuest::class);
    }

    public function workAuthor(): BelongsTo
    {
        return $this->belongsTo(WorkAuthor::class);
    }

    public function recipientDisplayName(): string
    {
        $stored = trim((string) ($this->recipient_name ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        return $this->user?->name ?? 'Não identificado';
    }

    /**
     * Normaliza o texto digitado pelo visitante (maiúsculas, só letras/números).
     */
    public static function normalizeValidationInput(?string $value): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
    }

    public static function typeLabel(string $tipo): string
    {
        return match ($tipo) {
            self::TYPE_PARTICIPATION => 'Participação no evento',
            self::TYPE_ACTIVITY => 'Participação em atividade',
            self::TYPE_PRESENTATION => 'Apresentação de trabalho',
            self::TYPE_REVIEWER => 'atuação como avaliador',
            self::TYPE_GUEST => 'participação como convidado',
            self::TYPE_ORGANIZATION => 'participação na organização',
            self::TYPE_COAUTHOR => 'coautoria de trabalho',
            self::TYPE_PUBLICATION => 'publicação em anais',
            default => $tipo,
        };
    }

    public static function certificateExists(
        int $userId,
        string $tipo,
        int $eventId,
        ?int $activityId,
        ?int $workId
    ): bool {
        return static::query()
            ->where('user_id', $userId)
            ->where('tipo', $tipo)
            ->where('event_id', $eventId)
            ->when($activityId !== null, fn ($q) => $q->where('activity_id', $activityId))
            ->when($activityId === null, fn ($q) => $q->whereNull('activity_id'))
            ->when($workId !== null, fn ($q) => $q->where('work_id', $workId))
            ->when($workId === null, fn ($q) => $q->whereNull('work_id'))
            ->exists();
    }
}
