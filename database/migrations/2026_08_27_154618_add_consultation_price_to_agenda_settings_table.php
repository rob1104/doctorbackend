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
        Schema::table('agenda_settings', function (Blueprint $table) {
            $table->integer('consultation_price')->default(1500)->after('slot_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agenda_settings', function (Blueprint $table) {
            $table->dropColumn('consultation_price');
        });
    }
};
