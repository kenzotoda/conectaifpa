<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'event_id',
        'parent_activity_id',
        'guest_id',
        'title',
        'description',
        'type',
        'start_at',
        'end_at',
        'location',
        'capacity',
        'speakers',
        'workload_hours',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'capacity' => 'integer',
        'guest_id' => 'integer',
        'speakers' => 'array',
        'workload_hours' => 'decimal:2',
    ];

    public const TYPE_LECTURE = 'palestra';

    public const TYPE_WORKSHOP = 'workshop';

    public const TYPE_MINICOURSE = 'minicurso';

    public const TYPE_COURSE = 'curso';

    public const TYPE_MODULE = 'modulo';

    public const TYPE_SESSION = 'sessao';

    public const TYPE_OTHER = 'outro';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_LECTURE,
            self::TYPE_WORKSHOP,
            self::TYPE_MINICOURSE,
            self::TYPE_COURSE,
            self::TYPE_MODULE,
            self::TYPE_SESSION,
            self::TYPE_OTHER,
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_LECTURE => 'Palestra',
            self::TYPE_WORKSHOP => 'Workshop',
            self::TYPE_MINICOURSE => 'Minicurso',
            self::TYPE_COURSE => 'Curso',
            self::TYPE_MODULE => 'Módulo',
            self::TYPE_SESSION => 'Sessão',
            self::TYPE_OTHER => 'Outro',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function parentActivity()
    {
        return $this->belongsTo(self::class, 'parent_activity_id');
    }

    public function subactivities()
    {
        return $this->hasMany(self::class, 'parent_activity_id')->orderBy('start_at');
    }

    public function guest()
    {
        return $this->belongsTo(EventGuest::class, 'guest_id');
    }

    /**
     * Convidados vinculados à atividade (ordem em sort_order no pivô).
     */
    public function eventGuests()
    {
        return $this->belongsToMany(EventGuest::class, 'activity_event_guest', 'activity_id', 'event_guest_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }

    public function activityPresences()
    {
        return $this->hasMany(ActivityPresence::class);
    }

    /**
     * Atividade encerrada por data/hora de fim (regra para emissão em lote).
     */
    public function certificateSlotEnded(): bool
    {
        $end = $this->end_at ?? $this->start_at;
        if ($end === null) {
            return false;
        }

        return now()->greaterThanOrEqualTo($end);
    }
}
