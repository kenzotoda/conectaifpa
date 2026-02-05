<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    protected $table = 'events';

    protected $casts = [
        'target_audience' => 'array',
        'prerequisites' => 'array',
        'modules' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'datetime_registration' => 'datetime',
    ];

    protected $guarded = [];

    // DELETA A IMAGEM DO EVENTO DO STORAGE QUANDO O EVENTO FOR DELETADO ($event->delete();)
    protected static function booted()
    {
        static::deleting(function ($event) {
            if ($event->image) {
                Storage::disk('public')->delete('events/' . $event->image);
            }
        });
    }

    // Relacionamentos
    public function user() 
    {
        return $this->belongsTo('App\Models\User');
    }

    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    // Regras de negócio
    public function registrationClosed()
    {
        return Carbon::now()->greaterThan(Carbon::parse($this->datetime_registration));
    }

    public function isFull()
    {
        return $this->users()->count() >= $this->capacity;
    }
}
