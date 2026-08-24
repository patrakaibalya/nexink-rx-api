<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClinicalExtraction\ClinicalExtractionConfirmRequest;
use App\Http\Requests\ClinicalExtraction\ClinicalExtractionRejectRequest;
use App\Http\Requests\ClinicalExtraction\ClinicalExtractionStoreRequest;
use App\Jobs\ClinicalExtractionJob;
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

        ClinicalExtractionJob::dispatch(
            $extraction->id,
            $request->user()->id
        );

        return ApiResponse::created(
            message: 'Clinical extraction processing started.',
            data: [
                'extraction' => [
                    'id' => $extraction->id,
                    'visit_id' => $extraction->visit_id,
                    'status' => $extraction->status,
                ],
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

    public function reject(
        ClinicalExtractionRejectRequest $request,
        ClinicalExtractionService $clinicalExtractionService,
        int $visitId
    ): JsonResponse {
        $extraction = $clinicalExtractionService->reject(
            $visitId,
            $request->validated('reason')
        );

        return ApiResponse::success(
            message: 'Clinical extraction rejected successfully.',
            data: [
                'extraction' => $extraction,
            ]
        );
    }
}
