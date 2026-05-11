<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('submitter_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('title');
            $table->text('abstract');
            $table->string('work_type');
            $table->string('file_path');

            $table->string('status')->default('submitted');
            $table->decimal('final_score', 5, 2)->nullable();

            $table->timestamp('decision_at')->nullable();
            $table->foreignId('decision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('author_feedback')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'status']);
            $table->index(['submitter_user_id', 'status']);
            $table->index('work_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
