<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSession extends Model
{
    protected $fillable = [
        'event_id',
        'title',
        'description',
        'session_date',
        'start_time',
        'end_time',
        'location',
        'modality',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public const MODALITY_ORAL = 'oral';

    public const MODALITY_POSTER = 'poster';

    public const MODALITY_ONLINE = 'online';

    public static function modalityOptions(): array
    {
        return [
            self::MODALITY_ORAL => 'Oral',
            self::MODALITY_POSTER => 'Pôster',
            self::MODALITY_ONLINE => 'Online',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function presentations()
    {
        return $this->hasMany(WorkPresentation::class);
    }
}
