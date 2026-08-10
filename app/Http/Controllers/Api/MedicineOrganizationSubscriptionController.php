<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicineOrganization\SubscriptionDecisionRequest;
use App\Models\DoctorMedicineSubscription;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicineOrganizationSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organization = $request->user();

        $subscriptions = DoctorMedicineSubscription::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->with('doctor')
            ->latest('id')
            ->paginate(20);

        return ApiResponse::success(
            message: 'Doctor subscription requests retrieved successfully.',
            data: [
                'subscriptions' => $subscriptions,
            ]
        );
    }

    public function update(
        SubscriptionDecisionRequest $request,
        int $subscriptionId
    ): JsonResponse {
        $organization = $request->user();

        $subscription = DoctorMedicineSubscription::query()
            ->where('id', $subscriptionId)
            ->where(
                'organization_id',
                $organization->id
            )
            ->first();

        if (!$subscription) {
            return ApiResponse::notFound(
                'Subscription request not found.'
            );
        }

        if ($subscription->status !== 'pending') {
            return ApiResponse::validationError(
                'Only pending subscription requests can be decided.'
            );
        }

        $status = $request->validated('status');

        $subscription->update([
            'status' => $status,
            'approved_at' => $status === 'approved'
                ? now()
                : null,
        ]);

        return ApiResponse::success(
            message: $status === 'approved'
                ? 'Doctor subscription approved successfully.'
                : 'Doctor subscription rejected successfully.',
            data: [
                'subscription' => $subscription->fresh(
                    'doctor'
                ),
            ]
        );
    }
}
