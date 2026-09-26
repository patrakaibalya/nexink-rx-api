<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_handwriting_samples', function (Blueprint $table) {
            $table->unsignedInteger('page_number')
                ->default(1)
                ->after('prescription_id');

            $table->index('page_number');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_handwriting_samples', function (Blueprint $table) {
            $table->dropIndex(['page_number']);
            $table->dropColumn('page_number');
        });
    }
};
