<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('annals_kind', 20)->nullable()->after('finalized_at');
            $table->string('annals_file_name')->nullable()->after('annals_kind');
            $table->string('annals_storage_path')->nullable()->after('annals_file_name');
            $table->timestamp('annals_uploaded_at')->nullable()->after('annals_storage_path');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'annals_kind',
                'annals_file_name',
                'annals_storage_path',
                'annals_uploaded_at',
            ]);
        });
    }
};
