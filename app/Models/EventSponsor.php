<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSponsor extends Model
{
    protected $fillable = ['event_id', 'name', 'website'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
