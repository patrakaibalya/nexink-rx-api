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
            if (Schema::hasColumn($table, 'unit')) {
                return;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('unit')->nullable()->after('sale_price');
                $table->string('unit_type')->nullable()->after('unit');
            });
        });
    }

    public function down(): void
    {
        $this->eachOrganizationTable(function (string $table) {
            if (!Schema::hasColumn($table, 'unit')) {
                return;
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['unit', 'unit_type']);
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
