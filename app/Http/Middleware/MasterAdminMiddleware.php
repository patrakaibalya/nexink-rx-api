<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MasterAdminMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (!$user instanceof \App\Models\MasterAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Master admin access required.',
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Master admin account is inactive.',
            ], 403);
        }

        return $next($request);
    }
}
