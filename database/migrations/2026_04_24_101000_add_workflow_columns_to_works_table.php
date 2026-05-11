<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->timestamp('withdrawal_requested_at')->nullable()->after('author_feedback');
            $table->text('withdrawal_requested_reason')->nullable()->after('withdrawal_requested_at');
            $table->text('workflow_note')->nullable()->after('withdrawal_requested_reason');
            $table->foreignId('deleted_by_coordinator_id')->nullable()->after('workflow_note')
                ->constrained('users')->nullOnDelete();

            $table->index('withdrawal_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropIndex(['withdrawal_requested_at']);
            $table->dropForeign(['deleted_by_coordinator_id']);
            $table->dropColumn([
                'withdrawal_requested_at',
                'withdrawal_requested_reason',
                'workflow_note',
                'deleted_by_coordinator_id',
            ]);
        });
    }
};
