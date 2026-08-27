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
        Schema::create('agenda_settings', function (Blueprint $table) {
            $table->id();
            $table->time('start_time')->default('09:00:00');
            $table->time('end_time')->default('17:00:00');
            $table->integer('slot_duration')->default(30);
            $table->json('working_days')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_settings');
    }
};
