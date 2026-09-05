<?php

namespace App\Jobs;

use App\Models\ClinicalExtraction;
use App\Models\DoctorDatabase;
use App\Models\Prescription;
use App\Services\ClinicalExtraction\ClinicalExtractionService;
use App\Services\Prescription\PrescriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClinicalExtractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $extractionId,
        public int $doctorId
    ) {}

    public function handle(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Find Doctor Database
        |--------------------------------------------------------------------------
        */

        $doctorDatabase = DoctorDatabase::query()
            ->where('doctor_id', $this->doctorId)
            ->first();

        if (!$doctorDatabase) {
            throw new \RuntimeException(
                "Doctor database not found for doctor ID {$this->doctorId}."
            );
        }

        if ($doctorDatabase->status !== 'active') {
            throw new \RuntimeException(
                "Doctor database is not active for doctor ID {$this->doctorId}."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Configure Doctor Tenant Connection
        |--------------------------------------------------------------------------
        */

        config([
            'database.connections.doctor' => [
                'driver' => 'mysql',
                'host' => $doctorDatabase->database_host,
                'port' => $doctorDatabase->database_port,
                'database' => $doctorDatabase->database_name,
                'username' => $doctorDatabase->database_username,
                'password' => $doctorDatabase->database_password,
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        ]);

        DB::purge('doctor');
        DB::reconnect('doctor');

        /*
        |--------------------------------------------------------------------------
        | Find Clinical Extraction
        |--------------------------------------------------------------------------
        */

        $extraction = ClinicalExtraction::query()
            ->find($this->extractionId);

        if (!$extraction) {
            return;
        }

        if ($extraction->status !== 'processing') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get Finalized Data
        |--------------------------------------------------------------------------
        */

        $payload = $extraction->payload ?? [];

        $finalizedData = $payload['finalized_data'] ?? [];

        if (!is_array($finalizedData)) {
            throw new \RuntimeException(
                'Clinical extraction finalized_data is invalid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | n8n Webhook
        |--------------------------------------------------------------------------
        */

        $webhookUrl = config(
            'services.n8n.clinical_extraction_webhook'
        );

        if (!$webhookUrl) {
            throw new \RuntimeException(
                'n8n Clinical Extraction webhook URL is not configured.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Call n8n AI
        |--------------------------------------------------------------------------
        */

        $response = Http::timeout(300)
            ->acceptJson()
            ->post(
                $webhookUrl,
                [
                    'doctor_id' => $this->doctorId,
                    'visit_id' => $extraction->visit_id,
                    'finalized_data' => $finalizedData,
                ]
            );

        $response->throw();

        $result = $response->json();

        $clinicalExtraction = $result['clinical_extraction']
            ?? ($result[0]['clinical_extraction'] ?? null);

        if (!is_array($clinicalExtraction)) {
            throw new \RuntimeException(
                'Invalid clinical extraction returned by n8n.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Save AI Result
        |--------------------------------------------------------------------------
        */

        $extraction->update([
            'schema_version' =>
            $clinicalExtraction['schema_version'] ?? '1.0',

            'confidence' =>
            $clinicalExtraction['confidence'] ?? null,

            'payload' =>
            $clinicalExtraction,

            'status' => 'pending',
        ]);

                    /*
        |--------------------------------------------------------------------------
        | Automatically Confirm AI Result
        |--------------------------------------------------------------------------
        |
        | Android does not need to call /clinical-extraction/confirm.
        | Once n8n successfully returns the AI result, Laravel confirms it
        | in the background using the existing service logic.
        |--------------------------------------------------------------------------
        */


        app(ClinicalExtractionService::class)->confirm(
            $extraction->visit_id,
            []
        );

        /*
        |--------------------------------------------------------------------------
        | Auto-finalize Draft Prescription
        |--------------------------------------------------------------------------
        |
        | The doctor already confirmed this extraction before it was sent to
        | n8n, so once it comes back confirmed the draft prescription for
        | this visit can be finalized automatically without another manual
        | UI call.
        |--------------------------------------------------------------------------
        */

        $prescription = Prescription::query()
            ->where('visit_id', $extraction->visit_id)
            ->where('status', 'draft')
            ->latest('id')
            ->first();

        if ($prescription && $prescription->items()->exists()) {
            try {
                app(PrescriptionService::class)->finalize(
                    $prescription->id
                );
            } catch (Throwable $exception) {
                Log::warning(
                    'Auto-finalize prescription failed after clinical extraction confirm.',
                    [
                        'prescription_id' => $prescription->id,
                        'visit_id' => $extraction->visit_id,
                        'error' => $exception->getMessage(),
                    ]
                );
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        /*
        |--------------------------------------------------------------------------
        | Configure Doctor Tenant Connection
        |--------------------------------------------------------------------------
        */

        $doctorDatabase = DoctorDatabase::query()
            ->where('doctor_id', $this->doctorId)
            ->first();

        if (!$doctorDatabase) {
            return;
        }

        config([
            'database.connections.doctor' => [
                'driver' => 'mysql',
                'host' => $doctorDatabase->database_host,
                'port' => $doctorDatabase->database_port,
                'database' => $doctorDatabase->database_name,
                'username' => $doctorDatabase->database_username,
                'password' => $doctorDatabase->database_password,
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        ]);

        DB::purge('doctor');
        DB::reconnect('doctor');

        ClinicalExtraction::query()
            ->whereKey($this->extractionId)
            ->update([
                'status' => 'failed',
            ]);
    }
}
