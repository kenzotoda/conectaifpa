<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('works')
            ->where('status', 'published_annals')
            ->update(['status' => 'presented']);
    }

    public function down(): void
    {
        // O status por trabalho nos anais deixou de existir.
    }
};
