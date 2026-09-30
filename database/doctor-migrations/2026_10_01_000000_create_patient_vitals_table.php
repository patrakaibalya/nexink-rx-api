<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_vitals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('patient_id')->index();
            $table->unsignedBigInteger('visit_id')->nullable()->index();
            $table->unsignedBigInteger('clinic_id')->nullable()->index();

            $table->decimal('weight', 6, 2)->nullable();
            $table->decimal('height', 6, 2)->nullable();

            // Readings are free text so staff can write e.g. "120/80", "98.6 F", "fasting 110"
            $table->string('blood_pressure_result', 100)->nullable();
            $table->string('pulse', 50)->nullable();
            $table->string('temperature', 50)->nullable();
            $table->string('spo2', 50)->nullable();
            $table->string('blood_sugar', 100)->nullable();
            $table->string('diabetic_result', 255)->nullable();
            $table->string('thyroid_result', 255)->nullable();

            $table->string('notes', 500)->nullable();
            $table->string('recorded_by', 100)->nullable();
            $table->timestamp('recorded_at')->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'recorded_at']);
        });

        // Keep what is already known: each patient's current values become
        // their first reading, dated when the patient record last changed.
        DB::table('patients')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNotNull('weight')
                    ->orWhereNotNull('height')
                    ->orWhereNotNull('blood_pressure_result')
                    ->orWhereNotNull('diabetic_result')
                    ->orWhereNotNull('thyroid_result');
            })
            ->orderBy('id')
            ->chunk(500, function ($patients) {
                $now = now();

                DB::table('patient_vitals')->insert(
                    $patients->map(fn ($patient) => [
                        'patient_id' => $patient->id,
                        'weight' => $patient->weight,
                        'height' => $patient->height,
                        'blood_pressure_result' => $patient->blood_pressure_result,
                        'diabetic_result' => $patient->diabetic_result,
                        'thyroid_result' => $patient->thyroid_result,
                        'recorded_by' => 'Patient profile',
                        'recorded_at' => $patient->updated_at ?? $patient->created_at ?? $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_vitals');
    }
};
