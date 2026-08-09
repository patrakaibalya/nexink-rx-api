<?php

namespace App\Services\ClinicalExtraction;

use App\Models\ClinicalExtraction;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClinicalExtractionService
{
    public function create(
        int $visitId,
        array $data
    ): ClinicalExtraction {
        return DB::connection('doctor')->transaction(
            function () use ($visitId, $data) {

                $visit = Visit::query()
                    ->lockForUpdate()
                    ->find($visitId);

                if (!$visit) {
                    throw ValidationException::withMessages([
                        'visit_id' => [
                            'Visit not found.',
                        ],
                    ]);
                }

                if (!in_array($visit->status, [
                    'in_progress',
                    'completed',
                ], true)) {
                    throw ValidationException::withMessages([
                        'visit_id' => [
                            'Clinical extraction cannot be created for this visit.',
                        ],
                    ]);
                }

                $extraction = ClinicalExtraction::create([
                    'clinic_id' => $visit->clinic_id,
                    'patient_id' => $visit->patient_id,
                    'visit_id' => $visit->id,
                    'schema_version' => $data['schema_version'],
                    'status' => 'pending',
                    'confidence' => $data['confidence'] ?? null,
                    'payload' => $data,
                ]);

                return $extraction->load([
                    'clinic',
                    'patient',
                    'visit',
                ]);
            }
        );
    }

    public function show(int $visitId): ?ClinicalExtraction
    {
        return ClinicalExtraction::query()
            ->where('visit_id', $visitId)
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }

    public function confirm(
        int $visitId,
        array $data
    ): ClinicalExtraction {
        return DB::connection('doctor')->transaction(
            function () use ($visitId, $data) {

                $extraction = ClinicalExtraction::query()
                    ->where('visit_id', $visitId)
                    ->where('status', 'pending')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if (!$extraction) {
                    throw ValidationException::withMessages([
                        'visit_id' => [
                            'No pending clinical extraction found.',
                        ],
                    ]);
                }

                $payload = $extraction->payload;

                foreach (
                    [
                        'diagnoses',
                        'medicines',
                        'investigations',
                        'instructions',
                        'follow_up',
                        'other',
                    ] as $section
                ) {
                    if (array_key_exists($section, $data)) {
                        $payload[$section] = $data[$section];
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | Save confirmed diagnosis to Visit
            |--------------------------------------------------------------------------
            */

                $diagnosis = $payload['diagnoses'][0]['name'] ?? null;

                if ($diagnosis) {
                    $extraction->visit->update([
                        'diagnosis' => $diagnosis,
                    ]);
                }

                $extraction->update([
                    'status' => 'confirmed',
                    'payload' => $payload,
                    'confirmed_at' => now(),
                ]);

                return $extraction->fresh([
                    'clinic',
                    'patient',
                    'visit',
                ]);
            }
        );
    }
}
