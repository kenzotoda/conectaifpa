<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkAuthor extends Model
{
    protected $fillable = [
        'work_id',
        'user_id',
        'author_name',
        'author_email',
        'institution',
        'is_main_author',
        'author_order',
    ];

    protected $casts = [
        'is_main_author' => 'boolean',
    ];

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
