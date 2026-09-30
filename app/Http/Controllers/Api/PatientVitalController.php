<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\PatientVitalRequest;
use App\Services\Patient\PatientVitalService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientVitalController extends Controller
{
    public function index(
        Request $request,
        PatientVitalService $vitalService,
        int $patientId
    ): JsonResponse {
        $vitals = $vitalService->list(
            $patientId,
            min(max($request->integer('per_page', 20), 1), 100)
        );

        return ApiResponse::success(
            message: 'Patient vitals retrieved successfully.',
            data: [
                'vitals' => $vitals,
                'latest' => $vitalService->latestValues($patientId),
            ]
        );
    }

    public function store(
        PatientVitalRequest $request,
        PatientVitalService $vitalService,
        int $patientId
    ): JsonResponse {
        $vital = $vitalService->store(
            $patientId,
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Patient vitals recorded successfully.',
            data: [
                'vital' => $vital,
                'latest' => $vitalService->latestValues($patientId),
            ]
        );
    }

    public function update(
        PatientVitalRequest $request,
        PatientVitalService $vitalService,
        int $patientId,
        int $vitalId
    ): JsonResponse {
        $vital = $vitalService->update(
            $patientId,
            $vitalId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Patient vitals updated successfully.',
            data: [
                'vital' => $vital,
                'latest' => $vitalService->latestValues($patientId),
            ]
        );
    }

    public function destroy(
        PatientVitalService $vitalService,
        int $patientId,
        int $vitalId
    ): JsonResponse {
        $vitalService->delete($patientId, $vitalId);

        return ApiResponse::success(
            message: 'Patient vitals deleted successfully.',
            data: [
                'latest' => $vitalService->latestValues($patientId),
            ]
        );
    }
}
