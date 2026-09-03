<?php

namespace App\Http\Middleware;

use App\Models\DoctorWebSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

class DoctorWebOrSanctumMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (!$request->hasSession()) {
            return app(StartSession::class)->handle(
                $request,
                function ($request) use ($next) {
                    return $this->authenticate($request, $next);
                }
            );
        }

        return $this->authenticate($request, $next);
    }

    private function authenticate(
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

        if (!$request->bearerToken()) {
            $webSession = DoctorWebSession::query()
                ->where('doctor_id', $doctor->id)
                ->where('session_id', $request->session()->getId())
                ->where('revoked', false)
                ->first();

            if (!$webSession) {
                return response()->json([
                    'message' => 'Web session expired.',
                ], 401);
            }
        }

        $request->setUserResolver(
            fn() => $doctor
        );

        return $next($request);
    }
}
