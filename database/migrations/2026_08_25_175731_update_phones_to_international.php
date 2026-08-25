<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar pacientes
        DB::table('patients')
            ->whereNotNull('phone')
            ->where('phone', 'NOT LIKE', '+%')
            ->whereRaw('LENGTH(phone) = 10')
            ->update(['phone' => DB::raw("CONCAT('+52', phone)")]);

        // Actualizar emergency contact phone si existiera
        DB::table('patients')
            ->whereNotNull('emergency_contact_phone')
            ->where('emergency_contact_phone', 'NOT LIKE', '+%')
            ->whereRaw('LENGTH(emergency_contact_phone) = 10')
            ->update(['emergency_contact_phone' => DB::raw("CONCAT('+52', emergency_contact_phone)")]);

        // Actualizar whatsapp messages
        DB::table('whats_app_messages')
            ->whereNotNull('phone')
            ->where('phone', 'NOT LIKE', '+%')
            ->whereRaw('LENGTH(phone) = 10')
            ->update(['phone' => DB::raw("CONCAT('+52', phone)")]);

        // Actualizar otp verifications
        DB::table('otp_verifications')
            ->whereNotNull('phone')
            ->where('phone', 'NOT LIKE', '+%')
            ->whereRaw('LENGTH(phone) = 10')
            ->update(['phone' => DB::raw("CONCAT('+52', phone)")]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('international', function (Blueprint $table) {
            //
        });
    }
};
