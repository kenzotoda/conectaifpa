<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {

            $table->id(); // chave primária
            $table->string('title');
            $table->string('category');
            $table->string('modality'); // Presencial, online, híbrido
            $table->integer('capacity');
            $table->string('ead_link')->nullable();

            $table->text('description');
            $table->json('target_audience')->nullable();
            $table->json('prerequisites')->nullable();
            $table->json('modules')->nullable();

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->string('campus');
            $table->string('building');
            $table->string('venue');
            $table->string('address')->nullable();
            $table->text('location_details')->nullable();

            $table->string('image');

            $table->string('coordinator_name');
            $table->string('coordinator_email');
            $table->string('coordinator_phone');

            $table->dateTime('datetime_registration')->nullable();

            $table->timestamps(); // created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
