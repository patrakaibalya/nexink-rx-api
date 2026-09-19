<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorAccount;
use App\Models\DoctorMedicineSubscription;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationReportController extends Controller
{
    /**
     * Doctors currently subscribed to this organization (approved and not
     * expired, same definition as the dashboard's subscriber count) —
     * feeds the "Doctor" filter dropdown on the reports screen.
     */
    public function doctors(Request $request): JsonResponse
    {
        $organization = $request->user();

        $doctorIds = DoctorMedicineSubscription::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->distinct()
            ->pluck('doctor_id');

        $doctors = DoctorAccount::query()
            ->whereIn('id', $doctorIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return ApiResponse::success(
            message: 'Doctors retrieved successfully.',
            data: [
                'doctors' => $doctors,
            ]
        );
    }
}
