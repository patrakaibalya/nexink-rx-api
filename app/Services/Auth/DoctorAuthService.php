<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
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

    public function login(array $data): array
{
    $doctor = DoctorAccount::where('email', $data['email'])->first();

    if (!$doctor || !Hash::check($data['password'], $doctor->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    if (!$doctor->is_active) {
        throw ValidationException::withMessages([
            'email' => ['Your doctor account is inactive.'],
        ]);
    }

    $doctor->update([
        'last_login_at' => now(),
    ]);

    $token = $doctor->createToken('nexink-rx')->plainTextToken;

    return [
        'doctor' => $doctor->fresh(),
        'token' => $token,
    ];
}
}
