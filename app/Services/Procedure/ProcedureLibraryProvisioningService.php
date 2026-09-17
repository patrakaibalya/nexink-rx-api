<?php

namespace App\Services\Procedure;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProcedureLibraryProvisioningService
{
    public function createForOrganization(int $organizationId): string
    {
        $tableName = $this->getTableName($organizationId);

        if (Schema::hasTable($tableName)) {
            return $tableName;
        }

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();

            $table->string('procedure_name');
            $table->text('description')->nullable();

            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();

            $table->unsignedInteger('unit')->nullable();
            $table->string('unit_type')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('procedure_name');
        });

        return $tableName;
    }

    public function getTableName(int $organizationId): string
    {
        return 'procedure_library_' . $organizationId;
    }
}
