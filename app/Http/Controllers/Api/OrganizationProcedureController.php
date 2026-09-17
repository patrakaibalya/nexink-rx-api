<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicineOrganization\MedicineOrganizationProcedureRequest;
use App\Services\Procedure\ProcedureLibraryProvisioningService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationProcedureController extends Controller
{
    public function index(
        Request $request,
        ProcedureLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->getTableName($organization->id);

        $query = DB::table($table);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where('procedure_name', 'like', "%{$search}%");
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
            message: 'Organization procedures retrieved successfully.',
            data: [
                'organization' => $organization,
                'procedures' => $procedures,
            ]
        );
    }

    public function store(
        MedicineOrganizationProcedureRequest $request,
        ProcedureLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->createForOrganization($organization->id);

        $procedureId = DB::table($table)->insertGetId(
            $request->validated()
        );

        $procedure = DB::table($table)
            ->where('id', $procedureId)
            ->first();

        return ApiResponse::created(
            message: 'Organization procedure created successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function show(
        Request $request,
        int $procedureId,
        ProcedureLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->getTableName($organization->id);

        $procedure = DB::table($table)
            ->where('id', $procedureId)
            ->first();

        if (!$procedure) {
            return ApiResponse::notFound(
                'Organization procedure not found.'
            );
        }

        return ApiResponse::success(
            message: 'Organization procedure retrieved successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function update(
        MedicineOrganizationProcedureRequest $request,
        int $procedureId,
        ProcedureLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->getTableName($organization->id);

        $procedure = DB::table($table)
            ->where('id', $procedureId)
            ->first();

        if (!$procedure) {
            return ApiResponse::notFound(
                'Organization procedure not found.'
            );
        }

        DB::table($table)
            ->where('id', $procedureId)
            ->update([
                ...$request->validated(),
                'updated_at' => now(),
            ]);

        $procedure = DB::table($table)
            ->where('id', $procedureId)
            ->first();

        return ApiResponse::success(
            message: 'Organization procedure updated successfully.',
            data: [
                'procedure' => $procedure,
            ]
        );
    }

    public function destroy(
        Request $request,
        int $procedureId,
        ProcedureLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = $request->user();

        $table = $provisioningService
            ->getTableName($organization->id);

        $deleted = DB::table($table)
            ->where('id', $procedureId)
            ->delete();

        if (!$deleted) {
            return ApiResponse::notFound(
                'Organization procedure not found.'
            );
        }

        return ApiResponse::success(
            message: 'Organization procedure deleted successfully.'
        );
    }
}
