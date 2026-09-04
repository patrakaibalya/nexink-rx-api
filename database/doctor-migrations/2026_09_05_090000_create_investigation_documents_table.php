<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('doctor')->create(
            'investigation_documents',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('investigation_id');

                /*
                |--------------------------------------------------------------------------
                | Uploaded Report File
                |--------------------------------------------------------------------------
                |
                | Store only the file path. Do NOT store the binary file in MySQL.
                |
                */
                $table->string('file_path');

                $table->string('original_name');

                $table->string('mime_type')->nullable();

                $table->unsignedBigInteger('size')->nullable();

                $table->timestamps();

                $table->index('investigation_id');
            }
        );
    }

    public function down(): void
    {
        Schema::connection('doctor')->dropIfExists(
            'investigation_documents'
        );
    }
};
