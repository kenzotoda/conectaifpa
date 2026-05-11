<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewCriterion extends Model
{
    protected $fillable = [
        'name',
        'description',
        'default_weight',
        'is_active',
    ];

    protected $casts = [
        'default_weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_review_criteria')
            ->withPivot('weight')
            ->withTimestamps();
    }

    public function scores()
    {
        return $this->hasMany(ReviewScore::class);
    }
}
