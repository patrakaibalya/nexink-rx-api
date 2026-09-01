<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('web_login_challenges', function (Blueprint $table) {
            $table->string('handoff_hash', 64)
                ->nullable()
                ->unique()
                ->after('status');

            $table->timestamp('handoff_expires_at')
                ->nullable()
                ->after('handoff_hash');
        });
    }

    public function down(): void
    {
        Schema::table('web_login_challenges', function (Blueprint $table) {
            $table->dropUnique([
                'handoff_hash',
            ]);

            $table->dropColumn([
                'handoff_hash',
                'handoff_expires_at',
            ]);
        });
    }
};
