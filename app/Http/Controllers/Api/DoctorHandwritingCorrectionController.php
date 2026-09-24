<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\CorrectDoctorHandwritingRequest;
use App\Services\Doctor\DoctorHandwritingCorrectionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorHandwritingCorrectionController extends Controller
{
    public function correct(
        CorrectDoctorHandwritingRequest $request,
        DoctorHandwritingCorrectionService $doctorHandwritingCorrectionService
    ): JsonResponse {
        $doctor = $request->user();

        $result = $doctorHandwritingCorrectionService->correct(
            doctorId: $doctor->id,
            rawRecognizedText: $request->validated(
                'raw_recognized_text'
            ),
            limit: $request->validated('limit') ?? 5
        );

        return ApiResponse::success(
            message: 'Handwriting corrected successfully.',
            data: $result
        );
    }

    /**
     * Bulk sync for the Android app's local Clinical Dictionary
     * (DictionaryManagementRepository.syncCorrectionsFromServer): every
     * word-level correction this doctor has ever confirmed, derived from
     * their stored handwriting samples rather than a live Qdrant lookup.
     */
    public function list(
        Request $request,
        DoctorHandwritingCorrectionService $doctorHandwritingCorrectionService
    ): JsonResponse {
        $doctor = $request->user();

        $data = Validator::make(
            $request->query(),
            [
                'since' => ['nullable', 'date'],
            ]
        )->validate();

        $result = $doctorHandwritingCorrectionService->listCorrections(
            doctorId: $doctor->id,
            since: $data['since'] ?? null
        );

        return ApiResponse::success(
            message: 'Doctor handwriting corrections retrieved successfully.',
            data: $result
        );
    }
}
