<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medicine_organizations', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('pincode', 10)->nullable()->after('state');

            $table->string('gst_number')->nullable()->after('pincode');
            $table->string('pan_number')->nullable()->after('gst_number');
            $table->string('drug_license_number')->nullable()->after('pan_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicine_organizations', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'state',
                'pincode',
                'gst_number',
                'pan_number',
                'drug_license_number',
            ]);
        });
    }
};
