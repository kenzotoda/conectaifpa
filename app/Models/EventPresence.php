<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPresence extends Model
{
    protected $table = 'presenca_evento';

    protected $fillable = [
        'user_id',
        'event_id',
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

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marcado_por');
    }
}
