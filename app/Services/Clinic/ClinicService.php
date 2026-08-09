<?php

namespace App\Services\Clinic;

use App\Models\Clinic;
use App\Models\ClinicWorkingHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClinicService
{
    public function updateWorkingHours(
        int $clinicId,
        array $workingHours
    ): Clinic {
        return DB::connection('doctor')->transaction(
            function () use ($clinicId, $workingHours) {

                $clinic = Clinic::find($clinicId);

                if (!$clinic) {
                    throw ValidationException::withMessages([
                        'clinic_id' => [
                            'Clinic not found.',
                        ],
                    ]);
                }

                if (!$clinic->is_active) {
                    throw ValidationException::withMessages([
                        'clinic_id' => [
                            'Clinic is inactive.',
                        ],
                    ]);
                }

                foreach ($workingHours as $hours) {
                    ClinicWorkingHour::updateOrCreate(
                        [
                            'clinic_id' => $clinic->id,
                            'day_of_week' => $hours['day_of_week'],
                        ],
                        [
                            'opening_time' => $hours['opening_time'] ?? null,
                            'closing_time' => $hours['closing_time'] ?? null,
                            'is_closed' => $hours['is_closed'],
                        ]
                    );
                }

                return $clinic->fresh([
                    'workingHours',
                ]);
            }
        );
    }

    public function getWorkingHours(
        int $clinicId
    ): Clinic {
        $clinic = Clinic::query()
            ->with([
                'workingHours',
            ])
            ->find($clinicId);

        if (!$clinic) {
            throw ValidationException::withMessages([
                'clinic_id' => [
                    'Clinic not found.',
                ],
            ]);
        }

        return $clinic;
    }
}
