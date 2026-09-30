<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\DoctorPatientReportRequest;
use App\Services\Report\DoctorReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DoctorReportController extends Controller
{
    public function patients(
        DoctorPatientReportRequest $request,
        DoctorReportService $reportService
    ): JsonResponse {
        $report = $reportService->patientReport(
            $request->integer('clinic_id'),
            $request->input('from_date'),
            $request->input('to_date')
        );

        return ApiResponse::success(
            message: 'Patient report retrieved successfully.',
            data: [
                'report' => $report,
            ]
        );
    }
}
