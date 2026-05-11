<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_review_criteria', function (Blueprint $table) {
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('review_criterion_id')->constrained('review_criteria')->cascadeOnDelete();
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->timestamps();

            $table->primary(['event_id', 'review_criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_review_criteria');
    }
};
