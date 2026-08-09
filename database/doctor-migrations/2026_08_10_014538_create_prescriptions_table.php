<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinic_id')
                ->constrained('clinics')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->foreignId('visit_id')
                ->unique()
                ->constrained('visits')
                ->cascadeOnDelete();

            $table->date('prescription_date');

            $table->text('notes')->nullable();

            $table->string('status')
                ->default('draft');

            $table->timestamps();

            $table->softDeletes();

            $table->index([
                'clinic_id',
                'prescription_date',
            ]);

            $table->index([
                'patient_id',
                'prescription_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
