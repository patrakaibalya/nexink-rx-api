<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicineOrganization\PrescriptionShareStatusRequest;
use App\Models\DoctorHandwritingSample;
use App\Models\PrescriptionShare;
use App\Services\Doctor\DoctorHandwritingStrokeService;
use App\Support\ApiResponse;
use App\Support\DoctorTenantConnector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionShareController extends Controller
{
    public function doctorIndex(
        Request $request,
        int $prescriptionId
    ): JsonResponse {
        $doctor = $request->user();

        $shares = PrescriptionShare::query()
            ->where('doctor_id', $doctor->id)
            ->where('prescription_id', $prescriptionId)
            ->with('organization:id,organization_name')
            ->latest('id')
            ->get();

        return ApiResponse::success(
            message: 'Prescription share status retrieved successfully.',
            data: [
                'shares' => $shares,
            ]
        );
    }

    public function index(Request $request): JsonResponse
    {
        $organization = $request->user();

        $shares = PrescriptionShare::query()
            ->where('organization_id', $organization->id)
            ->when(
                $request->query('status'),
                fn ($query, $status) => $query->where('status', $status)
            )
            ->latest('id')
            ->paginate(20);

        return ApiResponse::success(
            message: 'Shared prescriptions retrieved successfully.',
            data: [
                'shares' => $shares,
            ]
        );
    }

    public function show(
        Request $request,
        int $shareId
    ): JsonResponse {
        $organization = $request->user();

        $share = PrescriptionShare::query()
            ->where('organization_id', $organization->id)
            ->find($shareId);

        if (!$share) {
            return ApiResponse::notFound(
                'Shared prescription not found.'
            );
        }

        if ($share->status === 'sent') {
            $share->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }

        return ApiResponse::success(
            message: 'Shared prescription retrieved successfully.',
            data: [
                'share' => $share,
            ]
        );
    }

    public function handwritingStrokes(
        Request $request,
        DoctorHandwritingStrokeService $strokeService,
        int $shareId
    ): JsonResponse {
        $organization = $request->user();

        $share = PrescriptionShare::query()
            ->where('organization_id', $organization->id)
            ->find($shareId);

        if (!$share) {
            return ApiResponse::notFound(
                'Shared prescription not found.'
            );
        }

        if (!$share->has_handwriting_sample) {
            return ApiResponse::notFound(
                'No handwriting sample available for this prescription.'
            );
        }

        if (!DoctorTenantConnector::connect($share->doctor_id)) {
            return ApiResponse::error(
                'Doctor database is not available.',
                null,
                503
            );
        }

        $sample = DoctorHandwritingSample::query()
            ->where('sample_type', 'prescription')
            ->where('prescription_id', $share->prescription_id)
            ->first();

        if (!$sample || !$sample->ink_file_path) {
            return ApiResponse::notFound(
                'No handwriting sample available for this prescription.'
            );
        }

        try {
            $strokes = $strokeService->decode($sample);
        } catch (\RuntimeException $e) {
            return ApiResponse::error(
                message: $e->getMessage(),
                data: null,
                status: 500
            );
        }

        return ApiResponse::success(
            message: 'Original handwritten prescription retrieved successfully.',
            data: $strokes
        );
    }

    public function updateStatus(
        PrescriptionShareStatusRequest $request,
        int $shareId
    ): JsonResponse {
        $organization = $request->user();

        $share = PrescriptionShare::query()
            ->where('organization_id', $organization->id)
            ->find($shareId);

        if (!$share) {
            return ApiResponse::notFound(
                'Shared prescription not found.'
            );
        }

        $status = $request->validated('status');

        $timestamps = [];

        if ($status === 'dispensed') {
            $timestamps['dispensed_at'] = now();
        }

        $share->update([
            'status' => $status,
            ...$timestamps,
        ]);

        return ApiResponse::success(
            message: 'Shared prescription status updated successfully.',
            data: [
                'share' => $share->fresh(),
            ]
        );
    }
}
