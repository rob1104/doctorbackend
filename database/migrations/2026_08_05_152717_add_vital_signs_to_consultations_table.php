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
        Schema::table('consultations', function (Blueprint $table) {
            $table->string('blood_pressure', 20)->nullable();
            $table->decimal('temperature', 4, 1)->nullable(); // ej. 36.5
            $table->integer('heart_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->decimal('weight', 5, 2)->nullable(); // ej. 75.50
            $table->decimal('height', 4, 2)->nullable(); // ej. 1.75
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'blood_pressure',
                'temperature',
                'heart_rate',
                'respiratory_rate',
                'weight',
                'height'
            ]);
        });
    }
};
