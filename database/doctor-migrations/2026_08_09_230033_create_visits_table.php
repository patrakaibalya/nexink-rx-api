<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinic_id')
                ->constrained('clinics')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->foreignId('appointment_id')
                ->nullable()
                ->constrained('appointments')
                ->nullOnDelete();

            $table->foreignId('queue_id')
                ->nullable()
                ->constrained('queues')
                ->nullOnDelete();

            $table->date('visit_date');

            $table->timestamp('started_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->string('status')->default('in_progress');

            $table->text('chief_complaint')->nullable();

            $table->text('clinical_notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index([
                'clinic_id',
                'visit_date',
            ]);

            $table->index([
                'patient_id',
                'visit_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
