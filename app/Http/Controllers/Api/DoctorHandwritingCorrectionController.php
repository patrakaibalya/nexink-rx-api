<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\CorrectDoctorHandwritingRequest;
use App\Services\Doctor\DoctorHandwritingCorrectionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

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
}
