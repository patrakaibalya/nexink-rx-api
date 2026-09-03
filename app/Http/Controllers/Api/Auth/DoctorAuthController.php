<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DoctorLoginRequest;
use App\Http\Requests\Auth\DoctorRegisterRequest;
use App\Http\Requests\Doctor\DoctorChangePasswordRequest;
use App\Http\Requests\Doctor\DoctorProfileUpdateRequest;
use App\Models\DoctorWebSession;
use App\Services\Auth\DoctorAuthService;
use App\Support\ApiResponse;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DoctorAuthController extends Controller
{
    public function __construct(
        private readonly DoctorAuthService $doctorAuthService
    ) {}

    public function register(DoctorRegisterRequest $request): JsonResponse
    {
        $doctor = $this->doctorAuthService->register(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Doctor registered successfully.',
            data: [
                'doctor' => [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'email' => $doctor->email,
                    'mobile' => $doctor->mobile,
                    'specialization' => $doctor->specialization,
                    'medical_license_number' => $doctor->medical_license_number,
                    'is_active' => $doctor->is_active,
                    'email_verified_at' => $doctor->email_verified_at,
                ],
            ]
        );
    }

    public function login(DoctorLoginRequest $request): JsonResponse
    {
        $result = $this->doctorAuthService->login(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Doctor login successful.',
            data: [
                'doctor' => [
                    'id' => $result['doctor']->id,
                    'name' => $result['doctor']->name,
                    'email' => $result['doctor']->email,
                    'mobile' => $result['doctor']->mobile,
                    'specialization' => $result['doctor']->specialization,
                    'medical_license_number' => $result['doctor']->medical_license_number,
                    'is_active' => $result['doctor']->is_active,
                    'email_verified_at' => $result['doctor']->email_verified_at,
                ],
                'token' => $result['token'],
            ]
        );
    }


    public function me(Request $request): JsonResponse
    {
        // $doctor = $request->user();

        $doctor = auth('sanctum')->user();

        if (!$doctor) {
            $doctor = auth('doctor_web')->user();
        }

        if (!$doctor) {
            return ApiResponse::unauthorized(
                message: 'Unauthenticated.'
            );
        }


        return ApiResponse::success(
            message: 'Doctor profile retrieved successfully.',
            data: [
                'doctor' => [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'email' => $doctor->email,
                    'mobile' => $doctor->mobile,
                    'specialization' => $doctor->specialization,
                    'medical_license_number' => $doctor->medical_license_number,
                    'is_active' => $doctor->is_active,
                    'email_verified_at' => $doctor->email_verified_at,
                ],
            ]
        );
    }

    public function logout(Request $request): JsonResponse
    {
        // Android logout
        if ($request->bearerToken()) {
            $doctor = $request->user();

            $token = $doctor?->currentAccessToken();

            if ($token) {
                $token->delete();
            }

            if ($doctor) {
                DoctorWebSession::where(
                    'doctor_id',
                    $doctor->id
                )->update([
                    'revoked' => true,
                ]);
            }

            return ApiResponse::success(
                message: 'Doctor logged out successfully.'
            );
        }

    // Web logout
        /** @var StatefulGuard $webGuard */
        $webGuard = auth()->guard('doctor_web');

        if ($webGuard->check()) {
            $webGuard->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return ApiResponse::success(
            message: 'Doctor logged out successfully.'
        );
    }

    public function updateProfile(
        DoctorProfileUpdateRequest $request
    ): JsonResponse {
        $doctor = $request->user();

        $doctor->update(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Doctor profile updated successfully.',
            data: [
                'doctor' => $doctor->fresh(),
            ]
        );
    }

    public function changePassword(
        DoctorChangePasswordRequest $request
    ): JsonResponse {
        $doctor = $request->user();

        $doctor->update([
            'password' => Hash::make(
                $request->validated('password')
            ),
        ]);

        return ApiResponse::success(
            message: 'Doctor password changed successfully.'
        );
    }
}
