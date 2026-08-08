<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_databases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')
                ->unique()
                ->constrained('doctor_accounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('database_name')->unique();

            $table->string('database_host')->default('127.0.0.1');
            $table->unsignedSmallInteger('database_port')->default(3306);
            $table->string('database_username');
            $table->text('database_password')->nullable();

            $table->enum('status', [
                'provisioning',
                'active',
                'disabled',
                'failed',
            ])->default('provisioning');

            $table->timestamp('provisioned_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_databases');
    }
};
