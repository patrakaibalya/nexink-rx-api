<?php

namespace App\Http\Middleware;

use App\Models\DoctorAccount;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DoctorTenantMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $doctor = $request->user();

        if (!$doctor instanceof DoctorAccount) {
            return ApiResponse::forbidden(
                'Doctor authentication required.'
            );
        }

        if (!$doctor->is_active) {
            return ApiResponse::forbidden(
                'Doctor account is inactive.'
            );
        }

        $doctorDatabase = $doctor->database;

        if (!$doctorDatabase) {
            return ApiResponse::error(
                'Doctor database configuration not found.',
                null,
                500
            );
        }

        if ($doctorDatabase->status !== 'active') {
            return ApiResponse::error(
                'Doctor database is not active.',
                null,
                503
            );
        }

        config([
            'database.connections.doctor' => [
                'driver' => 'mysql',
                'host' => $doctorDatabase->database_host,
                'port' => $doctorDatabase->database_port,
                'database' => $doctorDatabase->database_name,
                'username' => $doctorDatabase->database_username,
                'password' => $doctorDatabase->database_password,
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        ]);

        DB::purge('doctor');
        DB::reconnect('doctor');

        app()->instance('current.doctor', $doctor);
        app()->instance('current.doctor.database', $doctorDatabase);

        return $next($request);
    }
}
