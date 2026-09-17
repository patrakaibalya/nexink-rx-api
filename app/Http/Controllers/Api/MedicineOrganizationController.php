<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MedicineOrganization\MedicineOrganizationStoreRequest;
use App\Models\MedicineOrganization;
use App\Services\Investigation\InvestigationLibraryProvisioningService;
use App\Services\Medicine\MedicineLibraryProvisioningService;
use App\Services\Procedure\ProcedureLibraryProvisioningService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class MedicineOrganizationController extends Controller
{
    public function index(): JsonResponse
    {
        $organizations = MedicineOrganization::query()
            ->latest('id')
            ->get();

        return ApiResponse::success(
            message: 'Medicine organizations retrieved successfully.',
            data: [
                'organizations' => $organizations,
            ]
        );
    }

    public function store(
        MedicineOrganizationStoreRequest $request,
        MedicineLibraryProvisioningService $medicineLibraryProvisioningService,
        InvestigationLibraryProvisioningService $investigationLibraryProvisioningService,
        ProcedureLibraryProvisioningService $procedureLibraryProvisioningService
    ): JsonResponse {
        $organization = MedicineOrganization::create([
            ...$request->validated(),
            'password' => Hash::make(
                $request->validated('password')
            ),
        ]);

        $medicineLibraryProvisioningService
            ->createForOrganization($organization->id);

        $investigationLibraryProvisioningService
            ->createForOrganization($organization->id);

        $procedureLibraryProvisioningService
            ->createForOrganization($organization->id);

        return ApiResponse::created(
            message: 'Medicine organization created successfully.',
            data: [
                'organization' => $organization,
            ]
        );
    }

    public function show(int $organizationId): JsonResponse
    {
        $organization = MedicineOrganization::find(
            $organizationId
        );

        if (!$organization) {
            return ApiResponse::notFound(
                'Medicine organization not found.'
            );
        }

        return ApiResponse::success(
            message: 'Medicine organization retrieved successfully.',
            data: [
                'organization' => $organization,
            ]
        );
    }

    public function update(
        MedicineOrganizationStoreRequest $request,
        int $organizationId
    ): JsonResponse {
        $organization = MedicineOrganization::find(
            $organizationId
        );

        if (!$organization) {
            return ApiResponse::notFound(
                'Medicine organization not found.'
            );
        }

        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make(
                $data['password']
            );
        }

        $organization->update($data);

        return ApiResponse::success(
            message: 'Medicine organization updated successfully.',
            data: [
                'organization' => $organization->fresh(),
            ]
        );
    }

    public function destroy(int $organizationId): JsonResponse
    {
        $organization = MedicineOrganization::find(
            $organizationId
        );

        if (!$organization) {
            return ApiResponse::notFound(
                'Medicine organization not found.'
            );
        }

        $organization->delete();

        return ApiResponse::success(
            message: 'Medicine organization deleted successfully.'
        );
    }
}
