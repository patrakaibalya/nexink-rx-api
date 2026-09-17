<?php

namespace App\Services\Medicine;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MedicineLibraryProvisioningService
{
    public function createForOrganization(int $organizationId): string
    {
        $tableName = $this->getTableName($organizationId);

        if (Schema::hasTable($tableName)) {
            return $tableName;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();

            $table->string('medicine_name');
            $table->string('generic_name')->nullable();
            $table->string('composition')->nullable();

            $table->string('strength')->nullable();
            $table->string('dosage_form')->nullable();

            $table->string('manufacturer')->nullable();

            $table->text('description')->nullable();

            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();

            $table->unsignedInteger('unit')->nullable();
            $table->string('unit_type')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('medicine_name');
            $table->index('generic_name');
            $table->index('manufacturer');
        });

        return $tableName;
    }

    public function getTableName(int $organizationId): string
    {
        return 'medicine_library_' . $organizationId;
    }
}
