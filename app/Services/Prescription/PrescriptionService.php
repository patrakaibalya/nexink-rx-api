<?php

namespace App\Services\Prescription;

use App\Models\DoctorHandwritingSample;
use App\Models\Prescription;
use App\Services\Patient\PatientVitalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;


class PrescriptionService
{
    public function __construct(
        protected PatientVitalService $vitalService
    ) {
    }

    /**
     * Also loads the visit's investigations and procedures with their
     * uploaded report documents, so the prescription preview can show the
     * lab / discharge reports for the same visit, and the vitals recorded
     * for that visit.
     */
    public function show(int $prescriptionId): Prescription
    {
        $prescription = Prescription::query()
            ->with([
                'clinic',
                'patient',
                'visit',
                'visit.investigations.items',
                'visit.investigations.documents',
                'visit.procedures.items',
                'visit.procedures.documents',
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

        // Vitals as recorded for this visit, so old prescriptions never show later readings.
        $prescription->visit?->setRelation('clinic', $prescription->clinic);
        $prescription->setAttribute('vitals', $this->vitalService->forVisit($prescription->visit));

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

    /**
     * Permanent delete - same clean-up as VisitService::resetDirect(): the handwriting
     * samples and their ink files, the items, then the prescription row itself.
     * The visit is kept (it's the patient's visit history), only its prescription goes.
     * Finalized prescriptions are medical records and can't be deleted.
     *
     * Note: handwriting-memory vectors already sent to Qdrant are not removed here
     * (resetDirect doesn't remove them either).
     */
    public function delete(int $prescriptionId): void
    {
        // lockForUpdate only holds inside a transaction.
        DB::connection('doctor')->transaction(
            function () use ($prescriptionId) {
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

                DoctorHandwritingSample::query()
                    ->where('prescription_id', $prescription->id)
                    ->get()
                    ->each(function (DoctorHandwritingSample $sample) {
                        if (
                            $sample->ink_file_path
                            && Storage::disk('local')->exists($sample->ink_file_path)
                        ) {
                            Storage::disk('local')->delete($sample->ink_file_path);
                        }

                        $sample->delete();
                    });

                // Items use SoftDeletes - remove the rows for real, not just mark them.
                $prescription->items()->forceDelete();
                $prescription->forceDelete();
            }
        );
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

        if (!empty($filters['clinic_id'])) {
            $query->where('clinic_id', $filters['clinic_id']);
        }

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

        if (!empty($filters['has_items'])) {
            $query->has('items');
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
