<?php

namespace App\Services\Report;

use App\Models\DoctorAccount;
use App\Models\PrescriptionShare;
use App\Services\Order\OrderDataProvisioningService;
use App\Support\OrderItemTotals;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Date-range sales reporting for one medicine organization: a row per order
 * created in the range, summary totals, a daily breakdown, a doctor-wise
 * referral summary and the top-selling items.
 *
 * Revenue only counts converted orders (OrderItemTotals::CONVERTED_STATUSES),
 * matching the organization dashboard.
 */
class OrganizationReportService
{
    private const TOP_LIMIT = 10;

    private const PENDING_STATUSES = ['converting', 'draft'];

    private const LOST_STATUSES = ['conversion_failed', 'cancelled'];

    public function __construct(
        protected OrderDataProvisioningService $orderDataProvisioningService
    ) {
    }

    public function salesReport(
        int $organizationId,
        string $fromDate,
        string $toDate,
        ?int $doctorId = null
    ): array {
        $shares = PrescriptionShare::query()
            ->where('organization_id', $organizationId)
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId))
            ->whereDate('shared_at', '>=', $fromDate)
            ->whereDate('shared_at', '<=', $toDate)
            ->get(['id', 'doctor_id']);

        $orders = $this->orders($organizationId, $fromDate, $toDate, $doctorId);

        $doctorNames = DoctorAccount::query()
            ->whereIn('id', $shares->pluck('doctor_id')->merge($orders->pluck('doctor_id'))->filter()->unique())
            ->pluck('name', 'id');

        $rows = $orders->map(fn ($order) => $this->buildRow($order, $doctorNames));

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'doctor_id' => $doctorId,
            'summary' => $this->summary($rows, $shares->count()),
            'daily' => $this->daily($rows, $fromDate, $toDate),
            'doctors' => $this->doctorSummary($rows, $shares, $doctorNames),
            'top_medicines' => $this->topItems($orders, 'medicine'),
            'top_investigations' => $this->topItems($orders, 'investigation'),
            'top_procedures' => $this->topItems($orders, 'procedure'),
            'rows' => $rows->values(),
        ];
    }

    /**
     * Orders created in the range, each with its share's doctor and clinic
     * attached. Order rows live in order_data_{organizationId}, so the share
     * details are looked up separately and merged in PHP.
     */
    private function orders(int $organizationId, string $fromDate, string $toDate, ?int $doctorId): Collection
    {
        $table = $this->orderDataProvisioningService->getTableName($organizationId);

        if (!Schema::hasTable($table)) {
            return collect();
        }

        $orders = DB::table($table)
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get([
                'id',
                'prescription_share_id',
                'order_ref_id',
                'patient_snapshot',
                'doctor_name',
                'status',
                'items',
                'grand_total',
                'created_at',
            ]);

        $sharesById = PrescriptionShare::query()
            ->whereIn('id', $orders->pluck('prescription_share_id')->filter()->unique())
            ->get(['id', 'doctor_id', 'clinic_snapshot'])
            ->keyBy('id');

        return $orders
            ->map(function ($order) use ($sharesById) {
                $share = $sharesById->get($order->prescription_share_id);

                $order->doctor_id = $share?->doctor_id;
                $order->clinic_name = $share?->clinic_snapshot['name']
                    ?? $share?->clinic_snapshot['clinic_name']
                    ?? null;
                $order->decoded_items = json_decode($order->items ?? '[]', true) ?: [];

                return $order;
            })
            ->when($doctorId, fn (Collection $rows) => $rows->where('doctor_id', $doctorId))
            ->values();
    }

    private function buildRow(object $order, Collection $doctorNames): array
    {
        $patient = json_decode($order->patient_snapshot ?? '{}', true) ?: [];
        $amounts = OrderItemTotals::breakdown([$order]);
        $countOf = fn (string $type) => collect($order->decoded_items)
            ->where('item_type', $type)
            ->count();

        return [
            'order_id' => $order->id,
            'order_ref_id' => $order->order_ref_id,
            'order_date' => Carbon::parse($order->created_at)->toDateString(),
            'status' => $order->status,
            'is_converted' => in_array($order->status, OrderItemTotals::CONVERTED_STATUSES, true),
            'patient' => [
                'name' => $patient['name'] ?? null,
                'mobile' => $patient['mobile'] ?? null,
                'gender' => $patient['gender'] ?? null,
                'age' => !empty($patient['date_of_birth'])
                    ? Carbon::parse($patient['date_of_birth'])->age
                    : ($patient['age'] ?? null),
            ],
            'doctor_id' => $order->doctor_id,
            'doctor_name' => $doctorNames[$order->doctor_id] ?? $order->doctor_name,
            'clinic_name' => $order->clinic_name,
            'medicines_count' => $countOf('medicine'),
            'investigations_count' => $countOf('investigation'),
            'procedures_count' => $countOf('procedure'),
            'medicine_amount' => $amounts['medicine'],
            'investigation_amount' => $amounts['investigation'],
            'procedure_amount' => $amounts['procedure'],
            'grand_total' => round((float) $order->grand_total, 2),
        ];
    }

    private function summary(Collection $rows, int $prescriptionsReceived): array
    {
        $converted = $rows->where('is_converted', true);
        $convertedCount = $converted->count();
        $revenue = round($converted->sum('grand_total'), 2);

        return [
            'prescriptions_received' => $prescriptionsReceived,
            'total_orders' => $rows->count(),
            'converted_orders' => $convertedCount,
            'pending_orders' => $rows->whereIn('status', self::PENDING_STATUSES)->count(),
            'lost_orders' => $rows->whereIn('status', self::LOST_STATUSES)->count(),
            'conversion_rate' => $prescriptionsReceived
                ? round($convertedCount / $prescriptionsReceived * 100, 1)
                : 0,
            'revenue' => $revenue,
            'medicine_amount' => round($converted->sum('medicine_amount'), 2),
            'investigation_amount' => round($converted->sum('investigation_amount'), 2),
            'procedure_amount' => round($converted->sum('procedure_amount'), 2),
            'average_order_value' => $convertedCount ? round($revenue / $convertedCount, 2) : 0,
            'unique_patients' => $rows
                ->map(fn ($row) => mb_strtolower(($row['patient']['name'] ?? '') . '|' . ($row['patient']['mobile'] ?? '')))
                ->filter(fn ($key) => $key !== '|')
                ->unique()
                ->count(),
            'active_doctors' => $rows->pluck('doctor_id')->filter()->unique()->count(),
        ];
    }

    /**
     * One entry per calendar day in the range (zero-filled for a continuous chart axis).
     */
    private function daily(Collection $rows, string $fromDate, string $toDate): array
    {
        $byDate = $rows->groupBy('order_date');
        $days = [];

        for ($day = Carbon::parse($fromDate); $day->lte(Carbon::parse($toDate)); $day->addDay()) {
            $date = $day->toDateString();
            $converted = $byDate->get($date, collect())->where('is_converted', true);

            $days[] = [
                'date' => $date,
                'orders' => $byDate->get($date, collect())->count(),
                'converted_orders' => $converted->count(),
                'medicine_amount' => round($converted->sum('medicine_amount'), 2),
                'investigation_amount' => round($converted->sum('investigation_amount'), 2),
                'procedure_amount' => round($converted->sum('procedure_amount'), 2),
                'revenue' => round($converted->sum('grand_total'), 2),
            ];
        }

        return $days;
    }

    /**
     * Per referring doctor: prescriptions shared → orders → converted → revenue.
     */
    private function doctorSummary(Collection $rows, Collection $shares, Collection $doctorNames): array
    {
        $sharesByDoctor = $shares->countBy('doctor_id');
        $rowsByDoctor = $rows->groupBy(fn ($row) => $row['doctor_id'] ?? 0);

        return $sharesByDoctor->keys()
            ->merge($rowsByDoctor->keys())
            ->unique()
            ->map(function ($doctorId) use ($sharesByDoctor, $rowsByDoctor, $doctorNames) {
                $doctorRows = $rowsByDoctor->get($doctorId, collect());
                $converted = $doctorRows->where('is_converted', true);
                $shared = $sharesByDoctor->get($doctorId, 0);

                return [
                    'doctor_id' => $doctorId ?: null,
                    'doctor_name' => $doctorNames[$doctorId] ?? $doctorRows->first()['doctor_name'] ?? 'Unknown',
                    'prescriptions' => $shared,
                    'orders' => $doctorRows->count(),
                    'converted_orders' => $converted->count(),
                    'conversion_rate' => $shared ? round($converted->count() / $shared * 100, 1) : 0,
                    'revenue' => round($converted->sum('grand_total'), 2),
                ];
            })
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }

    /**
     * Best-selling catalog items of one type across converted orders, by
     * quantity (falling back to 1 per line when quantity is missing).
     */
    private function topItems(Collection $orders, string $type): array
    {
        return $orders
            ->filter(fn ($order) => in_array($order->status, OrderItemTotals::CONVERTED_STATUSES, true))
            ->flatMap(fn ($order) => $order->decoded_items)
            ->filter(fn ($item) => ($item['item_type'] ?? null) === $type && trim((string) ($item['name'] ?? '')) !== '')
            ->groupBy(fn ($item) => mb_strtolower(trim($item['name'])))
            ->map(fn (Collection $group) => [
                'name' => trim($group->first()['name']),
                'count' => (int) $group->sum(fn ($item) => max(1, (float) ($item['quantity'] ?? 1))),
                'amount' => round($group->sum(fn ($item) => (float) ($item['total'] ?? 0)), 2),
            ])
            ->sortByDesc('count')
            ->take(self::TOP_LIMIT)
            ->values()
            ->all();
    }
}
