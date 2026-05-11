<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkPresentation extends Model
{
    protected $fillable = [
        'work_id',
        'event_session_id',
        'presentation_order',
        'presentation_type',
        'session_name',
        'scheduled_start',
        'scheduled_end',
        'location',
        'attendance_status',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
    ];

    public const TYPE_ORAL = 'oral';

    public const TYPE_POSTER = 'poster';

    public const TYPE_ONLINE = 'online';

    /** @deprecated Valor legado; novos fluxos gravam apenas apresentado ou ausente */
    public const ATTENDANCE_PENDENTE = 'pendente';

    public const ATTENDANCE_APRESENTADO = 'apresentado';

    public const ATTENDANCE_AUSENTE = 'ausente';

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function session()
    {
        return $this->belongsTo(EventSession::class, 'event_session_id');
    }
}
