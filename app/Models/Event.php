<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Event extends Model
{
    public const FIXED_CAMPUS = 'INSTITUTO FEDERAL DE EDUCACAO, CIENCIA E TECNOLOGIA DO PARA - CAMPUS BELEM';

    public const FIXED_BUILDING = 'INSTITUTO FEDERAL DE EDUCACAO, CIENCIA E TECNOLOGIA DO PARA - CAMPUS BELEM';

    public const FIXED_VENUE = 'INSTITUTO FEDERAL DE EDUCACAO, CIENCIA E TECNOLOGIA DO PARA - CAMPUS BELEM';

    public const FIXED_ADDRESS = 'Av. Almirante Barroso, 1155 - Marco, Belém - PA, CEP 66093-020';

    public const FIXED_LOCATION_DETAILS = 'Local fixo do evento no INSTITUTO FEDERAL DE EDUCACAO, CIENCIA E TECNOLOGIA DO PARA - CAMPUS BELEM.';

    public const FIXED_MAP_EMBED_URL = 'https://maps.google.com/maps?q=IFPA%20Campus%20Bel%C3%A9m&t=&z=15&ie=UTF8&iwloc=&output=embed';

    // Se não especificar o nome da tabela no Model, ele procura a tabela com o nome do Model no plural.
    protected $table = 'events';

    // Sempre que acessarmos $event->items, o Laravel vai devolver um array (não uma string JSON),
    // graças a esse cast. Isso facilita o uso e evita ter que usar json_decode() manualmente.
    //
    // Já o atributo 'date' será automaticamente convertido para um objeto Carbon (classe de datas do Laravel),
    // permitindo usar métodos como format(), addDays(), diffForHumans(), etc., de forma simples.
    protected $casts = [
        'target_audience' => 'array',
        'prerequisites' => 'array',
        'accepts_submissions' => 'boolean',
        'reviewers_min_per_work' => 'integer',
        'reviewers_max_per_work' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'datetime_registration' => 'datetime',
        'submission_deadline_at' => 'datetime',
        'review_deadline_at' => 'datetime',
        'finalized_at' => 'datetime',
        'satisfaction_invited_at' => 'datetime',
        'annals_uploaded_at' => 'datetime',
        'certificate_total_hours' => 'decimal:2',
        'certificate_reviewer_hours' => 'decimal:2',
        'certificate_guest_hours' => 'decimal:2',
        'certificate_organization_hours' => 'decimal:2',
        'certificate_publication_hours' => 'decimal:2',
        'certificate_reviewer_enabled' => 'boolean',
        'certificate_guest_enabled' => 'boolean',
        'certificate_organization_enabled' => 'boolean',
        'certificate_coauthor_enabled' => 'boolean',
        'edicao' => 'integer',
    ];

    public const ANNALS_KIND_ANAIS = 'anais';

    public const ANNALS_KIND_EBOOK = 'ebook';

    // Tudo que foi enviado pelo POST pode ser atualizado, sem restrição.
    protected $guarded = [];

    // DELETA A IMAGEM DO EVENTO DO BUCKET LOCAL OU PRODUÇÃO QUANDO O EVENTO FOR DELETADO ($event->delete();)
    protected static function booted()
    {
        static::deleting(function ($event) {
            $bucket = config('services.supabase.bucket_events');

            if ($event->image) {
                $path = "events/{$event->image}";
                Http::withHeaders([
                    'Authorization' => 'Bearer '.config('services.supabase.service_role'),
                    'apikey' => config('services.supabase.service_role'),
                ])->delete(
                    config('services.supabase.url').
                    "/storage/v1/object/$bucket/$path"
                );
            }

            if ($event->annals_storage_path) {
                $annalsBucket = config('services.supabase.bucket_annals');
                Http::withHeaders([
                    'Authorization' => 'Bearer '.config('services.supabase.service_role'),
                    'apikey' => config('services.supabase.service_role'),
                ])->delete(
                    config('services.supabase.url').
                    "/storage/v1/object/{$annalsBucket}/{$event->annals_storage_path}"
                );
            }

            $attachmentsBucket = config('services.supabase.bucket_attachments');
            foreach ($event->documents()->get() as $document) {
                Http::withHeaders([
                    'Authorization' => 'Bearer '.config('services.supabase.service_role'),
                    'apikey' => config('services.supabase.service_role'),
                ])->delete(
                    config('services.supabase.url').
                    "/storage/v1/object/{$attachmentsBucket}/{$document->storage_path}"
                );
            }
        });
    }

    // RELACIONAMENTOS

    // Retorna o dono do evento. || (1:N)
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    // Retorna as novidades do evento (mais recentes primeiro).
    public function eventNews()
    {
        return $this->hasMany(EventNews::class)->orderBy('created_at', 'desc');
    }

    public function sponsors()
    {
        return $this->hasMany(EventSponsor::class)->orderBy('id');
    }

    public function documents()
    {
        return $this->hasMany(EventDocument::class)
            ->orderBy('display_order')
            ->orderByDesc('created_at');
    }

    public function activities()
    {
        return $this->hasMany(Activity::class)->orderBy('start_at');
    }

    public function guests()
    {
        return $this->hasMany(EventGuest::class)->orderBy('name');
    }

    // Retorna os usuários participantes do evento. || (N:N)
    // Aqui não existe uma chave estrangeira direta nas tabelas users e events que resolva essa relação.
    // Então é necessário criar uma tabela intermediária (chamada de tabela pivô).
    // Por convenção do Laravel, o nome da tabela pivô será event_user, seguindo ordem alfabética das tabelas relacionadas.
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    // Trabalhos submetidos ao evento.
    public function works()
    {
        return $this->hasMany(Work::class);
    }

    public function eventPresences()
    {
        return $this->hasMany(EventPresence::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'event_id');
    }

    /**
     * Assinaturas exibidas nos certificados deste evento (ordem em pivot sort_order).
     */
    public function certificateSignatures()
    {
        return $this->belongsToMany(Signature::class, 'assinatura_event', 'event_id', 'assinatura_id')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /** Participantes (perfil aluno) inscritos no evento. Útil para presença e certificados. */
    public function satisfactionResponses()
    {
        return $this->hasMany(EventSatisfactionResponse::class);
    }

    public function participantUsers()
    {
        return $this->users()->where('users.role', User::ROLE_PARTICIPANT)->orderBy('users.name');
    }

    public function certificateOrganizerDisplay(): string
    {
        return trim((string) ($this->certificate_organizer ?: $this->coordinator_name)) ?: 'Coordenação';
    }

    public function certificateInstitutionDisplay(): string
    {
        return trim((string) ($this->certificate_institution ?: $this->campus)) ?: self::FIXED_CAMPUS;
    }

    // Tipos de trabalho aceitos no evento.
    public function acceptedWorkTypes()
    {
        return DB::table('event_work_types')
            ->where('event_id', $this->id)
            ->pluck('work_type');
    }

    // REGRAS DE NEGÓCIO
    /** Valor do `<select>` quando o usuário escolhe digitar manualmente (categoria / tipo científico). */
    public const SELECT_OTHER_VALUE = '__other__';

    public const CATEGORY_OPTIONS = [
        'Tecnologia',
        'Negócios',
        'Design',
        'Ciências',
        'Humanas',
        'Saúde',
        'Idiomas',
        'Artes',
    ];

    public const NATURE_OPTIONS = [
        'cientifico' => 'Científico',
        'educativo' => 'Educativo',
        'tecnico' => 'Técnico',
        'cultural' => 'Cultural',
        'artistico' => 'Artístico',
        'assistencial' => 'Assistencial',
        'civico' => 'Cívico',
        'desportivo' => 'Desportivo',
        'social' => 'Social',
    ];

    public const SCOPE_OPTIONS = [
        'local' => 'Local',
        'regional' => 'Regional',
        'nacional' => 'Nacional',
        'internacional' => 'Internacional',
    ];

    public const PERIODICITY_UNIQUE = 'unico';

    public const PERIODICITY_OPTIONS = [
        self::PERIODICITY_UNIQUE => 'Único',
        'regular' => 'Regular',
        'sazonal' => 'Sazonal',
        'esporadico' => 'Esporádico',
    ];

    public const ACCESS_FREE = 'gratuito';

    public const EVENT_TYPE_OPTIONS = [
        'Congresso',
        'Simpósio',
        'Seminário',
        'Jornada Acadêmica',
        'Colóquio',
        'Webinar',
        'Workshop',
        'Mesa-redonda',
        'Fórum',
        'Conferência',
        'Encontro Acadêmico',
        'Escola de Verão/Inverno',
        'Curso/Minicurso',
    ];

    public function natureLabel(): ?string
    {
        return self::NATURE_OPTIONS[$this->natureza] ?? null;
    }

    public function scopeLabel(): ?string
    {
        return self::SCOPE_OPTIONS[$this->abrangencia] ?? null;
    }

    public function periodicityLabel(): ?string
    {
        return self::PERIODICITY_OPTIONS[$this->periodicidade] ?? null;
    }

    public function periodicityDisplay(): ?string
    {
        $label = $this->periodicityLabel();
        if ($label === null) {
            return null;
        }

        if ($this->periodicidade !== self::PERIODICITY_UNIQUE && $this->edicao) {
            return $label.', edição '.$this->edicao;
        }

        return $label;
    }

    public function accessLabel(): string
    {
        return 'Gratuito';
    }

    /** Soma usada na faixa de porte: vagas internas e externas, ou a capacidade total quando ainda não há divisão. */
    public function audienceCapacityTotal(): int
    {
        if ($this->hasAudienceQuotas()) {
            return (int) $this->internal_capacity + (int) $this->external_capacity;
        }

        return (int) $this->capacity;
    }

    /**
     * Faixas da NBR 16004: micro até 100, pequeno 101 a 500, médio 501 a 5.000, grande 5.001 a 40.000, mega acima disso.
     */
    public function porteKey(): string
    {
        $total = $this->audienceCapacityTotal();

        return match (true) {
            $total <= 100 => 'micro',
            $total <= 500 => 'pequeno',
            $total <= 5000 => 'medio',
            $total <= 40000 => 'grande',
            default => 'mega',
        };
    }

    public function porteLabel(): string
    {
        return match ($this->porteKey()) {
            'micro' => 'Microevento',
            'pequeno' => 'Pequeno porte',
            'medio' => 'Médio porte',
            'grande' => 'Grande porte',
            default => 'Megaevento',
        };
    }

    public function acceptsSubmissions(): bool
    {
        return (bool) $this->accepts_submissions;
    }

    /**
     * Prazo de submissão de trabalhos encerrado (usa apenas submission_deadline_at).
     * Sem prazo definido: retorna false (avaliações não liberadas por este critério).
     */
    public function workSubmissionDeadlinePassed(): bool
    {
        if ($this->submission_deadline_at === null) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo(Carbon::parse($this->submission_deadline_at));
    }

    /**
     * Aluno ainda pode submeter ou editar trabalho: há submission_deadline_at e está no futuro.
     */
    public function workSubmissionWindowOpen(): bool
    {
        if ($this->submission_deadline_at === null) {
            return false;
        }

        return Carbon::now()->lessThan(Carbon::parse($this->submission_deadline_at));
    }

    public function hasReviewDeadline(): bool
    {
        return $this->review_deadline_at !== null;
    }

    /** Prazo próprio de avaliação já passou. Evento antigo, sem esse prazo, não usa esta trava. */
    public function reviewDeadlinePassed(): bool
    {
        if (! $this->hasReviewDeadline()) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo(Carbon::parse($this->review_deadline_at));
    }

    /**
     * Avaliadores podem enviar parecer: submissões encerradas e a janela ainda aberta.
     * Com prazo próprio, fecha em review_deadline_at. Sem ele, fecha no fim do evento.
     * Vale para a primeira avaliação e para a reavaliação após correção.
     */
    public function reviewersEvaluationWindowOpen(): bool
    {
        if (! $this->workSubmissionDeadlinePassed()) {
            return false;
        }

        if ($this->hasReviewDeadline()) {
            return ! $this->reviewDeadlinePassed();
        }

        return ! $this->calendarEnded();
    }

    public function evaluationWindowClosedMessage(): string
    {
        if ($this->hasReviewDeadline()) {
            return 'O prazo de avaliação deste evento já encerrou. Não é mais possível enviar avaliações (incluindo reavaliação após correções).';
        }

        return 'O período do evento já encerrou. Não é mais possível enviar avaliações (incluindo reavaliação após correções).';
    }

    public function minReviewersPerWork(): int
    {
        $value = (int) ($this->reviewers_min_per_work ?? 0);

        return $value > 0 ? $value : 1;
    }

    public function maxReviewersPerWork(): int
    {
        $min = $this->minReviewersPerWork();
        $max = (int) ($this->reviewers_max_per_work ?? 0);

        return $max > 0 ? max($max, $min) : $min;
    }

    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }

    public static function annalsKindOptions(): array
    {
        return [
            self::ANNALS_KIND_ANAIS => 'Anais',
            self::ANNALS_KIND_EBOOK => 'E-book',
        ];
    }

    public function hasAnnalsPublication(): bool
    {
        return filled($this->annals_storage_path);
    }

    public function annalsKindLabel(): string
    {
        $options = self::annalsKindOptions();

        return $options[$this->annals_kind] ?? 'Anais';
    }

    /** Apenas prazo de inscrição (campo datetime_registration), ignorando calendário do evento. */
    public function registrationDeadlinePassed(): bool
    {
        if ($this->datetime_registration === null) {
            return false;
        }

        return Carbon::now()->greaterThan(Carbon::parse($this->datetime_registration));
    }

    /** Alias histórico: “prazo de inscrição encerrado”. */
    public function registrationClosed(): bool
    {
        return $this->registrationDeadlinePassed();
    }

    public function hasAudienceQuotas(): bool
    {
        $internal = $this->getAttributes()['internal_capacity'] ?? null;
        $external = $this->getAttributes()['external_capacity'] ?? null;

        return $internal !== null && $external !== null;
    }

    public function externalParticipants()
    {
        return $this->users()->where('users.is_external_participant', true);
    }

    /**
     * Participantes sem a marca de externo (incluindo cadastros antigos) contam como internos.
     *
     * @return array{internal: int, external: int, total: int}
     */
    public function audienceRegistrationCounts(): array
    {
        $external = (int) $this->externalParticipants()->count();
        $total = (int) $this->users()->count();

        return [
            'internal' => $total - $external,
            'external' => $external,
            'total' => $total,
        ];
    }

    /**
     * Sugestão para eventos que ainda não dividem as vagas.
     * A sobra ímpar fica com os internos e o piso é a quantidade já inscrita.
     *
     * @return array{internal: int, external: int}
     */
    public function suggestedAudienceCapacities(): array
    {
        if ($this->hasAudienceQuotas()) {
            return [
                'internal' => (int) $this->internal_capacity,
                'external' => (int) $this->external_capacity,
            ];
        }

        $counts = $this->audienceRegistrationCounts();
        $capacity = max((int) $this->capacity, $counts['total']);
        $remaining = $capacity - $counts['total'];

        if ($remaining <= 0) {
            return [
                'internal' => $counts['internal'],
                'external' => $counts['external'],
            ];
        }

        $externalExtra = intdiv($remaining, 2);
        $internalExtra = $remaining - $externalExtra;

        return [
            'internal' => $counts['internal'] + $internalExtra,
            'external' => $counts['external'] + $externalExtra,
        ];
    }

    public function audienceQuotaBlockFor(User $user): ?string
    {
        if (! $this->hasAudienceQuotas()) {
            return null;
        }

        $counts = $this->audienceRegistrationCounts();
        if ($user->is_external_participant) {
            return $counts['external'] >= (int) $this->external_capacity ? 'external_full' : null;
        }

        return $counts['internal'] >= (int) $this->internal_capacity ? 'internal_full' : null;
    }

    public function isFull(): bool
    {
        if ($this->hasAudienceQuotas()) {
            $counts = $this->audienceRegistrationCounts();

            return $counts['internal'] >= (int) $this->internal_capacity
                && $counts['external'] >= (int) $this->external_capacity;
        }

        return $this->users()->count() >= (int) $this->capacity;
    }

    public function registrationsBlockedReasonFor(?User $user): ?string
    {
        $reason = $this->registrationsBlockedReason();
        if ($reason !== null || $user === null || ! $user->isParticipant()) {
            return $reason;
        }

        return $this->audienceQuotaBlockFor($user);
    }

    /** Horário de início e de fim definidos. Sem isso o evento não inicia, não encerra e não finaliza. */
    public function scheduleIsComplete(): bool
    {
        return $this->start_date !== null
            && filled($this->start_time)
            && filled($this->end_time);
    }

    public function calendarStartAt(): Carbon
    {
        $d = $this->start_date->format('Y-m-d');
        $t = self::normalizeTimeForSchedule($this->start_time, '00:00:00');

        return Carbon::parse($d.' '.$t, config('app.timezone'));
    }

    public function calendarEndAt(): Carbon
    {
        $day = $this->end_date ?? $this->start_date;
        $d = $day->format('Y-m-d');
        $t = self::normalizeTimeForSchedule($this->end_time, '23:59:59');

        return Carbon::parse($d.' '.$t, config('app.timezone'));
    }

    /**
     * Limite dos prazos de inscrição e submissão.
     * Com hora de início, o limite é esse instante. Sem hora, é o fim do dia da data de início.
     */
    public static function deadlineLimitFromSchedule(string $startDate, mixed $startTime): Carbon
    {
        $tz = config('app.timezone');
        $startTime = trim((string) ($startTime ?? ''));
        if ($startTime === '') {
            return Carbon::parse($startDate.' 23:59:59', $tz);
        }

        return Carbon::parse(
            $startDate.' '.self::normalizeTimeForSchedule($startTime, '00:00:00'),
            $tz
        );
    }

    /** Verdadeiro quando os dois horários existem e o fim (data + hora) é anterior ao início. */
    public static function eventEndIsBeforeStart(
        string $startDate,
        mixed $startTime,
        ?string $endDate,
        mixed $endTime
    ): bool {
        $startTime = trim((string) ($startTime ?? ''));
        $endTime = trim((string) ($endTime ?? ''));
        if ($startDate === '' || $startTime === '' || $endTime === '') {
            return false;
        }

        $endDay = ($endDate !== null && trim($endDate) !== '') ? trim($endDate) : $startDate;
        [$start, $end] = self::calendarBoundsFromSchedule($startDate, $startTime, $endDay, $endTime);

        return $end->lessThan($start);
    }

    /**
     * Início e fim do evento a partir dos campos do formulário (criação/edição).
     * Atividades devem ficar inteiramente dentro deste intervalo (inclusive nas extremidades).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function calendarBoundsFromSchedule(
        string $startDate,
        mixed $startTime,
        ?string $endDate,
        mixed $endTime
    ): array {
        $tz = config('app.timezone');
        $start = Carbon::parse(
            $startDate.' '.self::normalizeTimeForSchedule($startTime, '00:00:00'),
            $tz
        );
        $endDay = ($endDate !== null && $endDate !== '') ? $endDate : $startDate;
        $end = Carbon::parse(
            $endDay.' '.self::normalizeTimeForSchedule($endTime, '23:59:59'),
            $tz
        );

        return [$start, $end];
    }

    /**
     * Verifica se [activityStart, activityEnd] está contido em [eventStart, eventEnd] (inclusive).
     */
    public static function activityFitsEventWindow(
        Carbon $activityStart,
        Carbon $activityEnd,
        Carbon $eventStart,
        Carbon $eventEnd
    ): bool {
        return ! $activityStart->lessThan($eventStart)
            && ! $activityEnd->greaterThan($eventEnd);
    }

    public static function normalizeTimeForSchedule(mixed $time, string $default): string
    {
        if ($time === null || $time === '') {
            return $default;
        }
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i:s');
        }
        $s = (string) $time;
        if (strlen($s) === 5) {
            return $s.':00';
        }
        if (strlen($s) >= 8) {
            return substr($s, 0, 8);
        }

        return $default;
    }

    /** Após data/hora de início do evento (inscrições fecham e evento aparece como iniciado). */
    public function calendarStarted(): bool
    {
        if (! $this->scheduleIsComplete()) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo($this->calendarStartAt());
    }

    /** Após data/hora de término do evento (aparece encerrado na tela). */
    public function calendarEnded(): bool
    {
        if (! $this->scheduleIsComplete()) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo($this->calendarEndAt());
    }

    /**
     * Ordem: finalizado → período encerrado → em andamento (inscrições fechadas) → prazo de inscrição → lotação.
     */
    public function registrationsBlockedReason(): ?string
    {
        if ($this->isFinalized()) {
            return 'finalized';
        }
        if ($this->calendarEnded()) {
            return 'ended';
        }
        if ($this->calendarStarted()) {
            return 'started';
        }
        if ($this->registrationDeadlinePassed()) {
            return 'deadline';
        }
        if ($this->isFull()) {
            return 'full';
        }

        return null;
    }

    public function acceptsNewRegistrations(): bool
    {
        return $this->registrationsBlockedReason() === null;
    }

    public function acceptsWorkType(string $workType): bool
    {
        return $this->acceptedWorkTypes()->contains($workType);
    }
}
