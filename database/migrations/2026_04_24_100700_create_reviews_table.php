<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('recommendation')->nullable();
            $table->text('general_comment')->nullable();
            $table->text('comment_to_author')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_blind')->default(true);

            $table->timestamps();

            $table->unique(['work_id', 'reviewer_user_id']);
            $table->index(['work_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
