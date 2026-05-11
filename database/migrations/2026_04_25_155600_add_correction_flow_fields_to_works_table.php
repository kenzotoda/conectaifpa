<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->timestamp('correction_requested_at')->nullable()->after('workflow_note');
            $table->timestamp('correction_deadline_at')->nullable()->after('correction_requested_at');
            $table->timestamp('correction_submitted_at')->nullable()->after('correction_deadline_at');
            $table->text('correction_change_log')->nullable()->after('correction_submitted_at');
            $table->string('correction_status')->nullable()->after('correction_change_log');

            $table->index('correction_deadline_at');
            $table->index('correction_status');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropIndex(['correction_deadline_at']);
            $table->dropIndex(['correction_status']);
            $table->dropColumn([
                'correction_requested_at',
                'correction_deadline_at',
                'correction_submitted_at',
                'correction_change_log',
                'correction_status',
            ]);
        });
    }
};
