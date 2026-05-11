<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_event_guest', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignId('event_guest_id')->constrained('event_guests')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['activity_id', 'event_guest_id']);
        });

        if (Schema::hasColumn('activities', 'guest_id')) {
            $rows = DB::table('activities')->whereNotNull('guest_id')->get(['id', 'guest_id']);
            foreach ($rows as $row) {
                DB::table('activity_event_guest')->insert([
                    'activity_id' => $row->id,
                    'event_guest_id' => $row->guest_id,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_event_guest');
    }
};
