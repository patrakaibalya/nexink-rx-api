<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\MedicineOrganizationSubscriptionRequest;
use App\Models\DoctorMedicineSubscription;
use App\Models\MedicineOrganization;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorMedicineOrganizationController extends Controller
{
    public function index(): JsonResponse
    {
        $doctor = request()->user();

        $subscriptions = DoctorMedicineSubscription::query()
            ->where('doctor_id', $doctor->id)
            ->with('organization')
            ->latest('id')
            ->paginate(20);

        return ApiResponse::success(
            message: 'Medicine organization subscriptions retrieved successfully.',
            data: [
                'subscriptions' => $subscriptions,
            ]
        );
    }

    public function subscribe(
        MedicineOrganizationSubscriptionRequest $request,
        int $organizationId
    ): JsonResponse {
        $doctor = $request->user();

        $organization = MedicineOrganization::find(
            $organizationId
        );

        if (!$organization) {
            return ApiResponse::notFound(
                'Medicine organization not found.'
            );
        }

        if (!$organization->is_active) {
            return ApiResponse::validationError(
                'Medicine organization is inactive.'
            );
        }

        $existing = DoctorMedicineSubscription::query()
            ->where('doctor_id', $doctor->id)
            ->where('organization_id', $organizationId)
            ->first();

        if ($existing) {
            return ApiResponse::validationError(
                'Subscription already exists.'
            );
        }

        $subscription = DoctorMedicineSubscription::create([
            'doctor_id' => $doctor->id,
            'organization_id' => $organizationId,
            'status' => 'pending',
            'is_favorite' => $request->validated('is_favorite', false),
            'requested_at' => now(),
        ]);

        return ApiResponse::created(
            message: 'Medicine organization subscription requested successfully.',
            data: [
                'subscription' => $subscription->load(
                    'organization'
                ),
            ]
        );
    }

    public function update(
        MedicineOrganizationSubscriptionRequest $request,
        int $organizationId
    ): JsonResponse {
        $doctor = $request->user();

        $subscription = DoctorMedicineSubscription::query()
            ->where('doctor_id', $doctor->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$subscription) {
            return ApiResponse::notFound(
                'Subscription not found.'
            );
        }

        $subscription->update(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Medicine organization subscription updated successfully.',
            data: [
                'subscription' => $subscription->fresh(
                    'organization'
                ),
            ]
        );
    }

    public function destroy(
        Request $request,
        int $organizationId
    ): JsonResponse {
        $doctor = $request->user();

        $subscription = DoctorMedicineSubscription::query()
            ->where('doctor_id', $doctor->id)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$subscription) {
            return ApiResponse::notFound(
                'Subscription not found.'
            );
        }

        $subscription->update([
            'status' => 'cancelled',
        ]);

        return ApiResponse::success(
            message: 'Medicine organization subscription cancelled successfully.',
        );
    }
}
