<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_medicine_library', function (Blueprint $table) {
            $table->unsignedInteger('unit')->nullable()->after('sale_price');
            $table->string('unit_type')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('global_medicine_library', function (Blueprint $table) {
            $table->dropColumn(['unit', 'unit_type']);
        });
    }
};
