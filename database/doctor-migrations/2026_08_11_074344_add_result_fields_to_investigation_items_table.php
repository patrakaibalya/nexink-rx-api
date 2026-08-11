<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investigation_items', function (Blueprint $table) {
            $table->text('result')->nullable()->after('instructions');
            $table->string('result_unit')->nullable()->after('result');
            $table->string('reference_range')->nullable()->after('result_unit');
            $table->text('result_notes')->nullable()->after('reference_range');
        });
    }

    public function down(): void
    {
        Schema::table('investigation_items', function (Blueprint $table) {
            $table->dropColumn([
                'result',
                'result_unit',
                'reference_range',
                'result_notes',
            ]);
        });
    }
};
