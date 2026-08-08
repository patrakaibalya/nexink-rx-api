<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinic_id')
                ->constrained('clinics')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->unsignedInteger('queue_number');

            $table->date('queue_date');

            $table->enum('source', [
                'web',
                'vapi',
                'patient',
            ]);

            $table->enum('status', [
                'waiting',
                'called',
                'consulting',
                'completed',
                'cancelled',
                'no_show',
            ])->default('waiting');

            $table->timestamp('arrived_at')->nullable();

            $table->timestamp('called_at')->nullable();

            $table->timestamp('consultation_started_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index([
                'clinic_id',
                'queue_date',
            ]);

            $table->index([
                'clinic_id',
                'queue_date',
                'status',
            ]);

            $table->unique([
                'clinic_id',
                'queue_date',
                'queue_number',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
