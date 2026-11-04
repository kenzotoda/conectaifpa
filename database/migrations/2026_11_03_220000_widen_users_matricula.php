<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['matricula']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('matricula', 20)->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('matricula');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['matricula']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('matricula', 12)->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('matricula');
        });
    }
};
