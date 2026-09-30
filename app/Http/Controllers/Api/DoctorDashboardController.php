<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DoctorDashboardSummaryRequest;
use App\Services\Dashboard\DoctorDashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DoctorDashboardController extends Controller
{
    public function summary(
        DoctorDashboardSummaryRequest $request,
        DoctorDashboardService $dashboardService
    ): JsonResponse {
        $summary = $dashboardService->summary(
            $request->integer('clinic_id'),
            $request->input('date')
        );

        return ApiResponse::success(
            message: 'Doctor dashboard summary retrieved successfully.',
            data: [
                'dashboard' => $summary,
            ]
        );
    }
}
