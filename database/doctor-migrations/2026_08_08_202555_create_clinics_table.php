<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->string('address')->nullable();

            $table->string('city')->nullable();

            $table->string('state')->nullable();

            $table->string('pincode', 20)->nullable();

            $table->string('mobile', 20)->nullable();

            $table->string('email')->nullable();

            $table->string('timezone')->default('Asia/Kolkata');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
