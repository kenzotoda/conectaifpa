<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkVersion extends Model
{
    protected $fillable = [
        'work_id',
        'version_number',
        'file_path',
        'change_log',
        'uploaded_by_user_id',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
