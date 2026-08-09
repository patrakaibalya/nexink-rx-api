<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClinicalExtraction\ClinicalExtractionConfirmRequest;
use App\Http\Requests\ClinicalExtraction\ClinicalExtractionStoreRequest;
use App\Services\ClinicalExtraction\ClinicalExtractionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClinicalExtractionController extends Controller
{
    public function store(
        ClinicalExtractionStoreRequest $request,
        ClinicalExtractionService $clinicalExtractionService,
        int $visitId
    ): JsonResponse {
        $extraction = $clinicalExtractionService->create(
            $visitId,
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Clinical extraction stored successfully.',
            data: [
                'extraction' => $extraction,
            ]
        );
    }

    public function show(
        ClinicalExtractionService $clinicalExtractionService,
        int $visitId
    ): JsonResponse {
        $extraction = $clinicalExtractionService->show($visitId);

        if (!$extraction) {
            return ApiResponse::success(
                message: 'No pending clinical extraction found.',
                data: [
                    'extraction' => null,
                ]
            );
        }

        return ApiResponse::success(
            message: 'Clinical extraction retrieved successfully.',
            data: [
                'extraction' => $extraction,
            ]
        );
    }

    public function confirm(
        ClinicalExtractionConfirmRequest $request,
        ClinicalExtractionService $clinicalExtractionService,
        int $visitId
    ): JsonResponse {
        $extraction = $clinicalExtractionService->confirm(
            $visitId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Clinical extraction confirmed successfully.',
            data: [
                'extraction' => $extraction,
            ]
        );
    }
}
