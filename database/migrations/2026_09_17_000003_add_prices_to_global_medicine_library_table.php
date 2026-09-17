<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_medicine_library', function (Blueprint $table) {
            $table->decimal('purchase_price', 10, 2)->nullable()->after('description');
            $table->decimal('sale_price', 10, 2)->nullable()->after('purchase_price');
        });
    }

    public function down(): void
    {
        Schema::table('global_medicine_library', function (Blueprint $table) {
            $table->dropColumn(['purchase_price', 'sale_price']);
        });
    }
};
