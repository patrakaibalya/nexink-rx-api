<?php

namespace App\Services\Auth;

use App\Http\Requests\Auth\MedicineOrganizationRegisterRequest;
use App\Models\MedicineOrganization;
use App\Services\Investigation\InvestigationLibraryProvisioningService;
use App\Services\Medicine\MedicineLibraryProvisioningService;
use App\Services\Order\OrderDataProvisioningService;
use App\Services\Procedure\ProcedureLibraryProvisioningService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MedicineOrganizationAuthService
{

    protected MedicineLibraryProvisioningService $medicineLibraryProvisioningService;

    protected InvestigationLibraryProvisioningService $investigationLibraryProvisioningService;

    protected ProcedureLibraryProvisioningService $procedureLibraryProvisioningService;

    protected OrderDataProvisioningService $orderDataProvisioningService;

    public function __construct(
        MedicineLibraryProvisioningService $medicineLibraryProvisioningService,
        InvestigationLibraryProvisioningService $investigationLibraryProvisioningService,
        ProcedureLibraryProvisioningService $procedureLibraryProvisioningService,
        OrderDataProvisioningService $orderDataProvisioningService
    ) {
        $this->medicineLibraryProvisioningService =
            $medicineLibraryProvisioningService;

        $this->investigationLibraryProvisioningService =
            $investigationLibraryProvisioningService;

        $this->procedureLibraryProvisioningService =
            $procedureLibraryProvisioningService;

        $this->orderDataProvisioningService =
            $orderDataProvisioningService;
    }



    public function login(array $data): array
    {
        $organization = MedicineOrganization::query()
            ->where('email', $data['email'])
            ->first();

        if (
            !$organization ||
            !Hash::check(
                $data['password'],
                $organization->password
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }

        if (!$organization->is_active) {
            throw ValidationException::withMessages([
                'email' => [
                    'Your medicine organization account is inactive.',
                ],
            ]);
        }

        $organization->update([
            'last_login_at' => now(),
        ]);

        $token = $organization
            ->createToken('nexink-medicine-organization')
            ->plainTextToken;

        return [
            'organization' => $organization->fresh(),
            'token' => $token,
        ];
    }

    public function register(
        MedicineOrganizationRegisterRequest $request
    ): MedicineOrganization {
        $organization = MedicineOrganization::create([
            'organization_name' => $request->validated(
                'organization_name'
            ),

            'email' => $request->validated('email'),

            'mobile' => $request->validated('mobile'),

            'contact_person' => $request->validated(
                'contact_person'
            ),

            'address' => $request->validated('address'),

            'password' => Hash::make(
                $request->validated('password')
            ),
        ]);

        try {
            $this->medicineLibraryProvisioningService
                ->createForOrganization($organization->id);

            $this->investigationLibraryProvisioningService
                ->createForOrganization($organization->id);

            $this->procedureLibraryProvisioningService
                ->createForOrganization($organization->id);

            $this->orderDataProvisioningService
                ->createForOrganization($organization->id);
        } catch (\Throwable $exception) {
            $organization->delete();

            throw $exception;
        }

        return $organization->fresh();
    }
}
