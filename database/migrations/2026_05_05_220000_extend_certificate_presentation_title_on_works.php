<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->text('certificate_presentation_title')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->string('certificate_presentation_title', 500)->nullable()->change();
        });
    }
};
