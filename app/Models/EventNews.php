<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventNews extends Model
{
    protected $table = 'event_news';

    protected $fillable = ['event_id', 'title', 'content'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
