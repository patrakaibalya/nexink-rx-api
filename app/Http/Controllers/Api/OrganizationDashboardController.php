<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\OrganizationDashboardSummaryRequest;
use App\Services\Dashboard\OrganizationDashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrganizationDashboardController extends Controller
{
    public function summary(
        OrganizationDashboardSummaryRequest $request,
        OrganizationDashboardService $dashboardService
    ): JsonResponse {
        $organization = $request->user();

        $summary = $dashboardService->summary(
            $organization->id,
            $request->date('from_date')?->toDateString(),
            $request->date('to_date')?->toDateString()
        );

        return ApiResponse::success(
            message: 'Organization dashboard summary retrieved successfully.',
            data: [
                'dashboard' => $summary,
            ]
        );
    }
}
