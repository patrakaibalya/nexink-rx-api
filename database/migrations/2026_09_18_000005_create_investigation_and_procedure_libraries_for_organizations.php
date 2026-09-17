<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->eachOrganization(function (int $organizationId) {
            $this->createLibraryTable('investigation_library_' . $organizationId, 'investigation_name');
            $this->createLibraryTable('procedure_library_' . $organizationId, 'procedure_name');
        });
    }

    public function down(): void
    {
        $this->eachOrganization(function (int $organizationId) {
            Schema::dropIfExists('investigation_library_' . $organizationId);
            Schema::dropIfExists('procedure_library_' . $organizationId);
        });
    }

    private function eachOrganization(callable $callback): void
    {
        DB::table('medicine_organizations')
            ->pluck('id')
            ->each(fn (int $organizationId) => $callback($organizationId));
    }

    private function createLibraryTable(string $tableName, string $nameColumn): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($nameColumn) {
            $table->id();

            $table->string($nameColumn);
            $table->text('description')->nullable();

            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();

            $table->unsignedInteger('unit')->nullable();
            $table->string('unit_type')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index($nameColumn);
        });
    }
};
