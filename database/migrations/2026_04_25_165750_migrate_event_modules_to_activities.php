<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('events', 'modules')) {
            return;
        }

        $events = DB::table('events')->select('id', 'start_date', 'modules')->get();

        foreach ($events as $event) {
            if (empty($event->modules)) {
                continue;
            }

            $decoded = json_decode($event->modules, true);
            if (! is_array($decoded)) {
                continue;
            }

            foreach ($decoded as $index => $module) {
                if (is_string($module)) {
                    $module = json_decode($module, true);
                }

                if (! is_array($module)) {
                    continue;
                }

                $title = trim((string) ($module['name'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $baseDate = $event->start_date ? Carbon::parse($event->start_date) : now();
                $startAt = $baseDate->copy()->addMinutes($index)->format('Y-m-d H:i:s');

                DB::table('activities')->insert([
                    'event_id' => $event->id,
                    'parent_activity_id' => null,
                    'title' => $title,
                    'description' => ! empty($module['description']) ? (string) $module['description'] : null,
                    'type' => 'modulo',
                    'start_at' => $startAt,
                    'end_at' => null,
                    'location' => null,
                    'capacity' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Sem rollback automático para evitar remover atividades criadas manualmente.
    }
};
