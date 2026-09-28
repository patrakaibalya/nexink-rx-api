<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visit\DirectVisitRequest;
use App\Http\Requests\Visit\VisitCompleteRequest;
use App\Http\Requests\Visit\VisitEmergencyCompleteRequest;
use App\Http\Requests\Visit\VisitIndexRequest;
use App\Http\Requests\Visit\VisitStartRequest;
use App\Http\Requests\Visit\VisitUpdateRequest;
use App\Services\Visit\VisitService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function start(
        VisitStartRequest $request,
        VisitService $visitService
    ): JsonResponse {
        $visit = $visitService->start(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Consultation started successfully.',
            data: [
                'visit' => $visit,
            ]
        );
    }

    public function index(
        VisitIndexRequest $request,
        VisitService $visitService
    ): JsonResponse {
        $visits = $visitService->index(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Visits retrieved successfully.',
            data: [
                'visits' => $visits,
            ]
        );
    }

    public function show(
        int $visitId,
        VisitService $visitService
    ): JsonResponse {
        $visit = $visitService->show($visitId);

        return ApiResponse::success(
            message: 'Visit retrieved successfully.',
            data: [
                'visit' => $visit,
            ]
        );
    }

    public function update(
        VisitUpdateRequest $request,
        VisitService $visitService,
        int $visitId
    ): JsonResponse {
        $visit = $visitService->update(
            $visitId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Visit updated successfully.',
            data: [
                'visit' => $visit,
            ]
        );
    }

    public function complete(
        VisitCompleteRequest $request,
        VisitService $visitService,
        int $visitId
    ): JsonResponse {
        $visit = $visitService->complete(
            $visitId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Consultation completed successfully.',
            data: [
                'visit' => $visit,
            ]
        );
    }

    public function emergencyComplete(
        VisitEmergencyCompleteRequest $request,
        VisitService $visitService,
        int $visitId
    ): JsonResponse {
        $visit = $visitService->emergencyComplete(
            $visitId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Consultation finished. Prescription saved as unverified for later review.',
            data: [
                'visit' => $visit,
            ]
        );
    }

    public function resumePending(
        VisitService $visitService
    ): JsonResponse {
        $result = $visitService->resumePending();

        return ApiResponse::success(
            message: $result['resume_available']
                ? 'Pending consultation found.'
                : 'No pending consultation to resume.',
            data: $result
        );
    }

    public function reopen(
        VisitService $visitService,
        int $visitId
    ): JsonResponse {
        $visit = $visitService->reopen($visitId);

        return ApiResponse::success(
            message: 'Visit reopened for review.',
            data: [
                'visit' => $visit,
            ]
        );
    }

    public function direct(
        DirectVisitRequest $request,
        VisitService $visitService
    ): JsonResponse {
        $visit = $visitService->direct(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Direct consultation started successfully.',
            data: [
                'visit' => $visit,
            ]
        );
    }

    public function resetDirect(
        VisitService $visitService,
        int $visitId
    ): JsonResponse {
        $visitService->resetDirect($visitId);

        return ApiResponse::success(
            message: 'Direct consultation reset successfully.',
            data: []
        );
    }
}
