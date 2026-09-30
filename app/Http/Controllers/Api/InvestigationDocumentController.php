<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investigation\InvestigationDocumentRequest;
use App\Http\Requests\Investigation\InvestigationDocumentUploadRequest;
use App\Services\Investigation\InvestigationDocumentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class InvestigationDocumentController extends Controller
{
    public function store(
        InvestigationDocumentUploadRequest $request,
        InvestigationDocumentService $service,
        int $investigationId
    ): JsonResponse {
        $investigation = $service->store(
            $investigationId,
            $request->uploadedFiles()
        );

        return ApiResponse::created(
            message: 'Investigation documents uploaded successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function replace(
        InvestigationDocumentRequest $request,
        InvestigationDocumentService $service,
        int $investigationId,
        int $documentId
    ): JsonResponse {
        $investigation = $service->replace(
            $investigationId,
            $documentId,
            $request->file('document')
        );

        return ApiResponse::success(
            message: 'Investigation document updated successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function destroy(
        InvestigationDocumentService $service,
        int $investigationId,
        int $documentId
    ): JsonResponse {
        $investigation = $service->destroy(
            $investigationId,
            $documentId
        );

        return ApiResponse::success(
            message: 'Investigation document deleted successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function file(
        InvestigationDocumentService $service,
        int $investigationId,
        int $documentId
    ) {
        $document = $service->find($investigationId, $documentId);

        return $service->download($document);
    }
}
