<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_reviewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default('assigned');
            $table->timestamps();

            $table->unique(['work_id', 'reviewer_user_id']);
            $table->index(['reviewer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_reviewers');
    }
};
