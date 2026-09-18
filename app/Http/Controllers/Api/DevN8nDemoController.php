<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stands in for the real n8n "convert prescription to order" AI webhook
 * until that workflow exists. Picks the first candidate per prescribed
 * item and does a rough quantity estimate, matching the exact response
 * shape ConvertPrescriptionToOrderJob expects from the real webhook —
 * swap `services.n8n.order_conversion_webhook` to the real n8n URL and
 * delete this once the AI workflow is built.
 */
class DevN8nDemoController extends Controller
{
    public function orderConversion(Request $request): JsonResponse
    {
        $items = collect($request->input('prescribed_items', []))
            ->map(fn (array $item) => $this->resolveItem($item))
            ->all();

        return response()->json([
            'status' => 'success',
            'items' => $items,
        ]);
    }

    private function resolveItem(array $item): array
    {
        $candidates = $item['candidates'] ?? [];
        $bestMatch = $candidates[0] ?? null;

        if (!$bestMatch) {
            return [
                'item_type' => $item['item_type'] ?? null,
                'prescribed_name' => $item['name'] ?? null,
                'matched_catalog_id' => null,
                'matched_name' => null,
                'source' => null,
                'quantity' => null,
                'rate' => null,
                'total' => null,
                'match_status' => 'unmatched',
            ];
        }

        $rate = (float) ($bestMatch['sale_price'] ?? 0);
        $quantity = $this->estimateQuantity($item);
        $total = round($rate * $quantity, 2);

        return [
            'item_type' => $item['item_type'] ?? null,
            'prescribed_name' => $item['name'] ?? null,
            'matched_catalog_id' => $bestMatch['catalog_id'] ?? null,
            'matched_name' => $bestMatch['name'] ?? null,
            'source' => $bestMatch['source'] ?? null,
            'quantity' => $quantity,
            'rate' => $rate,
            'total' => $total,
            'match_status' => 'matched',
        ];
    }

    /**
     * Medicines: rough dose-count (non-zero segments in a "1-0-1" style
     * frequency string) x days parsed from the duration string.
     * Investigations/procedures: always one unit.
     */
    private function estimateQuantity(array $item): int
    {
        if (($item['item_type'] ?? null) !== 'medicine') {
            return 1;
        }

        $frequency = (string) ($item['frequency'] ?? '');
        $dosesPerDay = collect(explode('-', $frequency))
            ->filter(fn ($segment) => trim($segment) !== '' && trim($segment) !== '0')
            ->count();
        $dosesPerDay = max($dosesPerDay, 1);

        $duration = (string) ($item['duration'] ?? '');
        preg_match('/\d+/', $duration, $matches);
        $days = isset($matches[0]) ? (int) $matches[0] : 1;
        $days = max($days, 1);

        return $dosesPerDay * $days;
    }
}
