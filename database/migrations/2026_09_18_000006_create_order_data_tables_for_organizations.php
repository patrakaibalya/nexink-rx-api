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
            $this->createOrderDataTable('order_data_' . $organizationId);
        });
    }

    public function down(): void
    {
        $this->eachOrganization(function (int $organizationId) {
            Schema::dropIfExists('order_data_' . $organizationId);
        });
    }

    private function eachOrganization(callable $callback): void
    {
        DB::table('medicine_organizations')
            ->pluck('id')
            ->each(fn (int $organizationId) => $callback($organizationId));
    }

    private function createOrderDataTable(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            return;
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
    }
};
