<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinic_id')
                ->constrained('clinics')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->date('appointment_date');

            $table->time('appointment_time')->nullable();

            $table->enum('status', [
                'scheduled',
                'confirmed',
                'arrived',
                'completed',
                'cancelled',
                'no_show',
            ])->default('scheduled');

            $table->enum('source', [
                'web',
                'patient',
                'vapi',
                'staff',
            ])->default('web');

            $table->text('reason')->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('confirmed_at')->nullable();

            $table->timestamp('arrived_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index([
                'clinic_id',
                'appointment_date',
            ]);

            $table->index([
                'clinic_id',
                'appointment_date',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
