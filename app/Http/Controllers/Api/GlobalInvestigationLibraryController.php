<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GlobalInvestigationLibrary\GlobalInvestigationLibraryStoreRequest;
use App\Models\GlobalInvestigationLibrary;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalInvestigationLibraryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GlobalInvestigationLibrary::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(
                'investigation_name',
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

        $investigations = $query
            ->latest('id')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return ApiResponse::success(
            message: 'Global investigations retrieved successfully.',
            data: [
                'investigations' => $investigations,
            ]
        );
    }

    public function store(
        GlobalInvestigationLibraryStoreRequest $request
    ): JsonResponse {
        $investigation = GlobalInvestigationLibrary::create(
            $request->validated()
        );

        return ApiResponse::created(
            message: 'Global investigation created successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function show(int $investigationId): JsonResponse
    {
        $investigation = GlobalInvestigationLibrary::find(
            $investigationId
        );

        if (!$investigation) {
            return ApiResponse::notFound(
                'Global investigation not found.'
            );
        }

        return ApiResponse::success(
            message: 'Global investigation retrieved successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function update(
        GlobalInvestigationLibraryStoreRequest $request,
        int $investigationId
    ): JsonResponse {
        $investigation = GlobalInvestigationLibrary::find(
            $investigationId
        );

        if (!$investigation) {
            return ApiResponse::notFound(
                'Global investigation not found.'
            );
        }

        $investigation->update(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'Global investigation updated successfully.',
            data: [
                'investigation' => $investigation->fresh(),
            ]
        );
    }

    public function destroy(int $investigationId): JsonResponse
    {
        $investigation = GlobalInvestigationLibrary::find(
            $investigationId
        );

        if (!$investigation) {
            return ApiResponse::notFound(
                'Global investigation not found.'
            );
        }

        $investigation->delete();

        return ApiResponse::success(
            message: 'Global investigation deleted successfully.'
        );
    }
}
