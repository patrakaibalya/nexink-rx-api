<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();

            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();

            $table->string('blood_group')->nullable();

            $table->text('address')->nullable();

            $table->decimal('weight', 6, 2)->nullable();
            $table->decimal('height', 6, 2)->nullable();

            $table->timestamps();

            $table->index('name');
            $table->index('mobile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
