<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicineOrganization\MedicineOrganizationInvestigationRequest;
use App\Services\Investigation\InvestigationLibraryProvisioningService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationInvestigationController extends Controller
{
    public function index(
        Request $request,
        InvestigationLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->getTableName($organization->id);

        $query = DB::table($table);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where('investigation_name', 'like', "%{$search}%");
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
            message: 'Organization investigations retrieved successfully.',
            data: [
                'organization' => $organization,
                'investigations' => $investigations,
            ]
        );
    }

    public function store(
        MedicineOrganizationInvestigationRequest $request,
        InvestigationLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->createForOrganization($organization->id);

        $investigationId = DB::table($table)->insertGetId(
            $request->validated()
        );

        $investigation = DB::table($table)
            ->where('id', $investigationId)
            ->first();

        return ApiResponse::created(
            message: 'Organization investigation created successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function show(
        Request $request,
        int $investigationId,
        InvestigationLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->getTableName($organization->id);

        $investigation = DB::table($table)
            ->where('id', $investigationId)
            ->first();

        if (!$investigation) {
            return ApiResponse::notFound(
                'Organization investigation not found.'
            );
        }

        return ApiResponse::success(
            message: 'Organization investigation retrieved successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function update(
        MedicineOrganizationInvestigationRequest $request,
        int $investigationId,
        InvestigationLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->getTableName($organization->id);

        $investigation = DB::table($table)
            ->where('id', $investigationId)
            ->first();

        if (!$investigation) {
            return ApiResponse::notFound(
                'Organization investigation not found.'
            );
        }

        DB::table($table)
            ->where('id', $investigationId)
            ->update([
                ...$request->validated(),
                'updated_at' => now(),
            ]);

        $investigation = DB::table($table)
            ->where('id', $investigationId)
            ->first();

        return ApiResponse::success(
            message: 'Organization investigation updated successfully.',
            data: [
                'investigation' => $investigation,
            ]
        );
    }

    public function destroy(
        Request $request,
        int $investigationId,
        InvestigationLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->getTableName($organization->id);

        $deleted = DB::table($table)
            ->where('id', $investigationId)
            ->delete();

        if (!$deleted) {
            return ApiResponse::notFound(
                'Organization investigation not found.'
            );
        }

        return ApiResponse::success(
            message: 'Organization investigation deleted successfully.'
        );
    }
}
