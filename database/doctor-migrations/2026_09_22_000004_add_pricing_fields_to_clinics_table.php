<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->decimal('consultation_fee', 10, 2)
                ->nullable()
                ->after('appointment_duration_minutes');

            /*
            |--------------------------------------------------------------------------
            | Active Pricing Organization
            |--------------------------------------------------------------------------
            |
            | References medicine_organizations.id on the master database. No FK
            | constraint here: clinics lives in the doctor's own tenant database,
            | a separate physical connection from the master DB the organization
            | lives in. Validity (organization exists + doctor has an approved
            | subscription to it) is enforced in application code.
            |
            */
            $table->unsignedBigInteger('active_pricing_organization_id')
                ->nullable()
                ->after('consultation_fee');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'consultation_fee',
                'active_pricing_organization_id',
            ]);
        });
    }
};
