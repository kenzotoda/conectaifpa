<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedTinyInteger('reviewers_per_work')
                ->default(2)
                ->after('accepts_submissions');
            $table->text('call_for_papers_text')
                ->nullable()
                ->after('reviewers_per_work');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['reviewers_per_work', 'call_for_papers_text']);
        });
    }
};
