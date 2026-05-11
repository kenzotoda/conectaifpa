<?php

use App\Models\Work;
use App\Models\WorkPresentation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $workIdsPending = DB::table('work_presentations')
            ->where('attendance_status', WorkPresentation::ATTENDANCE_PENDENTE)
            ->pluck('work_id')
            ->all();

        DB::table('work_presentations')
            ->where('attendance_status', WorkPresentation::ATTENDANCE_PENDENTE)
            ->update(['attendance_status' => WorkPresentation::ATTENDANCE_AUSENTE]);

        if ($workIdsPending === []) {
            return;
        }

        Work::query()
            ->whereIn('id', $workIdsPending)
            ->where('status', Work::STATUS_SCHEDULED)
            ->update(['status' => Work::STATUS_ABSENT]);
    }

    public function down(): void
    {
        // Irreversível: não há como restaurar registros marcados anteriormente como "pendente".
    }
};
