<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('doctor')->create(
            'doctor_about_me',
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
                | Ink Tool Configuration
                |--------------------------------------------------------------------------
                |
                | Android sends:
                | color
                | smoothing
                | tool_type
                | width
                |
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
                | Doctor Approved Text
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
                | Example:
                | about_me.ink.pb
                |
                | Store only the file path here.
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

                $table->index('qdrant_status');
            }
        );
    }

    public function down(): void
    {
        Schema::connection('doctor')->dropIfExists(
            'doctor_about_me'
        );
    }
};
