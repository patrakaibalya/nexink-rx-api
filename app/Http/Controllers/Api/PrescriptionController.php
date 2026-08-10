<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prescription\PrescriptionIndexRequest;
use App\Http\Requests\Prescription\PrescriptionUpdateRequest;
use App\Services\Prescription\PrescriptionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PrescriptionController extends Controller
{
    public function show(
        PrescriptionService $prescriptionService,
        int $prescriptionId
    ): JsonResponse {
        $prescription = $prescriptionService->show(
            $prescriptionId
        );

        return ApiResponse::success(
            message: 'Prescription retrieved successfully.',
            data: [
                'prescription' => $prescription,
            ]
        );
    }

    public function update(
        PrescriptionUpdateRequest $request,
        PrescriptionService $prescriptionService,
        int $prescriptionId
    ): JsonResponse {
        $prescription = $prescriptionService->update(
            $prescriptionId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Prescription updated successfully.',
            data: [
                'prescription' => $prescription,
            ]
        );
    }

    public function destroy(
        PrescriptionService $prescriptionService,
        int $prescriptionId
    ): JsonResponse {
        $prescriptionService->delete($prescriptionId);

        return ApiResponse::success(
            message: 'Prescription deleted successfully.',
            data: []
        );
    }

    public function index(
        PrescriptionIndexRequest $request,
        PrescriptionService $prescriptionService
    ): JsonResponse {
        $prescriptions = $prescriptionService->index(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Prescriptions retrieved successfully.',
            data: [
                'prescriptions' => $prescriptions,
            ]
        );
    }

    public function finalize(
        PrescriptionService $prescriptionService,
        int $prescriptionId
    ): JsonResponse {
        $prescription = $prescriptionService->finalize(
            $prescriptionId
        );

        return ApiResponse::success(
            message: 'Prescription finalized successfully.',
            data: [
                'prescription' => $prescription,
            ]
        );
    }
}
