<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DoctorRegisterRequest;
use App\Services\Auth\DoctorAuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DoctorAuthController extends Controller
{
    public function __construct(
        private readonly DoctorAuthService $doctorAuthService
    ) {
    }

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
}
