<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MedicineOrganizationLoginRequest;
use App\Http\Requests\Auth\MedicineOrganizationRegisterRequest;
use App\Http\Requests\MedicineOrganization\OrganizationChangePasswordRequest;
use App\Http\Requests\MedicineOrganization\OrganizationProfileUpdateRequest;
use App\Services\Auth\MedicineOrganizationAuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    public function register(
        MedicineOrganizationRegisterRequest $request,
        MedicineOrganizationAuthService $medicineOrganizationAuthService
    ): JsonResponse {
        $organization = $medicineOrganizationAuthService->register(
            $request
        );

        return ApiResponse::created(
            message: 'Medicine organization registered successfully.',
            data: [
                'organization' => $organization,
            ]
        );
    }

    public function updateProfile(
        OrganizationProfileUpdateRequest $request
    ): JsonResponse {
        $organization = $request->user();

        $organization->update(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Medicine organization profile updated successfully.',
            data: [
                'organization' => $organization->fresh(),
            ]
        );
    }

    public function changePassword(
        OrganizationChangePasswordRequest $request
    ): JsonResponse {
        $organization = $request->user();

        $organization->update([
            'password' => Hash::make(
                $request->validated('password')
            ),
        ]);

        return ApiResponse::success(
            message: 'Medicine organization password changed successfully.'
        );
    }
}
