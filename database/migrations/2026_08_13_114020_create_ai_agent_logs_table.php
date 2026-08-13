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
        Schema::create('ai_agent_logs', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('tool')->nullable();
            $table->text('input')->nullable();
            $table->text('output')->nullable();
            $table->string('status')->default('success'); // success, error
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_agent_logs');
    }
};
