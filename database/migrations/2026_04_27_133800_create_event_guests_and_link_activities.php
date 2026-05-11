<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->text('role')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'name']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('guest_id')
                ->nullable()
                ->after('parent_activity_id')
                ->constrained('event_guests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_id');
        });

        Schema::dropIfExists('event_guests');
    }
};
