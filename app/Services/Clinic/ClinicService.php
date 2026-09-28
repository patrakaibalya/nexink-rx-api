<?php

namespace App\Services\Clinic;

use App\Models\Clinic;
use App\Models\ClinicWorkingHour;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClinicService
{
    private const DEFAULT_OPENING_TIME = '09:00';

    private const DEFAULT_CLOSING_TIME = '18:00';

    // ISO day of week: 1 = Monday ... 7 = Sunday
    private const DEFAULT_CLOSED_DAYS = [7];

    public function createClinic(array $data): Clinic
    {
        return DB::connection('doctor')->transaction(
            function () use ($data) {
                $clinic = Clinic::create($data);

                $this->createDefaultWorkingHours($clinic);

                return $clinic->fresh([
                    'workingHours',
                ]);
            }
        );
    }

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

    private function createDefaultWorkingHours(Clinic $clinic): void
    {
        $now = now();

        $rows = collect(range(1, 7))
            ->map(function (int $day) use ($clinic, $now) {
                $isClosed = in_array(
                    $day,
                    self::DEFAULT_CLOSED_DAYS,
                    true
                );

                return [
                    'clinic_id' => $clinic->id,
                    'day_of_week' => $day,
                    'opening_time' => $isClosed ? null : self::DEFAULT_OPENING_TIME,
                    'closing_time' => $isClosed ? null : self::DEFAULT_CLOSING_TIME,
                    'is_closed' => $isClosed,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        ClinicWorkingHour::insert($rows);
    }
}
