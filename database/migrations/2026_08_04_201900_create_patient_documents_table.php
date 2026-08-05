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
        Schema::create('patient_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('file_name');         // Nombre único generado en disco
            $table->string('original_name');      // Nombre original del archivo
            $table->string('file_path');           // Ruta relativa en el disco público
            $table->string('mime_type');            // Tipo MIME (image/jpeg, video/mp4, application/pdf, etc.)
            $table->unsignedBigInteger('file_size'); // Tamaño en bytes
            $table->timestamps();

            $table->index('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_documents');
    }
};
