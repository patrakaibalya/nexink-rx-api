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

                    'no_show' => [],
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
                    throw ValidationException::withMessages([
                        'queue' => ['No waiting patients in the queue.'],
                    ]);
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
}
