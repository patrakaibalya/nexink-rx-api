<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wrong -> correct word pairs a doctor added by hand on the Android
     * Clinical Dictionary screen. Corrections made during prescription
     * Review are not stored here - they are derived from
     * doctor_handwriting_samples. Kept so a reinstall can restore them.
     */
    public function up(): void
    {
        Schema::create('doctor_manual_corrections', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('doctor_id')->index();
            $table->string('wrong_word', 191);
            $table->string('correct_word', 191);
            // Clinical Dictionary category, e.g. Medicine, Dosage, Symptom, Instruction
            $table->string('category', 50);

            $table->timestamps();

            $table->unique(
                ['doctor_id', 'wrong_word', 'correct_word', 'category'],
                'doctor_manual_corrections_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_manual_corrections');
    }
};
