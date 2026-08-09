<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigations', function (Blueprint $table) {
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

            $table->date('investigation_date');

            $table->string('status')
                ->default('ordered');

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index([
                'clinic_id',
                'investigation_date',
            ]);

            $table->index([
                'patient_id',
                'investigation_date',
            ]);

            $table->index([
                'visit_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigations');
    }
};