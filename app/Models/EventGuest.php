<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventGuest extends Model
{
    public const ROLE_TYPE_OPTIONS = [
        'palestrante',
        'ministrante',
        'mediador',
        'avaliador',
        'organizador',
        'coordenador',
        'monitor',
    ];

    protected $fillable = [
        'event_id',
        'name',
        'role_type',
        'role',
    ];

    public static function roleTypeOptions(): array
    {
        return self::ROLE_TYPE_OPTIONS;
    }

    public static function roleTypeLabels(): array
    {
        return [
            'palestrante' => 'Palestrante',
            'ministrante' => 'Ministrante',
            'mediador' => 'Mediador',
            'avaliador' => 'Avaliador',
            'organizador' => 'Organizador',
            'coordenador' => 'Coordenador',
            'monitor' => 'Monitor',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_event_guest', 'event_guest_id', 'activity_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }
}
