<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\ClinicStoreRequest;
use App\Http\Requests\Clinic\ClinicUpdateRequest;
use App\Http\Requests\Clinic\ClinicWorkingHoursRequest;
use App\Models\Clinic;
use App\Models\DoctorMedicineSubscription;
use App\Services\Clinic\ClinicService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        $data = $request->validated();

        $this->assertActivePricingOrganizationIsSubscribed(
            $request,
            $data['active_pricing_organization_id'] ?? null
        );

        $clinic = Clinic::create($data);

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

        $data = $request->validated();

        if (array_key_exists('active_pricing_organization_id', $data)) {
            $this->assertActivePricingOrganizationIsSubscribed(
                $request,
                $data['active_pricing_organization_id']
            );
        }

        $clinic->update($data);

        return ApiResponse::success(
            message: 'Clinic updated successfully.',
            data: [
                'clinic' => $clinic->fresh(),
            ]
        );
    }

    /**
     * A clinic's active pricing organization must be one of the doctor's
     * own approved, non-expired subscriptions — it supplies medicine,
     * investigation and procedure pricing for every visit billed there.
     */
    private function assertActivePricingOrganizationIsSubscribed(
        Request $request,
        ?int $organizationId
    ): void {
        if ($organizationId === null) {
            return;
        }

        $doctor = $request->user();

        $subscription = DoctorMedicineSubscription::query()
            ->where('doctor_id', $doctor->id)
            ->where('organization_id', $organizationId)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (!$subscription) {
            throw ValidationException::withMessages([
                'active_pricing_organization_id' => [
                    'You must have an approved subscription to this organization before selecting it as the pricing source.',
                ],
            ]);
        }
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

    public function updateWorkingHours(
        ClinicWorkingHoursRequest $request,
        ClinicService $clinicService,
        int $clinicId
    ): JsonResponse {
        $clinic = $clinicService->updateWorkingHours(
            $clinicId,
            $request->input('working_hours')
        );

        return ApiResponse::success(
            message: 'Clinic working hours updated successfully.',
            data: [
                'clinic' => $clinic,
            ]
        );
    }

    public function workingHours(
        ClinicService $clinicService,
        int $clinicId
    ): JsonResponse {
        $clinic = $clinicService->getWorkingHours(
            $clinicId
        );

        return ApiResponse::success(
            message: 'Clinic working hours retrieved successfully.',
            data: [
                'clinic' => $clinic,
            ]
        );
    }
}
