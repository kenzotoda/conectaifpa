<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('activities', 'is_extension')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropColumn('is_extension');
            });
        }

        if (Schema::hasColumn('presenca_atividade', 'frequencia_percent')) {
            Schema::table('presenca_atividade', function (Blueprint $table) {
                $table->dropColumn('frequencia_percent');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('activities', 'is_extension')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->boolean('is_extension')->default(false);
            });
        }

        if (! Schema::hasColumn('presenca_atividade', 'frequencia_percent')) {
            Schema::table('presenca_atividade', function (Blueprint $table) {
                $table->unsignedTinyInteger('frequencia_percent')->nullable();
            });
        }
    }
};
