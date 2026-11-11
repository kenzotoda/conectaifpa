<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('role')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('event_guests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('event_id')->constrained('users')->nullOnDelete();
            $table->string('email')->nullable()->after('name');
        });

        Schema::create('event_organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_organizers');

        Schema::table('event_guests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};
