<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_extractions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinic_id')
                ->constrained('clinics')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->foreignId('visit_id')
                ->constrained('visits')
                ->cascadeOnDelete();

            $table->string('schema_version')
                ->default('1.0');

            $table->string('status')
                ->default('pending');

            $table->decimal('confidence', 5, 4)
                ->nullable();

            /*
             * Complete AI extraction JSON.
             */
            $table->json('payload');

            /*
             * Doctor-confirmed extraction timestamp.
             */
            $table->timestamp('confirmed_at')
                ->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index([
                'clinic_id',
                'patient_id',
            ]);

            $table->index([
                'visit_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_extractions');
    }
};
