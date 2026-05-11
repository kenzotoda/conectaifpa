<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->timestamp('published_in_annals_at')->nullable()->after('decision_at');
            $table->string('annals_url')->nullable()->after('published_in_annals_at');
            $table->text('annals_note')->nullable()->after('annals_url');

            $table->index('published_in_annals_at');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropIndex(['published_in_annals_at']);
            $table->dropColumn(['published_in_annals_at', 'annals_url', 'annals_note']);
        });
    }
};
