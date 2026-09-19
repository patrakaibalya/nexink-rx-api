<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicineOrganization\PrescriptionShareStatusRequest;
use App\Models\ClinicalExtraction;
use App\Models\DoctorHandwritingSample;
use App\Models\DoctorMedicineSubscription;
use App\Models\Prescription;
use App\Models\PrescriptionShare;
use App\Services\Clinic\ClinicPrescriptionTemplateService;
use App\Services\Doctor\DoctorHandwritingStrokeService;
use App\Support\ApiResponse;
use App\Support\DoctorTenantConnector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionShareController extends Controller
{
    public function doctorList(Request $request): JsonResponse
    {
        $doctor = $request->user();

        $shares = PrescriptionShare::query()
            ->where('doctor_id', $doctor->id)
            ->when(
                $request->query('status'),
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $request->query('organization_id'),
                fn ($query, $organizationId) => $query->where('organization_id', $organizationId)
            )
            ->with('organization:id,organization_name')
            ->latest('id')
            ->paginate(20);

        return ApiResponse::success(
            message: 'Shared prescriptions retrieved successfully.',
            data: [
                'shares' => $shares,
            ]
        );
    }

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

        $shareStatus = $this->resolveShareStatus(
            $doctor->id,
            $prescriptionId,
            $shares->isNotEmpty()
        );

        return ApiResponse::success(
            message: 'Prescription share status retrieved successfully.',
            data: [
                'share_status' => $shareStatus,
                'shares' => $shares,
            ]
        );
    }

    /**
     * Explain why `shares` may be empty, so the doctor's UI can show
     * something more useful than a blank list.
     */
    private function resolveShareStatus(
        int $doctorId,
        int $prescriptionId,
        bool $hasShares
    ): string {
        if ($hasShares) {
            return 'shared';
        }

        $hasApprovedSubscribers = DoctorMedicineSubscription::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (!$hasApprovedSubscribers) {
            return 'no_subscribers';
        }

        $prescription = Prescription::query()->find($prescriptionId);

        if (!$prescription) {
            return 'prescription_not_found';
        }

        $isConfirmed = ClinicalExtraction::query()
            ->where('visit_id', $prescription->visit_id)
            ->where('status', 'confirmed')
            ->exists();

        return $isConfirmed ? 'pending' : 'not_confirmed_yet';
    }

    public function index(Request $request): JsonResponse
    {
        $organization = $request->user();

        $shares = PrescriptionShare::query()
            ->where('organization_id', $organization->id)
            ->with('doctor:id,name')
            ->when(
                $request->query('status'),
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $request->query('doctor_id'),
                fn ($query, $doctorId) => $query->where('doctor_id', $doctorId)
            )
            ->when(
                $request->query('from_date'),
                fn ($query, $fromDate) => $query->whereDate('shared_at', '>=', $fromDate)
            )
            ->when(
                $request->query('to_date'),
                fn ($query, $toDate) => $query->whereDate('shared_at', '<=', $toDate)
            )
            ->when(
                $request->query('search'),
                fn ($query, $search) => $query->where('patient_snapshot', 'like', "%{$search}%")
            )
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

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

    public function headerImage(
        Request $request,
        ClinicPrescriptionTemplateService $service,
        int $shareId
    ) {
        $clinicId = $this->resolveShareClinicId($request, $shareId);

        if ($clinicId instanceof JsonResponse) {
            return $clinicId;
        }

        $template = $service->show($clinicId);

        if (!$template || !$template->header_image_path) {
            return ApiResponse::notFound(
                'Prescription template header image not found.'
            );
        }

        return $service->downloadHeaderImage($template);
    }

    public function footerImage(
        Request $request,
        ClinicPrescriptionTemplateService $service,
        int $shareId
    ) {
        $clinicId = $this->resolveShareClinicId($request, $shareId);

        if ($clinicId instanceof JsonResponse) {
            return $clinicId;
        }

        $template = $service->show($clinicId);

        if (!$template || !$template->footer_image_path) {
            return ApiResponse::notFound(
                'Prescription template footer image not found.'
            );
        }

        return $service->downloadFooterImage($template);
    }

    /**
     * Resolve the clinic behind a shared prescription, connecting to the
     * owning doctor's tenant database first since the prescription and
     * clinic records live there rather than in the master database.
     */
    private function resolveShareClinicId(
        Request $request,
        int $shareId
    ): int|JsonResponse {
        $organization = $request->user();

        $share = PrescriptionShare::query()
            ->where('organization_id', $organization->id)
            ->find($shareId);

        if (!$share) {
            return ApiResponse::notFound(
                'Shared prescription not found.'
            );
        }

        if (!DoctorTenantConnector::connect($share->doctor_id)) {
            return ApiResponse::error(
                'Doctor database is not available.',
                null,
                503
            );
        }

        $prescription = Prescription::query()->find($share->prescription_id);

        if (!$prescription) {
            return ApiResponse::notFound(
                'Prescription not found.'
            );
        }

        return $prescription->clinic_id;
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
