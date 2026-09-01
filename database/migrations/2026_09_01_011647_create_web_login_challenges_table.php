<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_login_challenges', function (Blueprint $table) {
            $table->id();

            $table->string('challenge', 128)->unique();

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('doctor_accounts')
                ->nullOnDelete();

            $table->string('status', 30)
                ->default('waiting');

            $table->timestamp('expires_at');

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamp('consumed_at')
                ->nullable();

            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index('doctor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_login_challenges');
    }
};
