<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MasterLoginRequest;
use App\Models\MasterAdmin;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MasterAuthController extends Controller
{
    public function login(
        MasterLoginRequest $request
    ): JsonResponse {
        $master = MasterAdmin::query()
            ->where('email', $request->validated('email'))
            ->first();

        if (
            !$master ||
            !Hash::check(
                $request->validated('password'),
                $master->password
            )
        ) {
            return ApiResponse::unauthorized(
                'Invalid email or password.'
            );
        }

        if (!$master->is_active) {
            return ApiResponse::forbidden(
                'Master account is inactive.'
            );
        }

        $token = $master->createToken(
            'master-api'
        )->plainTextToken;

        $master->update([
            'last_login_at' => now(),
        ]);

        return ApiResponse::success(
            message: 'Master login successful.',
            data: [
                'master' => $master->fresh(),
                'token' => $token,
            ]
        );
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            message: 'Master profile retrieved successfully.',
            data: [
                'master' => $request->user(),
            ]
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return ApiResponse::success(
            message: 'Master logout successful.'
        );
    }
}
