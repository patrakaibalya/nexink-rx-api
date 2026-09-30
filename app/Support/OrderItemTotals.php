<?php

namespace App\Support;

/**
 * Splits converted order value into medicine / investigation / procedure
 * amounts. Order rows live in per-organization tables (order_data_{id}) and
 * `items` is a raw JSON blob there, so the split is summed in PHP.
 */
class OrderItemTotals
{
    /**
     * Order statuses that count as a finished conversion.
     */
    public const CONVERTED_STATUSES = [
        'submitted',
        'pending',
        'processing',
        'shipped',
        'delivered',
    ];

    /**
     * @param iterable<object> $orders rows with an `items` JSON column
     * @return array{medicine: float, investigation: float, procedure: float}
     */
    public static function breakdown(iterable $orders): array
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
}
