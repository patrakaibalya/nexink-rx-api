<?php

namespace App\Services\Report;

use App\Models\Clinic;
use App\Models\PrescriptionShare;
use App\Models\Visit;
use App\Services\Order\OrderDataProvisioningService;
use App\Support\OrderItemTotals;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Date-range reporting for one clinic: a row per visit (patient, consultation
 * fee, what was prescribed, catalog value of converted orders, report upload
 * status) plus summary totals, a daily breakdown and most-prescribed items.
 *
 * Catalog amounts follow the dashboard rule: only orders converted by the
 * clinic's active pricing organization count.
 */
class DoctorReportService
{
    private const TOP_LIMIT = 10;

    public function __construct(
        protected OrderDataProvisioningService $orderDataProvisioningService
    ) {
    }

    public function patientReport(int $clinicId, string $fromDate, string $toDate): array
    {
        $clinic = Clinic::query()
            ->select([
                'id',
                'name',
                'timezone',
                'consultation_fee',
                'active_pricing_organization_id',
            ])
            ->findOrFail($clinicId);

        $visits = Visit::query()
            ->with([
                'patient:id,name,mobile,gender,date_of_birth',
                'prescription:id,visit_id,status',
                'prescription.items:id,prescription_id,medicine_name',
                'investigations:id,visit_id,status',
                'investigations.items:id,investigation_id,test_name',
                'investigations.documents:id,investigation_id',
                'procedures:id,visit_id,status',
                'procedures.items:id,procedure_id,procedure_name',
                'procedures.documents:id,procedure_id',
            ])
            ->where('clinic_id', $clinicId)
            ->whereDate('visit_date', '>=', $fromDate)
            ->whereDate('visit_date', '<=', $toDate)
            ->orderBy('visit_date')
            ->orderBy('id')
            ->get();

        $fee = (float) ($clinic->consultation_fee ?? 0);
        $ordersByVisit = $this->convertedOrdersByVisit($clinic, $visits->pluck('id'));

        $rows = $visits->map(
            fn (Visit $visit) => $this->buildRow($visit, $fee, $ordersByVisit[$visit->id] ?? null)
        );

        return [
            'clinic' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'consultation_fee' => $clinic->consultation_fee !== null ? $fee : null,
                'pricing_organization_id' => $clinic->active_pricing_organization_id,
            ],
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => $this->summary($rows),
            'daily' => $this->daily($rows, $fromDate, $toDate),
            'top_medicines' => $this->topItems(
                $visits->flatMap(fn (Visit $visit) => $visit->prescription?->items->pluck('medicine_name') ?? [])
            ),
            'top_investigations' => $this->topItems(
                $visits->flatMap(fn (Visit $visit) => $visit->investigations->flatMap->items->pluck('test_name'))
            ),
            'top_procedures' => $this->topItems(
                $visits->flatMap(fn (Visit $visit) => $visit->procedures->flatMap->items->pluck('procedure_name'))
            ),
            'rows' => $rows->values(),
        ];
    }

    private function buildRow(Visit $visit, float $fee, ?array $orders): array
    {
        $isCompleted = $visit->status === 'completed';
        $consultationFee = $isCompleted ? $fee : 0.0;

        $medicineAmount = $orders['medicine'] ?? 0.0;
        $investigationAmount = $orders['investigation'] ?? 0.0;
        $procedureAmount = $orders['procedure'] ?? 0.0;
        $catalogTotal = $orders['grand_total'] ?? 0.0;

        // An investigation / procedure is "awaiting report" when it has
        // something ordered on it but no document uploaded yet.
        $reportable = $visit->investigations->concat($visit->procedures)
            ->filter(fn ($record) => $record->items->isNotEmpty() && $record->status !== 'cancelled');

        return [
            'visit_id' => $visit->id,
            'visit_date' => $visit->visit_date?->toDateString(),
            'visit_status' => $visit->status,
            'patient' => [
                'id' => $visit->patient?->id,
                'name' => $visit->patient?->name,
                'mobile' => $visit->patient?->mobile,
                'gender' => $visit->patient?->gender,
                'age' => $visit->patient?->date_of_birth
                    ? Carbon::parse($visit->patient->date_of_birth)->age
                    : null,
            ],
            'prescription_id' => $visit->prescription?->id,
            'prescription_status' => $visit->prescription?->status,
            'medicines_count' => $visit->prescription?->items->count() ?? 0,
            'investigations_count' => $visit->investigations->sum(fn ($record) => $record->items->count()),
            'procedures_count' => $visit->procedures->sum(fn ($record) => $record->items->count()),
            'reports_uploaded' => $reportable->sum(fn ($record) => $record->documents->count()),
            'reports_pending' => $reportable->filter(fn ($record) => $record->documents->isEmpty())->count(),
            'consultation_fee' => $consultationFee,
            'medicine_amount' => $medicineAmount,
            'investigation_amount' => $investigationAmount,
            'procedure_amount' => $procedureAmount,
            'catalog_total' => $catalogTotal,
            'order_refs' => $orders['order_refs'] ?? [],
            'total_amount' => round($consultationFee + $catalogTotal, 2),
        ];
    }

    /**
     * [visitId => ['medicine', 'investigation', 'procedure', 'grand_total', 'order_refs']]
     * for orders converted by the clinic's active pricing organization.
     */
    private function convertedOrdersByVisit(Clinic $clinic, Collection $visitIds): array
    {
        $organizationId = $clinic->active_pricing_organization_id;

        if (!$organizationId || $visitIds->isEmpty()) {
            return [];
        }

        $visitIdByShareId = PrescriptionShare::query()
            ->where('organization_id', $organizationId)
            ->whereIn('visit_id', $visitIds)
            ->pluck('visit_id', 'id');

        $table = $this->orderDataProvisioningService->getTableName($organizationId);

        if ($visitIdByShareId->isEmpty() || !Schema::hasTable($table)) {
            return [];
        }

        $orders = DB::table($table)
            ->whereIn('prescription_share_id', $visitIdByShareId->keys())
            ->whereIn('status', OrderItemTotals::CONVERTED_STATUSES)
            ->get(['prescription_share_id', 'order_ref_id', 'items', 'grand_total']);

        return $orders
            ->groupBy(fn ($order) => $visitIdByShareId[$order->prescription_share_id])
            ->map(function (Collection $visitOrders) {
                $amounts = OrderItemTotals::breakdown($visitOrders);

                return [
                    ...$amounts,
                    'grand_total' => round((float) $visitOrders->sum('grand_total'), 2),
                    'order_refs' => $visitOrders->pluck('order_ref_id')->filter()->values()->all(),
                ];
            })
            ->all();
    }

    private function summary(Collection $rows): array
    {
        $visitCount = $rows->count();
        $grandTotal = round($rows->sum('total_amount'), 2);

        return [
            'total_visits' => $visitCount,
            'completed_visits' => $rows->where('visit_status', 'completed')->count(),
            'unique_patients' => $rows->pluck('patient.id')->filter()->unique()->count(),
            'consultation_earnings' => round($rows->sum('consultation_fee'), 2),
            'medicine_amount' => round($rows->sum('medicine_amount'), 2),
            'investigation_amount' => round($rows->sum('investigation_amount'), 2),
            'procedure_amount' => round($rows->sum('procedure_amount'), 2),
            'catalog_total' => round($rows->sum('catalog_total'), 2),
            'grand_total' => $grandTotal,
            'converted_orders' => $rows->sum(fn ($row) => count($row['order_refs'])),
            'average_per_visit' => $visitCount ? round($grandTotal / $visitCount, 2) : 0,
            'medicines_prescribed' => $rows->sum('medicines_count'),
            'investigations_prescribed' => $rows->sum('investigations_count'),
            'procedures_prescribed' => $rows->sum('procedures_count'),
            'reports_pending' => $rows->sum('reports_pending'),
        ];
    }

    /**
     * One entry per calendar day in the range (days without visits are
     * zero-filled so the chart has a continuous axis).
     */
    private function daily(Collection $rows, string $fromDate, string $toDate): array
    {
        $byDate = $rows->groupBy('visit_date');
        $days = [];

        for ($day = Carbon::parse($fromDate); $day->lte(Carbon::parse($toDate)); $day->addDay()) {
            $date = $day->toDateString();
            $dayRows = $byDate->get($date, collect());

            $days[] = [
                'date' => $date,
                'visits' => $dayRows->count(),
                'consultation_earnings' => round($dayRows->sum('consultation_fee'), 2),
                'catalog_total' => round($dayRows->sum('catalog_total'), 2),
                'total_amount' => round($dayRows->sum('total_amount'), 2),
            ];
        }

        return $days;
    }

    /**
     * Most frequent names, case-insensitively grouped, keeping the first
     * spelling seen for display.
     */
    private function topItems(Collection $names): array
    {
        return $names
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->groupBy(fn ($name) => mb_strtolower(trim($name)))
            ->map(fn (Collection $group) => [
                'name' => trim($group->first()),
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(self::TOP_LIMIT)
            ->values()
            ->all();
    }
}
