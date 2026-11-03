<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('natureza')->nullable()->after('category');
            $table->string('abrangencia')->nullable()->after('natureza');
            $table->string('periodicidade')->nullable()->after('abrangencia');
            $table->unsignedSmallInteger('edicao')->nullable()->after('periodicidade');
            $table->string('acesso')->default('gratuito')->after('edicao');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['natureza', 'abrangencia', 'periodicidade', 'edicao', 'acesso']);
        });
    }
};
