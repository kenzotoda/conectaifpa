<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_satisfaction_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('organization_score');
            $table->unsignedTinyInteger('content_score');
            $table->unsignedTinyInteger('environment_score');
            $table->unsignedTinyInteger('overall_score');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('satisfaction_invited_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_satisfaction_responses');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('satisfaction_invited_at');
        });
    }
};
