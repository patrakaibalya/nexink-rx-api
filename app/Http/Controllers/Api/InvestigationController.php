<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investigation\InvestigationIndexRequest;
use App\Http\Requests\Investigation\InvestigationUpdateRequest;
use App\Services\Investigation\InvestigationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class InvestigationController extends Controller
{
    public function show(
        InvestigationService $investigationService,
        int $investigationId
    ): JsonResponse {
        $investigation = $investigationService->show(
            $investigationId
        );

        return ApiResponse::success(
            message: 'Investigation retrieved successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function update(
        InvestigationUpdateRequest $request,
        InvestigationService $investigationService,
        int $investigationId
    ): JsonResponse {
        $investigation = $investigationService->update(
            $investigationId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Investigation updated successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function destroy(
        InvestigationService $investigationService,
        int $investigationId
    ): JsonResponse {
        $investigationService->delete($investigationId);

        return ApiResponse::success(
            message: 'Investigation deleted successfully.',
            data: []
        );
    }

    public function index(
        InvestigationIndexRequest $request,
        InvestigationService $investigationService
    ): JsonResponse {
        $investigations = $investigationService->index(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Investigations retrieved successfully.',
            data: [
                'investigations' => $investigations,
            ]
        );
    }
}
