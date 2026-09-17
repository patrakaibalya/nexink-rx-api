<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_shares', function (Blueprint $table) {
            $table->boolean('has_handwriting_sample')
                ->default(false)
                ->after('payload');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_shares', function (Blueprint $table) {
            $table->dropColumn('has_handwriting_sample');
        });
    }
};
