<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_medicine_library', function (Blueprint $table) {
            $table->id();

            $table->string('medicine_name');
            $table->string('generic_name')->nullable();
            $table->string('composition')->nullable();

            $table->string('strength')->nullable();
            $table->string('dosage_form')->nullable();

            $table->string('manufacturer')->nullable();

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('medicine_name');
            $table->index('generic_name');
            $table->index('manufacturer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_medicine_library');
    }
};
