<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewScore extends Model
{
    protected $fillable = [
        'review_id',
        'review_criterion_id',
        'score',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function criterion()
    {
        return $this->belongsTo(ReviewCriterion::class, 'review_criterion_id');
    }
}
