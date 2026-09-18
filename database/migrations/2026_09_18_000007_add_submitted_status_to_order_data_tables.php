<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->eachOrderDataTable(function (string $tableName) {
            DB::statement("ALTER TABLE `{$tableName}` MODIFY `status` ENUM(
                'converting',
                'draft',
                'conversion_failed',
                'submitted',
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'");
        });
    }

    public function down(): void
    {
        $this->eachOrderDataTable(function (string $tableName) {
            DB::statement("ALTER TABLE `{$tableName}` MODIFY `status` ENUM(
                'converting',
                'draft',
                'conversion_failed',
                'pending',
                'processing',
                'shipped',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'");
        });
    }

    private function eachOrderDataTable(callable $callback): void
    {
        DB::table('medicine_organizations')
            ->pluck('id')
            ->each(function (int $organizationId) use ($callback) {
                $tableName = 'order_data_' . $organizationId;

                if (Schema::hasTable($tableName)) {
                    $callback($tableName);
                }
            });
    }
};
