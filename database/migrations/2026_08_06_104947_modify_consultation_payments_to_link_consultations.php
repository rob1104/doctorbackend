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
                // If it has a foreign key constraint, you might need to drop that first, 
                // but assuming it's just dropping the column or the user already dropped the constraint.
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
