<?php

namespace App\Services\Auth;

use App\Models\MedicineOrganization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MedicineOrganizationAuthService
{
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
}
