<?php

namespace App\Services\Visit;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
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
}
