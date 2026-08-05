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
        Schema::table('patients', function (Blueprint $table) {
            $table->text('current_medications')->nullable();
            $table->text('surgical_history')->nullable();
            $table->string('skin_tendency')->nullable();
            $table->string('sun_exposure_level')->nullable();
            $table->text('previous_skin_conditions')->nullable();
            $table->text('skincare_routine')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'current_medications',
                'surgical_history',
                'skin_tendency',
                'sun_exposure_level',
                'previous_skin_conditions',
                'skincare_routine'
            ]);
        });
    }
};
