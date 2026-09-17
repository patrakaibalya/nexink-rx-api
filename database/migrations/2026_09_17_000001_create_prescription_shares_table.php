<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_shares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')
                ->constrained('doctor_accounts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('organization_id')
                ->constrained('medicine_organizations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * These reference rows in the doctor's own tenant database,
             * so they are plain ids without a foreign key constraint.
             */
            $table->unsignedBigInteger('clinical_extraction_id');
            $table->unsignedBigInteger('visit_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('prescription_id');

            $table->json('patient_snapshot');
            $table->json('clinic_snapshot')->nullable();
            $table->json('payload');

            $table->enum('status', [
                'sent',
                'viewed',
                'dispensed',
                'cancelled',
            ])->default('sent');

            $table->timestamp('shared_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('dispensed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['organization_id', 'clinical_extraction_id'],
                'organization_extraction_unique'
            );

            $table->index(
                ['organization_id', 'status'],
                'organization_status_index'
            );

            $table->index(
                ['doctor_id', 'visit_id'],
                'doctor_visit_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_shares');
    }
};
