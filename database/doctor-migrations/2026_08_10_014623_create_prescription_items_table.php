<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('prescription_id')
                ->constrained('prescriptions')
                ->cascadeOnDelete();

            $table->string('medicine_name');

            $table->string('dosage')->nullable();

            $table->string('frequency')->nullable();

            $table->string('duration')->nullable();

            $table->string('route')->nullable();

            $table->text('instructions')->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->softDeletes();

            $table->index([
                'prescription_id',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
