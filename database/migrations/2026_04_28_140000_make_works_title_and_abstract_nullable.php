<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->text('abstract')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->text('abstract')->nullable(false)->change();
        });
    }
};
