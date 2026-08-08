<?php

namespace App\Services\Auth;

use App\Models\DoctorAccount;
use App\Services\Database\DoctorDatabaseProvisioningService;

namespace App\Services\Auth;

use App\Models\DoctorAccount;
use App\Services\Database\DoctorDatabaseProvisioningService;

class DoctorAuthService
{
    public function __construct(
        private readonly DoctorDatabaseProvisioningService $databaseProvisioningService
    ) {
    }

    public function register(array $data): DoctorAccount
    {
        $doctor = DoctorAccount::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'specialization' => $data['specialization'] ?? null,
            'medical_license_number' => $data['medical_license_number'] ?? null,
            'password' => $data['password'],
        ]);

        $this->databaseProvisioningService->provision($doctor);

        return $doctor->fresh();
    }
}
