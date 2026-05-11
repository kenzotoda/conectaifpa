<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedTinyInteger('reviewers_min_per_work')
                ->nullable()
                ->after('accepts_submissions');
            $table->unsignedTinyInteger('reviewers_max_per_work')
                ->nullable()
                ->after('reviewers_min_per_work');
        });

        DB::table('events')->select('id', 'reviewers_per_work')->orderBy('id')->chunk(100, function ($events) {
            foreach ($events as $event) {
                $legacy = (int) ($event->reviewers_per_work ?? 2);
                $value = $legacy > 0 ? $legacy : 2;

                DB::table('events')
                    ->where('id', $event->id)
                    ->update([
                        'reviewers_min_per_work' => $value,
                        'reviewers_max_per_work' => $value,
                    ]);
            }
        });

        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'reviewers_per_work')) {
                $table->dropColumn('reviewers_per_work');
            }
            if (Schema::hasColumn('events', 'call_for_papers_text')) {
                $table->dropColumn('call_for_papers_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedTinyInteger('reviewers_per_work')
                ->default(2)
                ->after('accepts_submissions');
            $table->text('call_for_papers_text')
                ->nullable()
                ->after('reviewers_per_work');
        });

        DB::table('events')->select('id', 'reviewers_max_per_work', 'reviewers_min_per_work')->orderBy('id')->chunk(100, function ($events) {
            foreach ($events as $event) {
                $value = (int) ($event->reviewers_max_per_work ?? $event->reviewers_min_per_work ?? 2);
                $value = $value > 0 ? $value : 2;

                DB::table('events')
                    ->where('id', $event->id)
                    ->update([
                        'reviewers_per_work' => $value,
                        'call_for_papers_text' => null,
                    ]);
            }
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['reviewers_min_per_work', 'reviewers_max_per_work']);
        });
    }
};
