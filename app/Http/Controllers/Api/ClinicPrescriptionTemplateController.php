<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\ClinicPrescriptionTemplateRequest;
use App\Services\Clinic\ClinicPrescriptionTemplateService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClinicPrescriptionTemplateController extends Controller
{
    public function show(
        int $clinicId,
        ClinicPrescriptionTemplateService $service
    ): JsonResponse {
        $template = $service->show($clinicId);

        return ApiResponse::success(
            message: 'Prescription template retrieved successfully.',
            data: [
                'prescription_template' => $template,
            ]
        );
    }

    public function store(
        ClinicPrescriptionTemplateRequest $request,
        ClinicPrescriptionTemplateService $service,
        int $clinicId
    ): JsonResponse {
        $template = $service->upsert(
            clinicId: $clinicId,
            data: $request->validated(),
            headerImage: $request->file('header_image'),
            footerImage: $request->file('footer_image')
        );

        return ApiResponse::success(
            message: 'Prescription template saved successfully.',
            data: [
                'prescription_template' => $template,
            ]
        );
    }

    public function headerImage(
        int $clinicId,
        ClinicPrescriptionTemplateService $service
    ) {
        $template = $service->show($clinicId);

        if (!$template || !$template->header_image_path) {
            return ApiResponse::notFound(
                'Prescription template header image not found.'
            );
        }

        return $service->downloadHeaderImage($template);
    }

    public function footerImage(
        int $clinicId,
        ClinicPrescriptionTemplateService $service
    ) {
        $template = $service->show($clinicId);

        if (!$template || !$template->footer_image_path) {
            return ApiResponse::notFound(
                'Prescription template footer image not found.'
            );
        }

        return $service->downloadFooterImage($template);
    }
}
