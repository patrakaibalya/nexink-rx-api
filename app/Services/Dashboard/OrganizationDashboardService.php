<?php

namespace App\Services\Dashboard;

use App\Models\DoctorMedicineSubscription;
use App\Models\PrescriptionShare;
use App\Services\Order\OrderDataProvisioningService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrganizationDashboardService
{
    /**
     * Order statuses that count as a finished conversion (a prescription
     * that actually turned into a real order) rather than a draft still
     * being reviewed or a conversion that failed/was cancelled.
     */
    private const CONVERTED_STATUSES = [
        'submitted',
        'pending',
        'processing',
        'shipped',
        'delivered',
    ];

    /** Longest trend range served in one response, to keep the day-by-day loop bounded. */
    private const MAX_TREND_DAYS = 90;

    /** How many of the latest shared prescriptions to surface on the dashboard. */
    private const RECENT_PRESCRIPTIONS_LIMIT = 8;

    public function __construct(
        protected OrderDataProvisioningService $orderDataProvisioningService
    ) {
    }

    public function summary(int $organizationId, ?string $fromDate, ?string $toDate): array
    {
        $orderTable = $this->orderDataProvisioningService->getTableName($organizationId);
        $hasOrderTable = Schema::hasTable($orderTable);

        $convertedOrders = $hasOrderTable
            ? $this->convertedOrders($orderTable, $fromDate, $toDate)
            : collect();

        $amounts = $this->itemAmountBreakdown($convertedOrders);

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,

            'consultation_count' => $this->consultationCount($organizationId, $fromDate, $toDate),

            'doctor_subscribed_count' => $this->doctorSubscribedCount($organizationId),

            'total_order_count' => $hasOrderTable
                ? $this->totalOrderCount($orderTable, $fromDate, $toDate)
                : 0,

            'converted_order_count' => $convertedOrders->count(),
            'converted_order_amount' => round((float) $convertedOrders->sum('grand_total'), 2),

            'medicine_amount' => $amounts['medicine'],
            'investigation_amount' => $amounts['investigation'],
            'procedure_amount' => $amounts['procedure'],

            'trend' => $this->trend($organizationId, $orderTable, $hasOrderTable, $fromDate, $toDate),

            'recent_prescriptions' => $this->recentPrescriptions($organizationId, $fromDate, $toDate),
        ];
    }

    /**
     * Consultation count = number of prescriptions shared with this
     * organization (each share represents one doctor consultation).
     */
    private function consultationCount(int $organizationId, ?string $fromDate, ?string $toDate): int
    {
        return PrescriptionShare::query()
            ->where('organization_id', $organizationId)
            ->when($fromDate, fn ($query) => $query->whereDate('shared_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('shared_at', '<=', $toDate))
            ->count();
    }

    /**
     * Doctors currently subscribed to this organization's medicine library
     * (approved and not yet expired) — a standing headcount, not filtered
     * by the dashboard's date range.
     */
    private function doctorSubscribedCount(int $organizationId): int
    {
        return DoctorMedicineSubscription::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'approved')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->distinct('doctor_id')
            ->count('doctor_id');
    }

    private function totalOrderCount(string $table, ?string $fromDate, ?string $toDate): int
    {
        return DB::table($table)
            ->when($fromDate, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
            ->count();
    }

    private function convertedOrders(string $table, ?string $fromDate, ?string $toDate): Collection
    {
        return DB::table($table)
            ->whereIn('status', self::CONVERTED_STATUSES)
            ->when($fromDate, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
            ->select(['id', 'items', 'grand_total'])
            ->get();
    }

    /**
     * `items` is stored as a JSON blob per order (query builder rows don't
     * get Eloquent's array casts), so the medicine/investigation/procedure
     * split is summed in PHP rather than in SQL.
     */
    private function itemAmountBreakdown(Collection $orders): array
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

    /**
     * Day-by-day series for the "prescriptions requested vs converted"
     * chart: requested = shares received that day, converted = orders
     * that reached a converted status that day.
     */
    private function trend(
        int $organizationId,
        string $orderTable,
        bool $hasOrderTable,
        ?string $fromDate,
        ?string $toDate
    ): array {
        $end = $toDate ? Carbon::parse($toDate)->startOfDay() : Carbon::now()->startOfDay();
        $start = $fromDate ? Carbon::parse($fromDate)->startOfDay() : $end->copy()->subDays(13);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        if ($start->diffInDays($end) > self::MAX_TREND_DAYS) {
            $start = $end->copy()->subDays(self::MAX_TREND_DAYS);
        }

        $requestedByDay = PrescriptionShare::query()
            ->where('organization_id', $organizationId)
            ->whereDate('shared_at', '>=', $start->toDateString())
            ->whereDate('shared_at', '<=', $end->toDateString())
            ->selectRaw('DATE(shared_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $convertedByDay = $hasOrderTable
            ? DB::table($orderTable)
                ->whereIn('status', self::CONVERTED_STATUSES)
                ->whereDate('created_at', '>=', $start->toDateString())
                ->whereDate('created_at', '<=', $end->toDateString())
                ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
                ->groupBy('day')
                ->pluck('total', 'day')
            : collect();

        $series = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $day = $cursor->toDateString();

            $series[] = [
                'date' => $day,
                'requested' => (int) ($requestedByDay[$day] ?? 0),
                'converted' => (int) ($convertedByDay[$day] ?? 0),
            ];

            $cursor->addDay();
        }

        return $series;
    }

    private function recentPrescriptions(int $organizationId, ?string $fromDate, ?string $toDate): array
    {
        return PrescriptionShare::query()
            ->where('organization_id', $organizationId)
            ->with('doctor:id,name')
            ->when($fromDate, fn ($query) => $query->whereDate('shared_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('shared_at', '<=', $toDate))
            ->latest('shared_at')
            ->limit(self::RECENT_PRESCRIPTIONS_LIMIT)
            ->get([
                'id',
                'doctor_id',
                'patient_snapshot',
                'status',
                'shared_at',
            ])
            ->map(fn (PrescriptionShare $share) => [
                'id' => $share->id,
                'patient_name' => $share->patient_snapshot['name'] ?? null,
                'doctor_name' => $share->doctor?->name,
                'status' => $share->status,
                'shared_at' => $share->shared_at?->toIso8601String(),
            ])
            ->all();
    }
}
