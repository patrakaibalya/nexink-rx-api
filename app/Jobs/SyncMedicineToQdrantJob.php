<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncMedicineToQdrantJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        private readonly int $medicineId,
        private readonly string $medicineName,
        private readonly ?string $genericName,
        private readonly ?string $composition,
        private readonly ?string $strength,
        private readonly ?string $dosageForm,
        private readonly ?string $manufacturer,
        private readonly ?string $description,
        private readonly bool $isActive
    ) {}

    public function handle(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Get n8n Webhook
        |--------------------------------------------------------------------------
        */

        $webhookUrl = config(
            'services.n8n.global_medicine_save_webhook'
        );

        if (!$webhookUrl) {
            throw new \RuntimeException(
                'n8n Global Medicine Save webhook URL '
                    . 'is not configured.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Send Medicine To n8n For Qdrant Insert
        |--------------------------------------------------------------------------
        */

        $response = Http::timeout(60)
            ->acceptJson()
            ->post($webhookUrl, [
                'medicine_id' => $this->medicineId,

                'medicine_name' => $this->medicineName,

                'generic_name' => $this->genericName,

                'composition' => $this->composition,

                'strength' => $this->strength,

                'dosage_form' => $this->dosageForm,

                'manufacturer' => $this->manufacturer,

                'description' => $this->description,

                'is_active' => $this->isActive,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'n8n Global Medicine Save webhook request failed. '
                    . 'Status: ' . $response->status()
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error(
            'Failed to sync medicine to Qdrant via n8n.',
            [
                'medicine_id' => $this->medicineId,
                'medicine_name' => $this->medicineName,
                'error' => $exception->getMessage(),
            ]
        );
    }
}
