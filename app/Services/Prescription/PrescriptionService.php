<?php

namespace App\Services\Prescription;

use App\Models\Prescription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrescriptionService
{
    public function show(int $prescriptionId): Prescription
    {
        $prescription = Prescription::query()
            ->with([
                'clinic',
                'patient',
                'visit',
                'items',
            ])
            ->find($prescriptionId);

        if (!$prescription) {
            throw ValidationException::withMessages([
                'prescription_id' => [
                    'Prescription not found.',
                ],
            ]);
        }

        return $prescription;
    }

    public function update(
        int $prescriptionId,
        array $data
    ): Prescription {
        return DB::connection('doctor')->transaction(
            function () use ($prescriptionId, $data) {

                $prescription = Prescription::query()
                    ->lockForUpdate()
                    ->find($prescriptionId);

                if (!$prescription) {
                    throw ValidationException::withMessages([
                        'prescription_id' => [
                            'Prescription not found.',
                        ],
                    ]);
                }

                if ($prescription->status === 'cancelled') {
                    throw ValidationException::withMessages([
                        'prescription' => [
                            'A cancelled prescription cannot be updated.',
                        ],
                    ]);
                }

                if ($prescription->status === 'final') {
                    throw ValidationException::withMessages([
                        'prescription' => [
                            'A finalized prescription cannot be updated.',
                        ],
                    ]);
                }

                $prescription->update([
                    'notes' => array_key_exists('notes', $data)
                        ? $data['notes']
                        : $prescription->notes,
                    'finalized_data' => array_key_exists('finalized_data', $data)
                        ? $data['finalized_data']
                        : $prescription->finalized_data,
                ]);

                if (array_key_exists('items', $data)) {

                    $prescription->items()->delete();

                    foreach ($data['items'] as $index => $item) {
                        $prescription->items()->create([
                            'medicine_name' => $item['medicine_name'],
                            'dosage' => $item['dosage'] ?? null,
                            'frequency' => $item['frequency'] ?? null,
                            'duration' => $item['duration'] ?? null,
                            'route' => $item['route'] ?? null,
                            'instructions' => $item['instructions'] ?? null,
                            'sort_order' => $index,
                        ]);
                    }
                }

                return $prescription->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                ]);
            }
        );
    }

    public function delete(int $prescriptionId): void
    {
        $prescription = Prescription::query()
            ->lockForUpdate()
            ->find($prescriptionId);

        if (!$prescription) {
            throw ValidationException::withMessages([
                'prescription_id' => [
                    'Prescription not found.',
                ],
            ]);
        }

        if ($prescription->status === 'final') {
            throw ValidationException::withMessages([
                'prescription' => [
                    'A finalized prescription cannot be deleted.',
                ],
            ]);
        }

        $prescription->delete();
    }

    public function index(array $filters)
    {
        $query = Prescription::query()
            ->with([
                'clinic',
                'patient',
                'visit',
                'items',
            ])
            ->latest('id');

        if (!empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (!empty($filters['visit_id'])) {
            $query->where('visit_id', $filters['visit_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate(
                'prescription_date',
                $filters['date']
            );
        }

        return $query->paginate(
            $filters['per_page'] ?? 20
        );
    }

    public function finalize(int $prescriptionId): Prescription
    {
        return DB::connection('doctor')->transaction(
            function () use ($prescriptionId) {

                $prescription = Prescription::query()
                    ->lockForUpdate()
                    ->with('items')
                    ->find($prescriptionId);

                if (!$prescription) {
                    throw ValidationException::withMessages([
                        'prescription_id' => [
                            'Prescription not found.',
                        ],
                    ]);
                }

                if ($prescription->status === 'final') {
                    throw ValidationException::withMessages([
                        'prescription' => [
                            'Prescription is already finalized.',
                        ],
                    ]);
                }

                if ($prescription->status === 'cancelled') {
                    throw ValidationException::withMessages([
                        'prescription' => [
                            'A cancelled prescription cannot be finalized.',
                        ],
                    ]);
                }

                if ($prescription->items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'A prescription must contain at least one medicine before finalization.',
                        ],
                    ]);
                }

                $prescription->update([
                    'status' => 'final',
                ]);

                return $prescription->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                ]);
            }
        );
    }

    public function createDraftForVisit(int $visitId): Prescription
    {
        return DB::connection('doctor')->transaction(
            function () use ($visitId) {

                $visit = \App\Models\Visit::query()
                    ->lockForUpdate()
                    ->find($visitId);

                if (!$visit) {
                    throw ValidationException::withMessages([
                        'visit_id' => [
                            'Visit not found.',
                        ],
                    ]);
                }

                if ($visit->status !== 'in_progress') {
                    throw ValidationException::withMessages([
                        'visit_id' => [
                            'A draft prescription can only be created for an active visit.',
                        ],
                    ]);
                }

                $existingPrescription = Prescription::query()
                    ->where('visit_id', $visit->id)
                    ->where('status', 'draft')
                    ->latest('id')
                    ->first();

                if ($existingPrescription) {
                    return $existingPrescription->fresh([
                        'clinic',
                        'patient',
                        'visit',
                        'items',
                    ]);
                }

                $prescription = Prescription::create([
                    'clinic_id' => $visit->clinic_id,
                    'patient_id' => $visit->patient_id,
                    'visit_id' => $visit->id,
                    'prescription_date' => now(
                        $visit->clinic->timezone
                    )->toDateString(),
                    'notes' => null,
                    'status' => 'draft',
                ]);

                return $prescription->fresh([
                    'clinic',
                    'patient',
                    'visit',
                    'items',
                ]);
            }
        );
    }
}
