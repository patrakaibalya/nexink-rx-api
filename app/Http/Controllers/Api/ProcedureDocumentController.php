<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procedure\ProcedureDocumentRequest;
use App\Services\Procedure\ProcedureDocumentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProcedureDocumentController extends Controller
{
    public function store(
        ProcedureDocumentRequest $request,
        ProcedureDocumentService $service,
        int $procedureId
    ): JsonResponse {
        $procedure = $service->store(
            $procedureId,
            $request->file('document')
        );

        return ApiResponse::created(
            message: 'Procedure document uploaded successfully.',
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
