<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procedure\ProcedureCompleteRequest;
use App\Http\Requests\Procedure\ProcedureIndexRequest;
use App\Http\Requests\Procedure\ProcedureUpdateRequest;
use App\Services\Procedure\ProcedureService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProcedureController extends Controller
{
    public function show(
        ProcedureService $procedureService,
        int $procedureId
    ): JsonResponse {
        $procedure = $procedureService->show(
            $procedureId
        );

        return ApiResponse::success(
            message: 'Procedure retrieved successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function update(
        ProcedureUpdateRequest $request,
        ProcedureService $procedureService,
        int $procedureId
    ): JsonResponse {
        $procedure = $procedureService->update(
            $procedureId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Procedure updated successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function destroy(
        ProcedureService $procedureService,
        int $procedureId
    ): JsonResponse {
        $procedureService->delete($procedureId);

        return ApiResponse::success(
            message: 'Procedure deleted successfully.',
            data: []
        );
    }

    public function index(
        ProcedureIndexRequest $request,
        ProcedureService $procedureService
    ): JsonResponse {
        $procedures = $procedureService->index(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Procedures retrieved successfully.',
            data: [
                'procedures' => $procedures,
            ]
        );
    }

    public function complete(
        ProcedureCompleteRequest $request,
        ProcedureService $procedureService,
        int $procedureId
    ): JsonResponse {
        $procedure = $procedureService->complete(
            $procedureId,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Procedure completed successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }
}
