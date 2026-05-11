<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('submission_deadline_at')->nullable()->after('datetime_registration');
            $table->index('submission_deadline_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['submission_deadline_at']);
            $table->dropColumn('submission_deadline_at');
        });
    }
};
