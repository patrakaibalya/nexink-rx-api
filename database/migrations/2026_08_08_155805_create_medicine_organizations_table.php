<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_organizations', function (Blueprint $table) {
            $table->id();

            $table->string('organization_name');

            $table->string('email')->unique();
            $table->string('mobile')->unique();

            $table->string('contact_person')->nullable();
            $table->text('address')->nullable();

            $table->string('password');

            $table->timestamp('email_verified_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_login_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_organizations');
    }
};
