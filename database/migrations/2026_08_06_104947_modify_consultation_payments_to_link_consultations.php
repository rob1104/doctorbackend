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
        Schema::table('consultation_payments', function (Blueprint $table) {
            $hasAppointment = !empty(Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM `consultation_payments` LIKE 'appointment_id'"));
            if ($hasAppointment) {
                // Drop the foreign key constraint first to prevent MySQL errno 150
                $table->dropForeign(['appointment_id']);
                $table->dropColumn('appointment_id');
            }
            $table->foreignId('consultation_id')->after('id')->constrained('consultations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_payments', function (Blueprint $table) {
            $table->dropForeign(['consultation_id']);
            $table->dropColumn('consultation_id');
            $table->foreignId('appointment_id')->nullable()->after('id')->constrained('appointments')->onDelete('cascade');
        });
    }
};
