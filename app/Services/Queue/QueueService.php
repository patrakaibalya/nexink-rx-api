<?php

namespace App\Services\Queue;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueService
{
    public function create(array $data): Queue
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

                $queueDate = now()->toDateString();

                $lastQueue = Queue::where('clinic_id', $clinic->id)
                    ->whereDate('queue_date', $queueDate)
                    ->lockForUpdate()
                    ->orderByDesc('queue_number')
                    ->first();

                $queueNumber = $lastQueue
                    ? $lastQueue->queue_number + 1
                    : 1;

                return Queue::create([
                    'clinic_id' => $clinic->id,
                    'patient_id' => $patient->id,
                    'queue_number' => $queueNumber,
                    'queue_date' => $queueDate,
                    'source' => $data['source'],
                    'status' => 'waiting',
                    'arrived_at' => now(),
                    'notes' => $data['notes'] ?? null,
                    'appointment_id' => $data['appointment_id'] ?? null,
                ]);
            }
        );
    }

    public function updateStatus(
        int $queueId,
        string $newStatus
    ): Queue {
        return DB::connection('doctor')->transaction(
            function () use ($queueId, $newStatus) {
                $queue = Queue::query()
                    ->lockForUpdate()
                    ->find($queueId);

                if (!$queue) {
                    throw ValidationException::withMessages([
                        'queue_id' => ['Queue not found.'],
                    ]);
                }

                $allowedTransitions = [
                    'waiting' => [
                        'called',
                        'cancelled',
                        'no_show',
                    ],

                    'called' => [
                        'consulting',
                        'no_show',
                    ],

                    'consulting' => [
                        'completed',
                    ],

                    'completed' => [],

                    'cancelled' => [],

                    'no_show' => [
                        'called',
                    ],
                ];

                if (!in_array(
                    $newStatus,
                    $allowedTransitions[$queue->status] ?? [],
                    true
                )) {
                    throw ValidationException::withMessages([
                        'status' => [
                            "Cannot change queue status from "
                                . "{$queue->status} to {$newStatus}."
                        ],
                    ]);
                }

                $now = now();

                $updates = [
                    'status' => $newStatus,
                ];

                if ($newStatus === 'called') {
                    $updates['called_at'] = $now;
                }

                if ($newStatus === 'consulting') {
                    $updates['consultation_started_at'] = $now;
                }

                if ($newStatus === 'completed') {
                    $updates['completed_at'] = $now;
                }

                if ($newStatus === 'cancelled') {
                    $updates['cancelled_at'] = $now;
                }

                $queue->update($updates);

                return $queue->fresh([
                    'clinic',
                    'patient',
                ]);
            }
        );
    }

    public function callNext(int $clinicId): Queue
    {
        return DB::connection('doctor')->transaction(
            function () use ($clinicId) {
                $clinic = Clinic::find($clinicId);

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

                $queueDate = now()->toDateString();

                $queue = Queue::query()
                    ->where('clinic_id', $clinicId)
                    ->whereDate('queue_date', $queueDate)
                    ->where('status', 'waiting')
                    ->orderBy('queue_number')
                    ->lockForUpdate()
                    ->first();

                if (!$queue) {
                    return null;
                }

                $queue->update([
                    'status' => 'called',
                    'called_at' => now(),
                ]);

                return $queue->fresh([
                    'clinic',
                    'patient',
                ]);
            }
        );
    }

    public function current(int $clinicId): ?Queue
    {
        return Queue::query()
            ->with([
                'clinic',
                'patient',
            ])
            ->where('clinic_id', $clinicId)
            ->whereDate('queue_date', now()->toDateString())
            ->whereIn('status', [
                'called',
                'consulting',
            ])
            ->orderByDesc('called_at')
            ->first();
    }

    public function summary(
        int $clinicId,
        ?string $date = null
    ): array {
        $date ??= now()->toDateString();

        $query = Queue::query()
            ->where('clinic_id', $clinicId)
            ->whereDate('queue_date', $date);

        $counts = $query
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'date' => $date,
            'clinic_id' => $clinicId,
            'total' => (int) $counts->sum(),

            'waiting' => (int) ($counts['waiting'] ?? 0),

            'called' => (int) ($counts['called'] ?? 0),

            'consulting' => (int) ($counts['consulting'] ?? 0),

            'completed' => (int) ($counts['completed'] ?? 0),

            'cancelled' => (int) ($counts['cancelled'] ?? 0),

            'no_show' => (int) ($counts['no_show'] ?? 0),
        ];
    }

    public function history(array $filters)
    {
        $query = Queue::query()
            ->with([
                'clinic',
                'patient',
            ])
            ->where(
                'clinic_id',
                $filters['clinic_id']
            )
            ->whereBetween(
                'queue_date',
                [
                    $filters['from'],
                    $filters['to'],
                ]
            )
            ->orderByDesc('queue_date')
            ->orderBy('queue_number');

        if (!empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        return $query->get();
    }
}
