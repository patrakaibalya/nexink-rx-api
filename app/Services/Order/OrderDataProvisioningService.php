<?php

namespace App\Services\Order;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrderDataProvisioningService
{
    public function createForOrganization(int $organizationId): string
    {
        $tableName = $this->getTableName($organizationId);

        if (Schema::hasTable($tableName)) {
            return $tableName;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('prescription_share_id')->nullable()->index();

            $table->string('order_ref_id')->unique();

            $table->json('patient_snapshot');
            $table->string('doctor_name')->nullable();

            $table->enum('status', [
                'converting',
                'draft',
                'conversion_failed',
                'submitted',
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
            ])->default('pending');

            $table->json('items');
            $table->json('ai_meta')->nullable();
            $table->text('ai_conversion_error')->nullable();

            $table->decimal('grand_total', 12, 2)->default(0);

            $table->timestamps();

            $table->index('status');
        });

        return $tableName;
    }

    public function getTableName(int $organizationId): string
    {
        return 'order_data_' . $organizationId;
    }
}
