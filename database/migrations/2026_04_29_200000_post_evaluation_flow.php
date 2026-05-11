<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->string('modality'); // oral | poster | online
            $table->timestamps();

            $table->index(['event_id', 'session_date']);
        });

        Schema::table('works', function (Blueprint $table) {
            $table->timestamp('final_version_submitted_at')->nullable()->after('correction_status');
            $table->timestamp('final_version_validated_at')->nullable()->after('final_version_submitted_at');
            $table->foreignId('final_version_validated_by')->nullable()->after('final_version_validated_at')->constrained('users')->nullOnDelete();
            $table->string('final_version_file_path')->nullable()->after('final_version_validated_by');
        });

        Schema::table('work_presentations', function (Blueprint $table) {
            $table->foreignId('event_session_id')->nullable()->after('work_id')->constrained('event_sessions')->nullOnDelete();
            $table->unsignedInteger('presentation_order')->nullable()->after('event_session_id');
            $table->string('attendance_status')->default('pendente')->after('location');
        });

        DB::table('works')->where('status', 'accepted')->update(['status' => 'approved_final']);
    }

    public function down(): void
    {
        Schema::table('work_presentations', function (Blueprint $table) {
            $table->dropForeign(['event_session_id']);
            $table->dropColumn(['event_session_id', 'presentation_order', 'attendance_status']);
        });

        Schema::table('works', function (Blueprint $table) {
            $table->dropForeign(['final_version_validated_by']);
            $table->dropColumn([
                'final_version_submitted_at',
                'final_version_validated_at',
                'final_version_validated_by',
                'final_version_file_path',
            ]);
        });

        DB::table('works')->where('status', 'approved_final')->update(['status' => 'accepted']);

        Schema::dropIfExists('event_sessions');
    }
};
