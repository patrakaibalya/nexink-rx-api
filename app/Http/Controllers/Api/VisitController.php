<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Visit\DirectVisitRequest;
use App\Http\Requests\Visit\VisitCompleteRequest;
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
}
