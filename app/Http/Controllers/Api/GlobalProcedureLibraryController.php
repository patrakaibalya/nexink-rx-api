<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalProcedureLibrary\GlobalProcedureLibraryStoreRequest;
use App\Models\GlobalProcedureLibrary;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalProcedureLibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GlobalProcedureLibrary::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(
                'procedure_name',
                'like',
                "%{$search}%"
            );
        }

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        $procedures = $query
            ->latest('id')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return ApiResponse::success(
            message: 'Global procedures retrieved successfully.',
            data: [
                'procedures' => $procedures,
            ]
        );
    }

    public function store(
        GlobalProcedureLibraryStoreRequest $request
    ): JsonResponse {
        $procedure = GlobalProcedureLibrary::create(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Global procedure created successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function show(int $procedureId): JsonResponse
    {
        $procedure = GlobalProcedureLibrary::find(
            $procedureId
        );

        if (!$procedure) {
            return ApiResponse::notFound(
                'Global procedure not found.'
            );
        }

        return ApiResponse::success(
            message: 'Global procedure retrieved successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function update(
        GlobalProcedureLibraryStoreRequest $request,
        int $procedureId
    ): JsonResponse {
        $procedure = GlobalProcedureLibrary::find(
            $procedureId
        );

        if (!$procedure) {
            return ApiResponse::notFound(
                'Global procedure not found.'
            );
        }

        $procedure->update(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Global procedure updated successfully.',
            data: [
                'procedure' => $procedure->fresh(),
            ]
        );
    }

    public function destroy(int $procedureId): JsonResponse
    {
        $procedure = GlobalProcedureLibrary::find(
            $procedureId
        );

        if (!$procedure) {
            return ApiResponse::notFound(
                'Global procedure not found.'
            );
        }

        $procedure->delete();

        return ApiResponse::success(
            message: 'Global procedure deleted successfully.'
        );
    }
}
