<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctorWebOrSanctumMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $doctor = auth('sanctum')->user();

        if (!$doctor) {
            $doctor = auth('doctor_web')->user();
        }

        if (!$doctor) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->setUserResolver(
            fn() => $doctor
        );

        return $next($request);
    }
}
