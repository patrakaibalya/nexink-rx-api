<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_medicine_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')
                ->constrained('doctor_accounts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('organization_id')
                ->constrained('medicine_organizations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled',
                'expired',
            ])->default('pending');

            $table->boolean('is_favorite')->default(false);

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['doctor_id', 'organization_id'],
                'doctor_organization_unique'
            );

            $table->index(
                ['doctor_id', 'status'],
                'doctor_subscription_status_index'
            );

            $table->index(
                ['organization_id', 'status'],
                'organization_subscription_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_medicine_subscriptions');
    }
};
