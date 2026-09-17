<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->eachOrganizationTable(function (string $table) {
            if (Schema::hasColumn($table, 'purchase_price')) {
                return;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->decimal('purchase_price', 10, 2)->nullable()->after('description');
                $table->decimal('sale_price', 10, 2)->nullable()->after('purchase_price');
            });
        });
    }

    public function down(): void
    {
        $this->eachOrganizationTable(function (string $table) {
            if (!Schema::hasColumn($table, 'purchase_price')) {
                return;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['purchase_price', 'sale_price']);
            });
        });
    }

    private function eachOrganizationTable(callable $callback): void
    {
        DB::table('medicine_organizations')
            ->pluck('id')
            ->each(function (int $organizationId) use ($callback) {
                $table = 'medicine_library_' . $organizationId;

                if (Schema::hasTable($table)) {
                    $callback($table);
                }
            });
    }
};
