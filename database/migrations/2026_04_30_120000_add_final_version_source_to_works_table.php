<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->string('final_version_source', 32)->nullable()->after('final_version_file_path');
        });

        foreach (DB::table('works')->where('status', 'approved_final')->cursor() as $row) {
            DB::table('works')->where('id', $row->id)->update([
                'status' => 'final_validated',
                'final_version_validated_at' => $row->final_version_validated_at ?? $row->decision_at,
                'final_version_validated_by' => $row->final_version_validated_by ?? $row->decision_by,
                'final_version_source' => ($row->correction_submitted_at !== null) ? 'corrected' : 'direct',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn('final_version_source');
        });
    }
};
