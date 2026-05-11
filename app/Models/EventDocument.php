<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventDocument extends Model
{
    protected $fillable = [
        'event_id',
        'title',
        'document_type',
        'file_name',
        'storage_path',
        'display_order',
        'uploaded_by_user_id',
    ];

    public const TYPE_NOTICE = 'edital';

    public const TYPE_SCHEDULE = 'programacao';

    public const TYPE_TEMPLATE = 'template';

    public const TYPE_ATTACHMENT = 'anexo';

    public const TYPE_OTHER = 'outro';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_NOTICE,
            self::TYPE_SCHEDULE,
            self::TYPE_TEMPLATE,
            self::TYPE_ATTACHMENT,
            self::TYPE_OTHER,
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_NOTICE => 'Edital',
            self::TYPE_SCHEDULE => 'Programação',
            self::TYPE_TEMPLATE => 'Template',
            self::TYPE_ATTACHMENT => 'Anexo',
            self::TYPE_OTHER => 'Outro',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
