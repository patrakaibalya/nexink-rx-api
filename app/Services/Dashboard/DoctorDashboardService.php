<?php

namespace App\Services\Dashboard;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Queue;
use Carbon\Carbon;

class DoctorDashboardService
{
    public function summary(int $clinicId): array
    {
        $clinic = Clinic::query()
            ->select([
                'id',
                'name',
                'timezone',
            ])
            ->find($clinicId);

        if (!$clinic) {
            return [
                'clinic' => null,
                'appointment_summary' => $this->emptyAppointmentSummary(),
                'queue_summary' => $this->emptyQueueSummary(),
                'upcoming_appointments' => [],
                'current_queue' => [],
            ];
        }

        $today = Carbon::now($clinic->timezone)
            ->toDateString();

        return [
            'clinic' => $clinic,

            'appointment_summary' => $this->appointmentSummary(
                $clinicId,
                $today
            ),

            'queue_summary' => $this->queueSummary(
                $clinicId,
                $today
            ),

            'upcoming_appointments' => Appointment::query()
                ->with([
                    'clinic',
                    'patient',
                ])
                ->where('clinic_id', $clinicId)
                ->whereDate(
                    'appointment_date',
                    '>=',
                    $today
                )
                ->whereNotIn('status', [
                    'cancelled',
                    'no_show',
                    'completed',
                ])
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->limit(5)
                ->get(),

            'current_queue' => Queue::query()
                ->with([
                    'clinic',
                    'patient',
                ])
                ->where('clinic_id', $clinicId)
                ->whereDate(
                    'queue_date',
                    $today
                )
                ->whereIn('status', [
                    'waiting',
                    'called',
                    'consulting',
                ])
                ->orderBy('queue_number')
                ->limit(10)
                ->get(),
        ];
    }

    private function appointmentSummary(
        int $clinicId,
        string $date
    ): array {
        $counts = Appointment::query()
            ->where('clinic_id', $clinicId)
            ->whereDate(
                'appointment_date',
                $date
            )
            ->selectRaw(
                'status, COUNT(*) as total'
            )
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'date' => $date,
            'clinic_id' => $clinicId,
            'total' => (int) $counts->sum(),

            'scheduled' => (int) ($counts['scheduled'] ?? 0),
            'confirmed' => (int) ($counts['confirmed'] ?? 0),
            'arrived' => (int) ($counts['arrived'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
            'no_show' => (int) ($counts['no_show'] ?? 0),
        ];
    }

    private function queueSummary(
        int $clinicId,
        string $date
    ): array {
        $counts = Queue::query()
            ->where('clinic_id', $clinicId)
            ->whereDate(
                'queue_date',
                $date
            )
            ->selectRaw(
                'status, COUNT(*) as total'
            )
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

    private function emptyAppointmentSummary(): array
    {
        return [
            'date' => null,
            'clinic_id' => null,
            'total' => 0,
            'scheduled' => 0,
            'confirmed' => 0,
            'arrived' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'no_show' => 0,
        ];
    }

    private function emptyQueueSummary(): array
    {
        return [
            'date' => null,
            'clinic_id' => null,
            'total' => 0,
            'waiting' => 0,
            'called' => 0,
            'consulting' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'no_show' => 0,
        ];
    }
}
