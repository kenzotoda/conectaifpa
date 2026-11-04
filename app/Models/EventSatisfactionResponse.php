<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSatisfactionResponse extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'organization_score',
        'content_score',
        'environment_score',
        'overall_score',
        'comment',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
