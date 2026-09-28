<?php

namespace App\Services\Visit;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicalExtraction;
use App\Models\DoctorHandwritingSample;
use App\Models\Investigation;
use App\Models\InvestigationDocument;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Procedure;
use App\Models\ProcedureDocument;
use App\Models\Queue;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VisitService
{
    public function start(array $data): Visit
    {
        return DB::connection('doctor')->transaction(
            function () use ($data) {

                $clinic = Clinic::find($data['clinic_id']);

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

                $patient = Patient::find($data['patient_id']);

                if (!$patient) {
                    throw ValidationException::withMessages([
                        'patient_id' => [
                            'Patient not found.',
                        ],
                    ]);
                }

                $appointment = null;
                $queue = null;

                if (!empty($data['appointment_id'])) {
                    $appointment = Appointment::query()
                        ->lockForUpdate()
                        ->find($data['appointment_id']);

                    if (!$appointment) {
                        throw ValidationException::withMessages([
                            'appointment_id' => [
                                'Appointment not found.',
                            ],
                        ]);
                    }

                    if (
                        $appointment->clinic_id !==
                        $clinic->id
                    ) {
                        throw ValidationException::withMessages([
                            'appointment_id' => [
                                'Appointment does not belong to this clinic.',
                            ],
                        ]);
                    }

                    if (
                        $appointment->patient_id !==
                        $patient->id
                    ) {
                        throw ValidationException::withMessages([
                            'appointment_id' => [
                                'Appointment does not belong to this patient.',
                            ],
                        ]);
                    }

                    if ($appointment->status !== 'arrived') {
                        throw ValidationException::withMessages([
                            'appointment_id' => [
                                'Appointment must be arrived before consultation.',
                            ],
                        ]);
                    }
                }

                if (!empty($data['queue_id'])) {
                    $queue = Queue::query()
                        ->lockForUpdate()
                        ->find($data['queue_id']);

                    if (!$queue) {
                        throw ValidationException::withMessages([
                            'queue_id' => [
                                'Queue not found.',
                            ],
                        ]);
                    }

                    if (
                        $queue->clinic_id !==
                        $clinic->id
                    ) {
                        throw ValidationException::withMessages([
                            'queue_id' => [
                                'Queue does not belong to this clinic.',
                            ],
                        ]);
                    }

                    if (
                        $queue->patient_id !==
                        $patient->id
                    ) {
                        throw ValidationException::withMessages([
                            'queue_id' => [
                                'Queue does not belong to this patient.',
                            ],
                        ]);
                    }

                    if (!in_array($queue->status, [
                        'called',
                        'consulting',
                    ], true)) {
                        throw ValidationException::withMessages([
                            'queue_id' => [
                                'Patient is not ready for consultation.',
                            ],
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Prevent duplicate active visit
                |--------------------------------------------------------------------------
                */

                $existingVisit = Visit::query()
                    ->where('clinic_id', $clinic->id)
                    ->where('patient_id', $patient->id)
                    ->where('status', 'in_progress')
                    ->first();

                if ($existingVisit) {
                    return $existingVisit->load([
                        'clinic',
                        'patient',
                        'appointment',
                        'queue',
                    ]);
                }

                $visit = Visit::create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'appointment_id' => $appointment?->id,
                    'queue_id' => $queue?->id,
                    'visit_date' => now(
                        $clinic->timezone
                    )->toDateString(),
                    'started_at' => now(),
                    'status' => 'in_progress',
                    'chief_complaint' =>
                    $data['chief_complaint'] ?? null,
                ]);

                if ($queue) {
                    $queue->update([
                        'status' => 'consulting',
                    ]);
                }

                return $visit->load([
                    'clinic',
                    'patient',
                    'appointment',
                    'queue',
                ]);
            }
        );
    }

    public function index(array $filters)
    {
        $query = Visit::query()
            ->with([
                'clinic',
                'patient',
                'appointment',
                'queue',
                'prescription',
                'clinicalExtractions',
                'investigations',
                'procedures',
            ])
            ->whereDate(
                'visit_date',
                $filters['date'] ?? now()->toDateString()
            )
            ->latest('started_at');

        if (!empty($filters['clinic_id'])) {
            $query->where('clinic_id', $filters['clinic_id']);
        }

        if (!empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(
            $filters['per_page'] ?? 20
        );
    }

    public function show(int $visitId): Visit
    {
        $visit = Visit::query()
            ->with([
                'clinic',
                'patient',
                'appointment',
                'queue',
            ])
            ->find($visitId);

        if (!$visit) {
            throw ValidationException::withMessages([
                'visit_id' => [
                    'Visit not found.',
                ],
            ]);
        }

        return $visit;
    }

    public function update(
        int $visitId,
        array $data
    ): Visit {
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

                if ($visit->status !== 'in_progress') {
                    throw ValidationException::withMessages([
                        'visit' => [
                            'Only an in-progress visit can be updated.',
                        ],
                    ]);
                }

                $visit->update([
                    'chief_complaint' => $data['chief_complaint']
                        ?? $visit->chief_complaint,
                    'clinical_notes' => $data['clinical_notes']
                        ?? $visit->clinical_notes,
                    'diagnosis' => $data['diagnosis']
                        ?? $visit->diagnosis,
                ]);

                return $visit->fresh([
                    'clinic',
                    'patient',
                    'appointment',
                    'queue',
                ]);
            }
        );
    }

    public function complete(
        int $visitId,
        array $data
    ): Visit {
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

                if ($visit->status !== 'in_progress') {
                    throw ValidationException::withMessages([
                        'visit' => [
                            'Only an in-progress visit can be completed.',
                        ],
                    ]);
                }

                if (array_key_exists('clinical_notes', $data)) {
                    $visit->clinical_notes = $data['clinical_notes'];
                }

                $visit->status = 'completed';
                $visit->completed_at = now();

                $visit->save();

                /*
            |--------------------------------------------------------------------------
            | Complete linked appointment
            |--------------------------------------------------------------------------
            */

                if ($visit->appointment_id) {
                    $appointment = Appointment::query()
                        ->lockForUpdate()
                        ->find($visit->appointment_id);

                    if (
                        $appointment
                        && $appointment->status === 'arrived'
                    ) {
                        $appointment->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | Complete linked queue
            |--------------------------------------------------------------------------
            */

                if ($visit->queue_id) {
                    $queue = Queue::query()
                        ->lockForUpdate()
                        ->find($visit->queue_id);

                    if ($queue) {
                        $queue->update([
                            'status' => 'completed',
                        ]);
                    }
                }

                return $visit->fresh([
                    'clinic',
                    'patient',
                    'appointment',
                    'queue',
                ]);
            }
        );
    }

    /**
     * Emergency shortcut: complete the visit and mark its draft
     * prescription "unverified" without any AI reformat/finalize step,
     * so the doctor can move on immediately and correct it later.
     */
    public function emergencyComplete(
        int $visitId,
        array $data
    ): Visit {
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

                if ($visit->status !== 'in_progress') {
                    throw ValidationException::withMessages([
                        'visit' => [
                            'Only an in-progress visit can be completed.',
                        ],
                    ]);
                }

                $prescription = Prescription::query()
                    ->lockForUpdate()
                    ->where('visit_id', $visit->id)
                    ->where('status', 'draft')
                    ->latest('id')
                    ->first();

                if (!$prescription) {
                    throw ValidationException::withMessages([
                        'visit' => [
                            'This visit has no draft prescription to finish.',
                        ],
                    ]);
                }

                if (array_key_exists('clinical_notes', $data)) {
                    $visit->clinical_notes = $data['clinical_notes'];
                }

                $visit->status = 'completed';
                $visit->completed_at = now();

                $visit->save();

                $prescription->update([
                    'status' => 'unverified',
                ]);

                /*
            |--------------------------------------------------------------------------
            | Complete linked appointment
            |--------------------------------------------------------------------------
            */

                if ($visit->appointment_id) {
                    $appointment = Appointment::query()
                        ->lockForUpdate()
                        ->find($visit->appointment_id);

                    if (
                        $appointment
                        && $appointment->status === 'arrived'
                    ) {
                        $appointment->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | Complete linked queue
            |--------------------------------------------------------------------------
            */

                if ($visit->queue_id) {
                    $queue = Queue::query()
                        ->lockForUpdate()
                        ->find($visit->queue_id);

                    if ($queue) {
                        $queue->update([
                            'status' => 'completed',
                        ]);
                    }
                }

                return $visit->fresh([
                    'clinic',
                    'patient',
                    'appointment',
                    'queue',
                ]);
            }
        );
    }

    /**
     * Resume review of an emergency-finished (unverified) prescription:
     * flip the visit back to in_progress and the prescription back to
     * draft so the normal re-verify/AI-reformat/clinical-extraction/
     * complete pipeline (unchanged) can run again on it. The already
     * completed appointment/queue are left untouched — this is the
     * doctor correcting a document, not a new consultation.
     */
    public function reopen(int $visitId): Visit
    {
        return DB::connection('doctor')->transaction(
            function () use ($visitId) {

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

                if ($visit->status !== 'completed') {
                    throw ValidationException::withMessages([
                        'visit' => [
                            'Only a completed visit can be reopened for review.',
                        ],
                    ]);
                }

                $prescription = Prescription::query()
                    ->lockForUpdate()
                    ->where('visit_id', $visit->id)
                    ->where('status', 'unverified')
                    ->latest('id')
                    ->first();

                if (!$prescription) {
                    throw ValidationException::withMessages([
                        'visit' => [
                            'This visit has no unverified prescription to reopen.',
                        ],
                    ]);
                }

                $existingVisit = Visit::query()
                    ->where('clinic_id', $visit->clinic_id)
                    ->where('patient_id', $visit->patient_id)
                    ->where('status', 'in_progress')
                    ->first();

                if ($existingVisit) {
                    throw ValidationException::withMessages([
                        'visit' => [
                            'This patient already has another in-progress visit.',
                        ],
                    ]);
                }

                $visit->status = 'in_progress';
                $visit->completed_at = null;

                $visit->save();

                $prescription->update([
                    'status' => 'draft',
                ]);

                return $visit->fresh([
                    'clinic',
                    'patient',
                    'appointment',
                    'queue',
                    'prescription',
                ]);
            }
        );
    }

    /**
     * Called when the app (re)launches to find a visit/prescription the
     * doctor was left in the middle of — e.g. the app was killed while
     * on the prescription-writing screen before Finish/Skip was tapped.
     *
     * - An in-progress visit with a draft prescription is auto-finished
     *   the same way the "Skip" button (emergencyComplete) does, and
     *   handed back for the Review stage.
     * - An in-progress visit with no prescription yet is left alone and
     *   handed back for the writing stage.
     * - Otherwise, the most recent already-unverified prescription (e.g.
     *   from an earlier Skip that was never reviewed) is handed back
     *   for the Review stage.
     */
    public function resumePending(): array
    {
        $inProgressVisit = Visit::query()
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first();

        if ($inProgressVisit) {
            $draftPrescription = Prescription::query()
                ->where('visit_id', $inProgressVisit->id)
                ->where('status', 'draft')
                ->latest('id')
                ->first();

            if (!$draftPrescription) {
                return [
                    'resume_available' => true,
                    'stage' => 'writing',
                    'visit' => $inProgressVisit->load([
                        'clinic',
                        'patient',
                        'appointment',
                        'queue',
                    ]),
                    'prescription' => null,
                ];
            }

            $visit = $this->emergencyComplete($inProgressVisit->id, []);
            $visit->load(['clinic', 'patient', 'appointment', 'queue']);

            return [
                'resume_available' => true,
                'stage' => 'review',
                'visit' => $visit,
                'prescription' => $visit->prescription()
                    ->with('items')
                    ->first(),
            ];
        }

        $unverifiedPrescription = Prescription::query()
            ->with('items')
            ->where('status', 'unverified')
            ->latest('id')
            ->first();

        if ($unverifiedPrescription) {
            return [
                'resume_available' => true,
                'stage' => 'review',
                'visit' => Visit::query()
                    ->with([
                        'clinic',
                        'patient',
                        'appointment',
                        'queue',
                    ])
                    ->find($unverifiedPrescription->visit_id),
                'prescription' => $unverifiedPrescription,
            ];
        }

        return [
            'resume_available' => false,
            'stage' => null,
            'visit' => null,
            'prescription' => null,
        ];
    }

    public function direct(array $data): Visit
    {
        return DB::connection('doctor')->transaction(
            function () use ($data) {

                $clinic = Clinic::find($data['clinic_id']);

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

                $patient = Patient::find($data['patient_id']);

                if (!$patient) {
                    throw ValidationException::withMessages([
                        'patient_id' => [
                            'Patient not found.',
                        ],
                    ]);
                }

                /*
            |--------------------------------------------------------------------------
            | Prevent duplicate active visit
            |--------------------------------------------------------------------------
            */

                $existingVisit = Visit::query()
                    ->where('clinic_id', $clinic->id)
                    ->where('patient_id', $patient->id)
                    ->where('status', 'in_progress')
                    ->first();

                if ($existingVisit) {
                    return $existingVisit->load([
                        'clinic',
                        'patient',
                        'appointment',
                        'queue',
                    ]);
                }

                /*
            |--------------------------------------------------------------------------
            | Create direct visit
            |--------------------------------------------------------------------------
            */

                $visit = Visit::create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'appointment_id' => null,
                    'queue_id' => null,
                    'visit_date' => now(
                        $clinic->timezone
                    )->toDateString(),
                    'started_at' => now(),
                    'status' => 'in_progress',
                    'chief_complaint' =>
                    $data['chief_complaint'] ?? null,
                ]);

                return $visit->load([
                    'clinic',
                    'patient',
                    'appointment',
                    'queue',
                ]);
            }
        );
    }

    public function resetDirect(int $visitId): void
    {
        DB::connection('doctor')->transaction(
            function () use ($visitId) {
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

                if (
                    $visit->appointment_id !== null
                    || $visit->queue_id !== null
                ) {
                    throw ValidationException::withMessages([
                        'visit_id' => [
                            'Only a direct visit can be reset.',
                        ],
                    ]);
                }

                if ($visit->status !== 'in_progress') {
                    throw ValidationException::withMessages([
                        'visit_id' => [
                            'Only an in-progress direct visit can be reset.',
                        ],
                    ]);
                }

                $prescription = Prescription::query()
                    ->where('visit_id', $visit->id)
                    ->first();

                if ($prescription) {
                    DoctorHandwritingSample::query()
                        ->where('prescription_id', $prescription->id)
                        ->get()
                        ->each(function (DoctorHandwritingSample $sample) {
                            if (
                                $sample->ink_file_path
                                && Storage::disk('local')->exists(
                                    $sample->ink_file_path
                                )
                            ) {
                                Storage::disk('local')->delete(
                                    $sample->ink_file_path
                                );
                            }

                            $sample->delete();
                        });

                    $prescription->items()->delete();
                    $prescription->forceDelete();
                }

                Investigation::query()
                    ->where('visit_id', $visit->id)
                    ->get()
                    ->each(function (Investigation $investigation) {
                        $investigation->documents
                            ->each(function (InvestigationDocument $document) {
                                if (
                                    Storage::disk('local')->exists(
                                        $document->file_path
                                    )
                                ) {
                                    Storage::disk('local')->delete(
                                        $document->file_path
                                    );
                                }

                                $document->delete();
                            });

                        $investigation->items()->delete();
                        $investigation->forceDelete();
                    });

                Procedure::query()
                    ->where('visit_id', $visit->id)
                    ->get()
                    ->each(function (Procedure $procedure) {
                        $procedure->documents
                            ->each(function (ProcedureDocument $document) {
                                if (
                                    Storage::disk('local')->exists(
                                        $document->file_path
                                    )
                                ) {
                                    Storage::disk('local')->delete(
                                        $document->file_path
                                    );
                                }

                                $document->delete();
                            });

                        $procedure->items()->delete();
                        $procedure->forceDelete();
                    });

                ClinicalExtraction::query()
                    ->where('visit_id', $visit->id)
                    ->forceDelete();

                $visit->forceDelete();
            }
        );
    }
}
