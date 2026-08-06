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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('generic_name');
            $table->string('commercial_name')->nullable();
            $table->string('presentation')->nullable();
            $table->string('active_substance')->nullable();
            $table->string('route')->nullable();
            $table->string('concentration')->nullable();
            $table->string('status')->default('active');
            
            // Unique constraint to prevent duplicate entries
            $table->unique(['generic_name', 'commercial_name', 'presentation'], 'medication_unique');
            
            // Indexes for fast searching
            $table->index('generic_name');
            $table->index('commercial_name');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
