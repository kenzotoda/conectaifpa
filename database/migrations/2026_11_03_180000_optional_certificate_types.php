<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('certificate_reviewer_enabled')->default(false);
            $table->boolean('certificate_guest_enabled')->default(false);
            $table->boolean('certificate_organization_enabled')->default(false);
            $table->boolean('certificate_coauthor_enabled')->default(false);
        });

        Schema::table('certificados', function (Blueprint $table) {
            $table->string('recipient_name')->nullable();
            $table->string('recipient_role')->nullable();
            $table->foreignId('event_guest_id')->nullable()->constrained('event_guests')->nullOnDelete();
            $table->foreignId('work_author_id')->nullable()->constrained('work_authors')->nullOnDelete();
        });

        $this->makeCertificateUserNullable();
    }

    public function down(): void
    {
        Schema::table('certificados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_guest_id');
            $table->dropConstrainedForeignId('work_author_id');
            $table->dropColumn(['recipient_name', 'recipient_role']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'certificate_reviewer_enabled',
                'certificate_guest_enabled',
                'certificate_organization_enabled',
                'certificate_coauthor_enabled',
            ]);
        });
    }

    private function makeCertificateUserNullable(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            Schema::table('certificados', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('certificados', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        if ($driver !== 'sqlite') {
            Schema::table('certificados', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }
};
