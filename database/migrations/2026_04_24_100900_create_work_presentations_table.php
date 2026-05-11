<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->unique()->constrained('works')->cascadeOnDelete();
            $table->string('presentation_type');
            $table->string('session_name')->nullable();
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_presentations');
    }
};
