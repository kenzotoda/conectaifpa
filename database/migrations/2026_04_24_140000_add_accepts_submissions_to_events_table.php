<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('accepts_submissions')
                ->default(false)
                ->after('submission_deadline_at');
            $table->index('accepts_submissions');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['accepts_submissions']);
            $table->dropColumn('accepts_submissions');
        });
    }
};
