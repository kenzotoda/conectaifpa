<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('certificate_reviewer_hours', 8, 2)->nullable();
            $table->decimal('certificate_guest_hours', 8, 2)->nullable();
            $table->decimal('certificate_organization_hours', 8, 2)->nullable();
            $table->decimal('certificate_publication_hours', 8, 2)->nullable();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('is_extension')->default(false);
        });

        Schema::table('presenca_atividade', function (Blueprint $table) {
            $table->unsignedTinyInteger('frequencia_percent')->nullable();
        });

        Schema::table('presenca_evento', function (Blueprint $table) {
            $table->boolean('presente_presencial')->default(false);
            $table->boolean('presente_online')->default(false);
        });

        DB::table('presenca_evento')->where('presente', true)->update([
            'presente_presencial' => true,
        ]);

        $onlineEventIds = DB::table('events')->where('modality', 'Online')->pluck('id');
        if ($onlineEventIds->isNotEmpty()) {
            DB::table('presenca_evento')
                ->whereIn('event_id', $onlineEventIds)
                ->where('presente', true)
                ->update([
                    'presente_presencial' => false,
                    'presente_online' => true,
                ]);
        }

        DB::table('users')->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('presenca_evento', function (Blueprint $table) {
            $table->dropColumn(['presente_presencial', 'presente_online']);
        });

        Schema::table('presenca_atividade', function (Blueprint $table) {
            $table->dropColumn('frequencia_percent');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('is_extension');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_reviewer_hours',
                'certificate_guest_hours',
                'certificate_organization_hours',
                'certificate_publication_hours',
            ]);
        });
    }
};
