<?php

namespace App\Http\Middleware;

use App\Models\MedicineOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MedicineOrganizationMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user instanceof MedicineOrganization) {
            return response()->json([
                'success' => false,
                'message' => 'Medicine organization access required.',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Medicine organization account is inactive.',
            ], 403);
        }

        return $next($request);
    }
}
