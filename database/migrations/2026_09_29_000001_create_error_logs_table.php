<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();

            // Who hit the error
            $table->string('user_type', 50)->nullable(); // doctor | medicine_organization | master_admin | guest
            $table->unsignedBigInteger('login_user_id')->nullable();
            $table->string('name')->nullable();

            // Where it happened
            $table->string('platform', 20)->default('server'); // android | ios | web | server
            $table->string('screen_name')->nullable();
            $table->string('function_name')->nullable();

            // What happened
            $table->text('error_description');
            $table->string('error_code', 100)->nullable();
            $table->string('exception_class')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->string('severity', 20)->default('medium'); // low | medium | high | critical

            // Request context
            $table->string('api_endpoint', 500)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('request_payload')->nullable();

            // Client context
            $table->string('app_version', 50)->nullable();
            $table->string('device_info')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Resolution workflow (master admin)
            $table->string('status', 20)->default('open'); // open | in_progress | resolved | ignored
            $table->foreignId('resolved_by')->nullable()->constrained('master_admins')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();

            $table->timestamps();

            $table->index(['user_type', 'login_user_id']);
            $table->index(['status', 'created_at']);
            $table->index('screen_name');
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
