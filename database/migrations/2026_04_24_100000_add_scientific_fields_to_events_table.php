<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('event_type')->nullable()->after('category');
            $table->string('modality_type')->nullable()->after('modality');

            $table->index('event_type');
            $table->index('modality_type');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['event_type']);
            $table->dropIndex(['modality_type']);
            $table->dropColumn(['event_type', 'modality_type']);
        });
    }
};
