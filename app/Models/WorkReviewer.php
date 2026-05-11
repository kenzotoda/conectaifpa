<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkReviewer extends Model
{
    protected $fillable = [
        'work_id',
        'reviewer_user_id',
        'assigned_by',
        'assigned_at',
        'status',
        'prior_evaluation_score',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'prior_evaluation_score' => 'decimal:2',
    ];

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ASSIGNED => 'Atribuído',
            self::STATUS_IN_PROGRESS => 'Em andamento',
            self::STATUS_COMPLETED => 'Concluído',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? str_replace('_', ' ', ucfirst((string) $this->status));
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
