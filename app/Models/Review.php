<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'work_id',
        'reviewer_user_id',
        'recommendation',
        'score',
        'general_comment',
        'comment_to_author',
        'feedback_file_path',
        'refined_correction_file_path',
        'submitted_at',
        'is_blind',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'submitted_at' => 'datetime',
        'is_blind' => 'boolean',
    ];

    public const RECOMMENDATION_ACCEPT = 'accept';

    public const RECOMMENDATION_ACCEPT_WITH_CORRECTIONS = 'accept_with_corrections';

    public const RECOMMENDATION_REJECT = 'reject';

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function scores()
    {
        return $this->hasMany(ReviewScore::class);
    }
}
