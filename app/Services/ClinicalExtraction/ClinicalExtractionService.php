<?php

namespace App\Services\ClinicalExtraction;

use App\Models\ClinicalExtraction;
use App\Models\Investigation;
use App\Models\Prescription;
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
                    'status' => 'processing',
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

                /*
            |--------------------------------------------------------------------------
            | Save Instructions / Follow-up / Other to Visit
            |--------------------------------------------------------------------------
            */

                $additionalNotes = [];

                foreach ($payload['instructions'] ?? [] as $instruction) {
                    if (!empty($instruction['text'])) {
                        $additionalNotes[] = 'Instruction: ' . $instruction['text'];
                    }
                }

                if (!empty($payload['follow_up'])) {
                    $followUp = $payload['follow_up'];

                    $text = 'Follow-up: ' .
                        ($followUp['value'] ?? '') . ' ' .
                        ($followUp['unit'] ?? '');

                    if (!empty($followUp['instructions'])) {
                        $text .= ' - ' . $followUp['instructions'];
                    }

                    $additionalNotes[] = $text;
                }

                foreach ($payload['other'] ?? [] as $other) {
                    if (!empty($other['text'])) {
                        $additionalNotes[] = 'Other: ' . $other['text'];
                    }
                }

                if (!empty($additionalNotes)) {
                    $visit = $extraction->visit;

                    $clinicalNotes = $visit->clinical_notes;

                    $visit->update([
                        'clinical_notes' => trim(
                            ($clinicalNotes ? $clinicalNotes . "\n\n" : '') .
                                implode("\n", $additionalNotes)
                        ),
                    ]);
                }

                /*
            |--------------------------------------------------------------------------
            | Create Prescription
            |--------------------------------------------------------------------------
            */

                $medicines = $payload['medicines'] ?? [];

                if (!empty($medicines)) {

                    $prescription = Prescription::create([
                        'clinic_id' => $extraction->clinic_id,
                        'patient_id' => $extraction->patient_id,
                        'visit_id' => $extraction->visit_id,
                        'prescription_date' => now(
                            $extraction->clinic->timezone
                        )->toDateString(),
                        'notes' => null,
                        'status' => 'draft',
                    ]);

                    foreach ($medicines as $index => $medicine) {
                        $prescription->items()->create([
                            'medicine_name' => $medicine['name'],
                            'dosage' => $medicine['dosage'] ?? null,
                            'frequency' => $medicine['frequency'] ?? null,
                            'duration' => $medicine['duration'] ?? null,
                            'route' => $medicine['route'] ?? null,
                            'instructions' => $medicine['instructions'] ?? null,
                            'sort_order' => $index,
                        ]);
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | Create Investigation
            |--------------------------------------------------------------------------
            */

                $investigations = $payload['investigations'] ?? [];

                if (!empty($investigations)) {

                    $investigation = Investigation::create([
                        'clinic_id' => $extraction->clinic_id,
                        'patient_id' => $extraction->patient_id,
                        'visit_id' => $extraction->visit_id,
                        'investigation_date' => now(
                            $extraction->clinic->timezone
                        )->toDateString(),
                        'status' => 'ordered',
                        'notes' => null,
                    ]);

                    foreach ($investigations as $index => $item) {
                        $investigation->items()->create([
                            'test_name' => $item['name'],
                            'test_type' => $item['type'],
                            'instructions' => $item['instructions'] ?? null,
                            'sort_order' => $index,
                        ]);
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | Confirm extraction
            |--------------------------------------------------------------------------
            */

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

    public function reject(
        int $visitId,
        string $reason
    ): ClinicalExtraction {
        return DB::connection('doctor')->transaction(
            function () use ($visitId, $reason) {

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

                $payload['rejection_reason'] = $reason;

                $extraction->update([
                    'status' => 'rejected',
                    'payload' => $payload,
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
