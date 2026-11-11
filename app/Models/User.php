<?php

namespace App\Models;

use App\Notifications\VerifyEmailQueued;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    // Constantes para papéis de usuário
    const ROLE_COORDINATOR = 'coordinator';

    const ROLE_PARTICIPANT = 'participant';

    const ROLE_STUDENT = self::ROLE_PARTICIPANT;

    const ROLE_REVIEWER = 'reviewer';

    const ROLE_GUEST = 'guest';

    const ROLE_ORGANIZER = 'organizer';

    private ?bool $ownsIssuedCertificates = null;

    public const PROFILE_DISCENTE = 'discente';

    public const PROFILE_DOCENTE = 'docente';

    public const PROFILE_TECNICO = 'tecnico_administrativo';

    public const PROFILE_EMPREENDEDOR = 'empreendedor';

    public const PROFILE_ESTUDANTE_ESCOLA_PUBLICA = 'estudante_escola_publica';

    public const PROFILE_STARTUP = 'startup';

    public const PROFILE_ORGANIZACAO_SOCIAL = 'organizacao_social';

    public const PROFILE_COMUNIDADE_GERAL = 'comunidade_geral';

    public const PROFILE_IFPA_OUTRO_CAMPUS = 'ifpa_outro_campus';

    /** @var array<string, string> */
    public const PARTICIPANT_PROFILE_LABELS = [
        self::PROFILE_DISCENTE => 'Discente',
        self::PROFILE_DOCENTE => 'Docente',
        self::PROFILE_TECNICO => 'Técnico administrativo',
        self::PROFILE_EMPREENDEDOR => 'Empreendedor',
        self::PROFILE_ESTUDANTE_ESCOLA_PUBLICA => 'Estudante de escola pública',
        self::PROFILE_STARTUP => 'Startup',
        self::PROFILE_ORGANIZACAO_SOCIAL => 'Organização social',
        self::PROFILE_COMUNIDADE_GERAL => 'Comunidade em geral',
        self::PROFILE_IFPA_OUTRO_CAMPUS => 'Discente, docente ou técnico de outro campus',
    ];

    /** Vínculo de quem é do IFPA, mas não do campus Belém. */
    /** @var array<string, string> */
    public const IFPA_BOND_LABELS = [
        self::PROFILE_DISCENTE => 'Discente',
        self::PROFILE_DOCENTE => 'Docente',
        self::PROFILE_TECNICO => 'Técnico administrativo',
    ];

    /** @var list<string> */
    public const INTERNAL_PARTICIPANT_PROFILES = [
        self::PROFILE_DISCENTE,
        self::PROFILE_DOCENTE,
        self::PROFILE_TECNICO,
    ];

    /** @var list<string> */
    public const EXTERNAL_PARTICIPANT_PROFILES = [
        self::PROFILE_IFPA_OUTRO_CAMPUS,
        self::PROFILE_EMPREENDEDOR,
        self::PROFILE_ESTUDANTE_ESCOLA_PUBLICA,
        self::PROFILE_STARTUP,
        self::PROFILE_ORGANIZACAO_SOCIAL,
        self::PROFILE_COMUNIDADE_GERAL,
    ];

    /** Perfis que informam curso, área, setor ou série. */
    /** @var list<string> */
    public const PROFILES_WITH_DETAIL = [
        self::PROFILE_DISCENTE,
        self::PROFILE_DOCENTE,
        self::PROFILE_TECNICO,
        self::PROFILE_ESTUDANTE_ESCOLA_PUBLICA,
        self::PROFILE_IFPA_OUTRO_CAMPUS,
    ];

    /** Perfis externos em que a organização é obrigatória. Comunidade em geral pode ficar sem. */
    /** @var list<string> */
    public const PROFILES_WITH_REQUIRED_ORGANIZATION = [
        self::PROFILE_EMPREENDEDOR,
        self::PROFILE_ESTUDANTE_ESCOLA_PUBLICA,
        self::PROFILE_STARTUP,
        self::PROFILE_ORGANIZACAO_SOCIAL,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'created_by_user_id',
        'matricula',
        'curso',
        'is_external_participant',
        'participant_profile',
        'ifpa_bond',
        'institution',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_external_participant' => 'boolean',
        ];
    }

    // Retorna os eventos que o usuário é dono. || (1:N)
    public function events()
    {
        return $this->hasMany('App\Models\Event');
    }

    // Retorna os eventos que o usuário participa. || (N:N)
    // Aqui não existe uma chave estrangeira direta nas tabelas users e events que resolva essa relação.
    // Então é necessário criar uma tabela intermediária (chamada de tabela pivô).
    // Por convenção do Laravel, o nome da tabela pivô será event_user, seguindo ordem alfabética das tabelas relacionadas.
    public function eventsAsParticipant()
    {
        return $this->belongsToMany('App\Models\Event');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailQueued);
    }

    public function isCoordinator()
    {
        return $this->role === self::ROLE_COORDINATOR;
    }

    public function isParticipant()
    {
        return $this->role === self::ROLE_PARTICIPANT;
    }

    public function isStudent()
    {
        return $this->isParticipant();
    }

    public function isReviewer()
    {
        return $this->role === self::ROLE_REVIEWER;
    }

    public function isGuest()
    {
        return $this->role === self::ROLE_GUEST;
    }

    public function isOrganizer()
    {
        return $this->role === self::ROLE_ORGANIZER;
    }

    public function hasIssuedCertificates(): bool
    {
        return $this->ownsIssuedCertificates ??= Certificate::query()
            ->where('user_id', $this->id)
            ->exists();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_COORDINATOR => 'Coordenador',
            self::ROLE_REVIEWER => 'Avaliador',
            self::ROLE_GUEST => 'Convidado',
            self::ROLE_ORGANIZER => 'Organizador',
            default => 'Participante',
        };
    }

    public function organizedEvents()
    {
        return $this->belongsToMany(Event::class, 'event_organizers')->withTimestamps();
    }

    public function managesEvent(Event|int|null $event): bool
    {
        $eventId = $event instanceof Event ? (int) $event->id : (int) $event;
        if ($eventId <= 0) {
            return false;
        }

        if ($this->isCoordinator()) {
            $ownerId = $event instanceof Event
                ? (int) $event->user_id
                : (int) Event::query()->whereKey($eventId)->value('user_id');

            return $ownerId === (int) $this->id;
        }

        if (! $this->isOrganizer()) {
            return false;
        }

        if ($event instanceof Event && $event->relationLoaded('organizers')) {
            return $event->organizers->contains(fn (User $organizer) => (int) $organizer->id === (int) $this->id);
        }

        return $this->organizedEvents()->where('events.id', $eventId)->exists();
    }

    public function submittedWorks()
    {
        return $this->hasMany(Work::class, 'submitter_user_id');
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function assignedWorksForReview()
    {
        return $this->belongsToMany(Work::class, 'work_reviewers', 'reviewer_user_id', 'work_id')
            ->withPivot(['status', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewer_user_id');
    }

    public function audienceCategoryLabel(): string
    {
        return $this->is_external_participant ? 'Externo' : 'Interno';
    }

    public function participantProfileLabel(): string
    {
        $profile = (string) ($this->participant_profile ?? '');
        if ($profile === self::PROFILE_IFPA_OUTRO_CAMPUS) {
            $bond = self::IFPA_BOND_LABELS[(string) ($this->ifpa_bond ?? '')] ?? 'IFPA';

            return $bond.' de outro campus';
        }
        if (isset(self::PARTICIPANT_PROFILE_LABELS[$profile])) {
            return self::PARTICIPANT_PROFILE_LABELS[$profile];
        }

        return $this->is_external_participant ? 'Comunidade externa' : 'Discente';
    }

    /**
     * Texto de vínculo (instituição / curso) para autoria principal em trabalhos.
     */
    public function participantAffiliationForWorks(): ?string
    {
        if (! $this->isParticipant()) {
            return null;
        }

        $detail = trim((string) ($this->curso ?? ''));
        $organization = trim((string) ($this->institution ?? ''));

        if ($this->is_external_participant) {
            if ($organization !== '' && $detail !== '') {
                return $organization.', '.$detail;
            }

            return $organization !== '' ? $organization : ($detail !== '' ? $detail : null);
        }

        if ($detail !== '') {
            return 'IFPA - Belém, '.$detail;
        }

        return 'IFPA - Belém';
    }
}
