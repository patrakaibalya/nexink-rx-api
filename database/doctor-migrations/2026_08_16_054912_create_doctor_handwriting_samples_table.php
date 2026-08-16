<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('doctor')->create(
            'doctor_handwriting_samples',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Doctor
                |--------------------------------------------------------------------------
                */
                $table->unsignedBigInteger('doctor_id');

                /*
                |--------------------------------------------------------------------------
                | Handwriting Sample Type
                |--------------------------------------------------------------------------
                |
                | Examples:
                | about_me
                | prescription
                |
                */
                $table->string('sample_type');

                /*
                |--------------------------------------------------------------------------
                | Related Prescription
                |--------------------------------------------------------------------------
                |
                | NULL for About Me.
                |
                | Used only when:
                | sample_type = prescription
                |
                */
                $table->unsignedBigInteger(
                    'prescription_id'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Ink Tool Configuration
                |--------------------------------------------------------------------------
                */
                $table->json('tool_data')->nullable();

                /*
                |--------------------------------------------------------------------------
                | ML Kit Recognition
                |--------------------------------------------------------------------------
                */
                $table->longText(
                    'raw_recognized_text'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Final Doctor Approved Text
                |--------------------------------------------------------------------------
                */
                $table->longText(
                    'final_corrected_text'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Original Handwriting File
                |--------------------------------------------------------------------------
                |
                | Store only the file path.
                | Do NOT store the binary file in MySQL.
                |
                */
                $table->string(
                    'ink_file_path'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Qdrant Processing Status
                |--------------------------------------------------------------------------
                */
                $table->string(
                    'qdrant_status'
                )->default('pending');

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */
                $table->index('doctor_id');

                $table->index('sample_type');

                $table->index('prescription_id');

                $table->index('qdrant_status');

                /*
                |--------------------------------------------------------------------------
                | One About Me Sample Per Doctor
                |--------------------------------------------------------------------------
                |
                | We will enforce this in the service query.
                | Do not use a unique(doctor_id, sample_type) here because
                | prescription allows multiple samples.
                |
                */
            }
        );
    }

    public function down(): void
    {
        Schema::connection('doctor')->dropIfExists(
            'doctor_handwriting_samples'
        );
    }
};
