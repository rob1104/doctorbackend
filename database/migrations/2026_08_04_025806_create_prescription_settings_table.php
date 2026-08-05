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
        Schema::create('prescription_settings', function (Blueprint $table) {
            $table->id();
            $table->string('doctor_name');
            $table->string('specialty')->nullable();
            $table->string('university')->nullable();
            $table->string('professional_id')->nullable();
            $table->string('specialty_id')->nullable();
            $table->string('clinic_address')->nullable();
            $table->string('logo_path')->nullable();
            $table->integer('folio_counter')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_settings');
    }
};
