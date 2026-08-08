<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\ClinicStoreRequest;
use App\Http\Requests\Clinic\ClinicUpdateRequest;
use App\Models\Clinic;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClinicController extends Controller
{
    public function index(): JsonResponse
    {
        $clinics = Clinic::query()
            ->latest()
            ->get();

        return ApiResponse::success(
            message: 'Clinics retrieved successfully.',
            data: [
                'clinics' => $clinics,
            ]
        );
    }

    public function store(ClinicStoreRequest $request): JsonResponse
    {
        $clinic = Clinic::create(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Clinic created successfully.',
            data: [
                'clinic' => $clinic,
            ]
        );
    }

    public function show(int $clinicId): JsonResponse
    {
        $clinic = Clinic::find($clinicId);

        if (!$clinic) {
            return ApiResponse::notFound(
                'Clinic not found.'
            );
        }

        return ApiResponse::success(
            message: 'Clinic retrieved successfully.',
            data: [
                'clinic' => $clinic,
            ]
        );
    }

    public function update(
        ClinicUpdateRequest $request,
        int $clinicId
    ): JsonResponse {
        $clinic = Clinic::find($clinicId);

        if (!$clinic) {
            return ApiResponse::notFound(
                'Clinic not found.'
            );
        }

        $clinic->update(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Clinic updated successfully.',
            data: [
                'clinic' => $clinic->fresh(),
            ]
        );
    }

    public function toggleStatus(int $clinicId): JsonResponse
    {
        $clinic = Clinic::find($clinicId);

        if (!$clinic) {
            return ApiResponse::notFound(
                'Clinic not found.'
            );
        }

        $clinic->update([
            'is_active' => !$clinic->is_active,
        ]);

        return ApiResponse::success(
            message: $clinic->is_active
                ? 'Clinic activated successfully.'
                : 'Clinic deactivated successfully.',
            data: [
                'clinic' => $clinic->fresh(),
            ]
        );
    }

    public function destroy(int $clinicId): JsonResponse
    {
        $clinic = Clinic::find($clinicId);

        if (!$clinic) {
            return ApiResponse::notFound(
                'Clinic not found.'
            );
        }

        $clinic->delete();

        return ApiResponse::success(
            message: 'Clinic deleted successfully.'
        );
    }


}
