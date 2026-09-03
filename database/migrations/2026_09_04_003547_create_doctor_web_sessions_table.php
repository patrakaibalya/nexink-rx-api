<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_web_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')
                ->constrained('doctor_accounts')
                ->cascadeOnDelete();

            $table->string('session_id', 255)
                ->unique();

            $table->boolean('revoked')
                ->default(false);

            $table->timestamps();

            $table->index([
                'doctor_id',
                'revoked',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_web_sessions');
    }
};
