<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicineOrganization\MedicineOrganizationMedicineRequest;
use App\Models\MedicineOrganization;
use App\Services\Medicine\MedicineLibraryProvisioningService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizationMedicineController extends Controller
{
    public function index(
        Request $request,
        int $organizationId,
        MedicineLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = MedicineOrganization::find($organizationId);

        if (!$organization) {
            return ApiResponse::notFound(
                'Medicine organization not found.'
            );
        }

        $table = $provisioningService
            ->getTableName($organizationId);

        $query = DB::table($table);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'medicine_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'generic_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'manufacturer',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        $medicines = $query
            ->latest('id')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return ApiResponse::success(
            message: 'Organization medicines retrieved successfully.',
            data: [
                'organization' => $organization,
                'medicines' => $medicines,
            ]
        );
    }

    public function store(
        MedicineOrganizationMedicineRequest $request,
        int $organizationId,
        MedicineLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = MedicineOrganization::find($organizationId);

        if (!$organization) {
            return ApiResponse::notFound(
                'Medicine organization not found.'
            );
        }

        $table = $provisioningService
            ->createForOrganization($organizationId);

        $medicineId = DB::table($table)->insertGetId(
            $request->validated()
        );

        $medicine = DB::table($table)
            ->where('id', $medicineId)
            ->first();

        return ApiResponse::created(
            message: 'Organization medicine created successfully.',
            data: [
                'medicine' => $medicine,
            ]
        );
    }

    public function show(
        int $organizationId,
        int $medicineId,
        MedicineLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = MedicineOrganization::find($organizationId);

        if (!$organization) {
            return ApiResponse::notFound(
                'Medicine organization not found.'
            );
        }

        $table = $provisioningService
            ->getTableName($organizationId);

        $medicine = DB::table($table)
            ->where('id', $medicineId)
            ->first();

        if (!$medicine) {
            return ApiResponse::notFound(
                'Organization medicine not found.'
            );
        }

        return ApiResponse::success(
            message: 'Organization medicine retrieved successfully.',
            data: [
                'medicine' => $medicine,
            ]
        );
    }

    public function update(
        MedicineOrganizationMedicineRequest $request,
        int $organizationId,
        int $medicineId,
        MedicineLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = MedicineOrganization::find($organizationId);

        if (!$organization) {
            return ApiResponse::notFound(
                'Medicine organization not found.'
            );
        }

        $table = $provisioningService
            ->getTableName($organizationId);

        $medicine = DB::table($table)
            ->where('id', $medicineId)
            ->first();

        if (!$medicine) {
            return ApiResponse::notFound(
                'Organization medicine not found.'
            );
        }

        DB::table($table)
            ->where('id', $medicineId)
            ->update([
                ...$request->validated(),
                'updated_at' => now(),
            ]);

        $medicine = DB::table($table)
            ->where('id', $medicineId)
            ->first();

        return ApiResponse::success(
            message: 'Organization medicine updated successfully.',
            data: [
                'medicine' => $medicine,
            ]
        );
    }

    public function destroy(
        int $organizationId,
        int $medicineId,
        MedicineLibraryProvisioningService $provisioningService
    ): JsonResponse {
        $organization = MedicineOrganization::find($organizationId);

        if (!$organization) {
            return ApiResponse::notFound(
                'Medicine organization not found.'
            );
        }

        $table = $provisioningService
            ->getTableName($organizationId);

        $deleted = DB::table($table)
            ->where('id', $medicineId)
            ->delete();

        if (!$deleted) {
            return ApiResponse::notFound(
                'Organization medicine not found.'
            );
        }

        return ApiResponse::success(
            message: 'Organization medicine deleted successfully.'
        );
    }
}