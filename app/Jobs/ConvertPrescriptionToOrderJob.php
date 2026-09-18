<?php

namespace App\Jobs;

use App\Models\MedicineOrganization;
use App\Models\PrescriptionShare;
use App\Services\Order\OrderConversionService;
use App\Services\Order\OrderDataProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class ConvertPrescriptionToOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $organizationId,
        public int $orderId,
        public int $prescriptionShareId
    ) {
    }

    public function handle(
        OrderConversionService $conversionService,
        OrderDataProvisioningService $orderDataProvisioningService
    ): void {
        $organization = MedicineOrganization::query()->find($this->organizationId);

        if (!$organization) {
            return;
        }

        $table = $orderDataProvisioningService->getTableName($organization->id);

        $order = DB::table($table)->where('id', $this->orderId)->first();

        if (!$order || $order->status !== 'converting') {
            return;
        }

        $share = PrescriptionShare::query()->find($this->prescriptionShareId);

        if (!$share) {
            $this->markFailed($table, 'Source prescription share no longer exists.');
            return;
        }

        $prescribedItems = $conversionService->buildPrescribedItems($share, $organization);

        $webhookUrl = config('services.n8n.order_conversion_webhook');

        if (!$webhookUrl) {
            $this->markFailed($table, 'n8n Order Conversion webhook URL is not configured.');
            return;
        }

        $response = Http::timeout(300)
            ->acceptJson()
            ->post($webhookUrl, [
                'order_id' => $this->orderId,
                'prescription_share_id' => $share->id,
                'organization_id' => $organization->id,
                'patient_snapshot' => $share->patient_snapshot,
                'clinic_snapshot' => $share->clinic_snapshot,
                'prescribed_items' => $prescribedItems,
            ]);

        $response->throw();

        $result = $response->json();

        if (($result['status'] ?? null) !== 'success' || !is_array($result['items'] ?? null)) {
            $this->markFailed($table, 'Invalid order conversion result returned by n8n.');
            return;
        }

        $items = array_map(fn (array $item) => [
            'item_type' => $item['item_type'] ?? null,
            'catalog_id' => $item['matched_catalog_id'] ?? null,
            'catalog_source' => $item['source'] ?? null,
            'name' => $item['matched_name'] ?? $item['prescribed_name'] ?? null,
            'quantity' => $item['quantity'] ?? null,
            'rate' => $item['rate'] ?? null,
            'total' => $item['total'] ?? null,
            'match_status' => $item['match_status'] ?? 'unmatched',
        ], $result['items']);

        $grandTotal = array_sum(array_column($items, 'total'));

        DB::table($table)->where('id', $this->orderId)->update([
            'items' => json_encode($items),
            'ai_meta' => json_encode($result),
            'grand_total' => $grandTotal,
            'status' => 'draft',
            'updated_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $organization = MedicineOrganization::query()->find($this->organizationId);

        if (!$organization) {
            return;
        }

        $table = app(OrderDataProvisioningService::class)->getTableName($organization->id);

        $this->markFailed($table, $exception->getMessage());
    }

    private function markFailed(string $table, string $message): void
    {
        DB::table($table)->where('id', $this->orderId)->update([
            'status' => 'conversion_failed',
            'ai_conversion_error' => $message,
            'updated_at' => now(),
        ]);
    }
}
