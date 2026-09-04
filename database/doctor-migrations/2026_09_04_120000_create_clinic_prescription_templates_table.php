<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('doctor')->create(
            'clinic_prescription_templates',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Clinic
                |--------------------------------------------------------------------------
                |
                | One prescription letterhead design per clinic.
                |
                */
                $table->unsignedBigInteger('clinic_id');

                /*
                |--------------------------------------------------------------------------
                | A4 Design Canvas
                |--------------------------------------------------------------------------
                |
                | The reference page size (px) the header/footer width & height
                | were measured against, so Android can scale proportionally.
                |
                */
                $table->unsignedInteger('page_width')->default(794);

                $table->unsignedInteger('page_height')->default(1123);

                /*
                |--------------------------------------------------------------------------
                | Header Image
                |--------------------------------------------------------------------------
                */
                $table->string('header_image_path')->nullable();

                $table->unsignedInteger('header_width')->nullable();

                $table->unsignedInteger('header_height')->nullable();

                /*
                |--------------------------------------------------------------------------
                | Footer Image
                |--------------------------------------------------------------------------
                */
                $table->string('footer_image_path')->nullable();

                $table->unsignedInteger('footer_width')->nullable();

                $table->unsignedInteger('footer_height')->nullable();

                $table->timestamps();

                $table->unique('clinic_id');
            }
        );
    }

    public function down(): void
    {
        Schema::connection('doctor')->dropIfExists(
            'clinic_prescription_templates'
        );
    }
};
