<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_procedure_library', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('global_procedure_library');
    }
};
