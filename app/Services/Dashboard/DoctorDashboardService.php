<?php

namespace App\Services\Dashboard;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\PrescriptionShare;
use App\Models\Queue;
use App\Models\Visit;
use App\Services\Order\OrderDataProvisioningService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DoctorDashboardService
{
    /**
     * Order statuses that count as a finished conversion, mirroring
     * OrganizationDashboardService::CONVERTED_STATUSES.
     */
    private const CONVERTED_STATUSES = [
        'submitted',
        'pending',
        'processing',
        'shipped',
        'delivered',
    ];

    public function __construct(
        protected OrderDataProvisioningService $orderDataProvisioningService
    ) {
    }

    public function summary(int $clinicId): array
    {
        $clinic = Clinic::query()
            ->select([
                'id',
                'name',
                'timezone',
                'consultation_fee',
                'active_pricing_organization_id',
            ])
            ->find($clinicId);

        if (!$clinic) {
            return [
                'clinic' => null,
                'appointment_summary' => $this->emptyAppointmentSummary(),
                'queue_summary' => $this->emptyQueueSummary(),
                'consultation_earnings' => $this->emptyConsultationEarnings(),
                'catalog_value_summary' => $this->emptyCatalogValueSummary(),
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

            'consultation_earnings' => $this->consultationEarnings(
                $clinicId,
                $clinic->consultation_fee,
                $today
            ),

            'catalog_value_summary' => $this->catalogValueSummary(
                $clinic,
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

    private function consultationEarnings(
        int $clinicId,
        ?string $consultationFee,
        string $date
    ): array {
        $completedVisits = Visit::query()
            ->where('clinic_id', $clinicId)
            ->where('status', 'completed')
            ->whereDate('visit_date', $date)
            ->count();

        $fee = (float) ($consultationFee ?? 0);

        return [
            'date' => $date,
            'clinic_id' => $clinicId,
            'consultation_fee' => $consultationFee !== null
                ? $fee
                : null,
            'completed_visits' => $completedVisits,
            'total' => $completedVisits * $fee,
        ];
    }

    private function emptyConsultationEarnings(): array
    {
        return [
            'date' => null,
            'clinic_id' => null,
            'consultation_fee' => null,
            'completed_visits' => 0,
            'total' => 0,
        ];
    }

    /**
     * Medicine/investigation/procedure value for this clinic's visits today,
     * read from orders already converted by the clinic's active pricing
     * organization only — a prescription can be shared with several
     * subscribed organizations at once, but only the one the doctor picked
     * for this clinic counts toward this total.
     */
    private function catalogValueSummary(Clinic $clinic, string $date): array
    {
        $organizationId = $clinic->active_pricing_organization_id;

        if (!$organizationId) {
            return $this->emptyCatalogValueSummary($clinic->id, $date);
        }

        $visitIds = Visit::query()
            ->where('clinic_id', $clinic->id)
            ->whereDate('visit_date', $date)
            ->pluck('id');

        if ($visitIds->isEmpty()) {
            return $this->emptyCatalogValueSummary($clinic->id, $date, $organizationId);
        }

        $shareIds = PrescriptionShare::query()
            ->where('organization_id', $organizationId)
            ->whereIn('visit_id', $visitIds)
            ->pluck('id');

        $orderTable = $this->orderDataProvisioningService->getTableName($organizationId);

        if ($shareIds->isEmpty() || !Schema::hasTable($orderTable)) {
            return $this->emptyCatalogValueSummary($clinic->id, $date, $organizationId);
        }

        $orders = DB::table($orderTable)
            ->whereIn('prescription_share_id', $shareIds)
            ->whereIn('status', self::CONVERTED_STATUSES)
            ->select(['items', 'grand_total'])
            ->get();

        $amounts = $this->itemAmountBreakdown($orders);

        return [
            'date' => $date,
            'clinic_id' => $clinic->id,
            'organization_id' => $organizationId,
            'converted_order_count' => $orders->count(),
            'total' => round((float) $orders->sum('grand_total'), 2),
            'medicine_amount' => $amounts['medicine'],
            'investigation_amount' => $amounts['investigation'],
            'procedure_amount' => $amounts['procedure'],
        ];
    }

    /**
     * `items` is stored as a JSON blob per order (query builder rows don't
     * get Eloquent's array casts), so the medicine/investigation/procedure
     * split is summed in PHP rather than in SQL.
     */
    private function itemAmountBreakdown($orders): array
    {
        $totals = [
            'medicine' => 0.0,
            'investigation' => 0.0,
            'procedure' => 0.0,
        ];

        foreach ($orders as $order) {
            $items = json_decode($order->items ?? '[]', true) ?: [];

            foreach ($items as $item) {
                $type = $item['item_type'] ?? null;

                if (!isset($totals[$type])) {
                    continue;
                }

                $totals[$type] += (float) ($item['total'] ?? 0);
            }
        }

        return array_map(fn ($total) => round($total, 2), $totals);
    }

    private function emptyCatalogValueSummary(
        ?int $clinicId = null,
        ?string $date = null,
        ?int $organizationId = null
    ): array {
        return [
            'date' => $date,
            'clinic_id' => $clinicId,
            'organization_id' => $organizationId,
            'converted_order_count' => 0,
            'total' => 0,
            'medicine_amount' => 0,
            'investigation_amount' => 0,
            'procedure_amount' => 0,
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
