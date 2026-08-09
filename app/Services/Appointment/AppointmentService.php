<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function create(array $data): Appointment
    {
        return DB::connection('doctor')->transaction(
            function () use ($data) {
                $clinic = Clinic::find($data['clinic_id']);

                if (!$clinic) {
                    throw ValidationException::withMessages([
                        'clinic_id' => ['Clinic not found.'],
                    ]);
                }

                if (!$clinic->is_active) {
                    throw ValidationException::withMessages([
                        'clinic_id' => ['Clinic is inactive.'],
                    ]);
                }

                $patient = Patient::find($data['patient_id']);

                if (!$patient) {
                    throw ValidationException::withMessages([
                        'patient_id' => ['Patient not found.'],
                    ]);
                }

                $appointment = Appointment::create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'appointment_date' => $data['appointment_date'],
                    'appointment_time' => $data['appointment_time'] ?? null,
                    'status' => 'scheduled',
                    'source' => $data['source'] ?? 'web',
                    'reason' => $data['reason'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                return $appointment->fresh([
                    'clinic',
                    'patient',
                ]);
            }
        );
    }


    public function list(array $filters)
    {
        $query = Appointment::query()
            ->with([
                'clinic',
                'patient',
            ])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time');

        if (!empty($filters['clinic_id'])) {
            $query->where(
                'clinic_id',
                $filters['clinic_id']
            );
        }

        if (!empty($filters['date'])) {
            $query->where(
                'appointment_date',
                $filters['date']
            );
        }

        if (!empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        if (!empty($filters['patient_id'])) {
            $query->where(
                'patient_id',
                $filters['patient_id']
            );
        }

        return $query->get();
    }

    public function find(int $appointmentId): Appointment
    {
        $appointment = Appointment::query()
            ->with([
                'clinic',
                'patient',
            ])
            ->find($appointmentId);

        if (!$appointment) {
            throw ValidationException::withMessages([
                'appointment_id' => ['Appointment not found.'],
            ]);
        }

        return $appointment;
    }
    public function update(
        int $appointmentId,
        array $data
    ): Appointment {
        return DB::connection('doctor')->transaction(
            function () use ($appointmentId, $data) {
                $appointment = Appointment::query()
                    ->lockForUpdate()
                    ->find($appointmentId);

                if (!$appointment) {
                    throw ValidationException::withMessages([
                        'appointment_id' => ['Appointment not found.'],
                    ]);
                }

                if (in_array($appointment->status, [
                    'completed',
                    'cancelled',
                    'no_show',
                ], true)) {
                    throw ValidationException::withMessages([
                        'appointment' => [
                            'This appointment can no longer be updated.',
                        ],
                    ]);
                }

                if (isset($data['clinic_id'])) {
                    $clinic = Clinic::find($data['clinic_id']);

                    if (!$clinic) {
                        throw ValidationException::withMessages([
                            'clinic_id' => ['Clinic not found.'],
                        ]);
                    }

                    if (!$clinic->is_active) {
                        throw ValidationException::withMessages([
                            'clinic_id' => ['Clinic is inactive.'],
                        ]);
                    }
                }

                if (isset($data['patient_id'])) {
                    $patient = Patient::find($data['patient_id']);

                    if (!$patient) {
                        throw ValidationException::withMessages([
                            'patient_id' => ['Patient not found.'],
                        ]);
                    }
                }

                $appointment->update($data);

                return $appointment->fresh([
                    'clinic',
                    'patient',
                ]);
            }
        );
    }

    public function delete(int $appointmentId): void
    {
        $appointment = Appointment::find($appointmentId);

        if (!$appointment) {
            throw ValidationException::withMessages([
                'appointment_id' => ['Appointment not found.'],
            ]);
        }

        if (in_array($appointment->status, [
            'completed',
            'arrived',
        ], true)) {
            throw ValidationException::withMessages([
                'appointment' => [
                    'Completed or arrived appointments cannot be deleted.',
                ],
            ]);
        }

        $appointment->delete();
    }

    public function updateStatus(
        int $appointmentId,
        string $newStatus
    ): Appointment {
        return DB::connection('doctor')->transaction(
            function () use ($appointmentId, $newStatus) {
                $appointment = Appointment::query()
                    ->lockForUpdate()
                    ->find($appointmentId);

                if (!$appointment) {
                    throw ValidationException::withMessages([
                        'appointment_id' => [
                            'Appointment not found.',
                        ],
                    ]);
                }

                $allowedTransitions = [
                    'scheduled' => [
                        'confirmed',
                        'cancelled',
                        'no_show',
                    ],

                    'confirmed' => [
                        'arrived',
                        'cancelled',
                        'no_show',
                    ],

                    'arrived' => [
                        'completed',
                    ],

                    'completed' => [],

                    'cancelled' => [],

                    'no_show' => [],
                ];

                if (!in_array(
                    $newStatus,
                    $allowedTransitions[$appointment->status] ?? [],
                    true
                )) {
                    throw ValidationException::withMessages([
                        'status' => [
                            "Cannot change appointment status from "
                                . "{$appointment->status} to {$newStatus}.",
                        ],
                    ]);
                }

                $now = now();

                $updates = [
                    'status' => $newStatus,
                ];

                if ($newStatus === 'confirmed') {
                    $updates['confirmed_at'] = $now;
                }

                if ($newStatus === 'arrived') {
                    $updates['arrived_at'] = $now;
                }

                if ($newStatus === 'completed') {
                    $updates['completed_at'] = $now;
                }

                if ($newStatus === 'cancelled') {
                    $updates['cancelled_at'] = $now;
                }

                $appointment->update($updates);

                return $appointment->fresh([
                    'clinic',
                    'patient',
                ]);
            }
        );
    }


    public function arrive(
        int $appointmentId
    ): array {
        return DB::connection('doctor')->transaction(
            function () use ($appointmentId) {

                $appointment = Appointment::query()
                    ->lockForUpdate()
                    ->find($appointmentId);

                if (!$appointment) {
                    throw ValidationException::withMessages([
                        'appointment_id' => [
                            'Appointment not found.',
                        ],
                    ]);
                }

                if (!in_array($appointment->status, [
                    'scheduled',
                    'confirmed',
                ], true)) {
                    throw ValidationException::withMessages([
                        'appointment' => [
                            "Appointment cannot be marked as arrived from "
                                . "{$appointment->status}.",
                        ],
                    ]);
                }

                $appointment->update([
                    'status' => 'arrived',
                    'arrived_at' => now(),
                ]);

                $queueService = app(
                    \App\Services\Queue\QueueService::class
                );

                $existingQueue = Queue::query()
                    ->where('appointment_id', $appointment->id)
                    ->first();

                if ($existingQueue) {
                    return [
                        'appointment' => $appointment->fresh([
                            'clinic',
                            'patient',
                        ]),
                        'queue' => $existingQueue->load([
                            'clinic',
                            'patient',
                            'appointment',
                        ]),
                    ];
                }

                $queueService = app(
                    \App\Services\Queue\QueueService::class
                );

                $queue = $queueService->create([
                    'clinic_id' => $appointment->clinic_id,
                    'patient_id' => $appointment->patient_id,
                    'source' => 'web',
                    'notes' => 'Created from appointment.',
                    'appointment_id' => $appointment->id,
                ]);

                return [
                    'appointment' => $appointment->fresh([
                        'clinic',
                        'patient',
                    ]),
                    'queue' => $queue->load([
                        'clinic',
                        'patient',
                    ]),
                ];
            }
        );
    }

    public function bookForPatient(
        array $data
    ): Appointment {
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

                $appointment = Appointment::create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'appointment_date' => $data['appointment_date'],
                    'appointment_time' => $data['appointment_time'],
                    'status' => 'scheduled',
                    'source' => 'patient',
                    'reason' => $data['reason'] ?? null,
                ]);

                return $appointment->fresh([
                    'clinic',
                    'patient',
                ]);
            }
        );
    }

    public function availability(
        int $clinicId,
        string $date
    ): array {
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

        $appointments = Appointment::query()
            ->where('clinic_id', $clinicId)
            ->where('appointment_date', $date)
            ->whereNotIn('status', [
                'cancelled',
                'no_show',
            ])
            ->orderBy('appointment_time')
            ->get([
                'id',
                'patient_id',
                'appointment_time',
                'status',
            ]);

        return [
            'clinic_id' => $clinicId,
            'date' => $date,
            'appointments' => $appointments,
        ];
    }
}
