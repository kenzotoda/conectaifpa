<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityPresence extends Model
{
    protected $table = 'presenca_atividade';

    protected $fillable = [
        'user_id',
        'activity_id',
        'presente',
        'marcado_por',
        'data_marcacao',
    ];

    protected $casts = [
        'presente' => 'boolean',
        'data_marcacao' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marcado_por');
    }
}
