<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->string('institution')->nullable();
            $table->boolean('is_main_author')->default(false);
            $table->unsignedInteger('author_order')->default(1);

            $table->timestamps();

            $table->index(['work_id', 'author_order']);
            $table->index(['work_id', 'is_main_author']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_authors');
    }
};
