<?php

namespace App\Http\Middleware;

use App\Models\DoctorAccount;
use App\Support\ApiResponse;
use App\Support\DoctorTenantConnector;
use Closure;
use Illuminate\Http\Request;
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

        if (!$doctor->database) {
            return ApiResponse::error(
                'Doctor database configuration not found.',
                null,
                500
            );
        }

        if ($doctor->database->status !== 'active') {
            return ApiResponse::error(
                'Doctor database is not active.',
                null,
                503
            );
        }

        $doctorDatabase = DoctorTenantConnector::connect($doctor->id);

        app()->instance('current.doctor', $doctor);
        app()->instance('current.doctor.database', $doctorDatabase);

        return $next($request);
    }
}
