<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->foreignId('review_criterion_id')->constrained('review_criteria')->cascadeOnDelete();
            $table->decimal('score', 4, 2);
            $table->timestamps();

            $table->unique(['review_id', 'review_criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_scores');
    }
};
