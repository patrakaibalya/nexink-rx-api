<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigation_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('investigation_id')
                ->constrained('investigations')
                ->cascadeOnDelete();

            $table->string('test_name');

            $table->string('test_type');

            $table->text('instructions')
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->softDeletes();

            $table->index([
                'investigation_id',
                'sort_order',
            ]);

            $table->index([
                'test_type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_items');
    }
};
