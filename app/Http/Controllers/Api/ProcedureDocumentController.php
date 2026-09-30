<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procedure\ProcedureDocumentRequest;
use App\Http\Requests\Procedure\ProcedureDocumentUploadRequest;
use App\Services\Procedure\ProcedureDocumentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProcedureDocumentController extends Controller
{
    public function store(
        ProcedureDocumentUploadRequest $request,
        ProcedureDocumentService $service,
        int $procedureId
    ): JsonResponse {
        $procedure = $service->store(
            $procedureId,
            $request->uploadedFiles()
        );

        return ApiResponse::created(
            message: 'Procedure documents uploaded successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function replace(
        ProcedureDocumentRequest $request,
        ProcedureDocumentService $service,
        int $procedureId,
        int $documentId
    ): JsonResponse {
        $procedure = $service->replace(
            $procedureId,
            $documentId,
            $request->file('document')
        );

        return ApiResponse::success(
            message: 'Procedure document updated successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function destroy(
        ProcedureDocumentService $service,
        int $procedureId,
        int $documentId
    ): JsonResponse {
        $procedure = $service->destroy(
            $procedureId,
            $documentId
        );

        return ApiResponse::success(
            message: 'Procedure document deleted successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function file(
        ProcedureDocumentService $service,
        int $procedureId,
        int $documentId
    ) {
        $document = $service->find($procedureId, $documentId);

        return $service->download($document);
    }
}
