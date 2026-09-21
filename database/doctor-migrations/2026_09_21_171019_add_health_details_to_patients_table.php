<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('is_diabetic')->nullable()->after('height');
            $table->string('diabetic_result')->nullable()->after('is_diabetic');

            $table->string('blood_pressure')->nullable()->after('diabetic_result');
            $table->string('blood_pressure_result')->nullable()->after('blood_pressure');

            $table->string('uid_aadhar_no')->nullable()->after('blood_pressure_result');

            $table->string('thyroid_result')->nullable()->after('uid_aadhar_no');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'is_diabetic',
                'diabetic_result',
                'blood_pressure',
                'blood_pressure_result',
                'uid_aadhar_no',
                'thyroid_result',
            ]);
        });
    }
};
