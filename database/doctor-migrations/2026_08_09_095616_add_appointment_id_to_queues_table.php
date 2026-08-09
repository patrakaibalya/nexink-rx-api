<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->foreignId('appointment_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('appointments')
                ->nullOnDelete();

            $table->unique('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropForeign([
                'appointment_id',
            ]);

            $table->dropUnique([
                'queues_appointment_id_unique',
            ]);

            $table->dropColumn('appointment_id');
        });
    }
};
