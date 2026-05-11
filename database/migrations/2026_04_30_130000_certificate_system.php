<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('certificate_total_hours', 8, 2)->nullable()->after('finalized_at');
            $table->string('certificate_organizer')->nullable()->after('certificate_total_hours');
            $table->string('certificate_institution')->nullable()->after('certificate_organizer');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('workload_hours', 8, 2)->nullable()->after('capacity');
        });

        Schema::table('works', function (Blueprint $table) {
            $table->decimal('certificate_presentation_hours', 8, 2)->nullable()->default(2)->after('final_version_source');
        });

        Schema::create('presenca_evento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->boolean('presente')->default(false);
            $table->foreignId('marcado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_marcacao')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'event_id']);
        });

        Schema::create('presenca_atividade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->boolean('presente')->default(false);
            $table->foreignId('marcado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('data_marcacao')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'activity_id']);
        });

        Schema::create('assinaturas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cargo');
            $table->string('imagem_assinatura')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assinatura_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('assinatura_id')->constrained('assinaturas')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'assinatura_id']);
        });

        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 32);
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->foreignId('work_id')->nullable()->constrained('works')->nullOnDelete();
            $table->string('arquivo_pdf');
            $table->string('codigo_validacao', 48)->unique();
            $table->timestamp('data_emissao');
            $table->timestamps();

            $table->index(['event_id', 'tipo']);
            $table->index(['user_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados');
        Schema::dropIfExists('assinatura_event');
        Schema::dropIfExists('assinaturas');
        Schema::dropIfExists('presenca_atividade');
        Schema::dropIfExists('presenca_evento');

        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn('certificate_presentation_hours');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('workload_hours');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_total_hours',
                'certificate_organizer',
                'certificate_institution',
            ]);
        });
    }
};
