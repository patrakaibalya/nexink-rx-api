<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MedicineOrganizationLoginRequest;
use App\Services\Auth\MedicineOrganizationAuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicineOrganizationAuthController extends Controller
{
    public function login(
        MedicineOrganizationLoginRequest $request,
        MedicineOrganizationAuthService $authService
    ): JsonResponse {
        $result = $authService->login(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Medicine organization login successful.',
            data: $result
        );
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            message: 'Medicine organization profile retrieved successfully.',
            data: [
                'organization' => $request->user(),
            ]
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return ApiResponse::success(
            message: 'Medicine organization logout successful.'
        );
    }
}
