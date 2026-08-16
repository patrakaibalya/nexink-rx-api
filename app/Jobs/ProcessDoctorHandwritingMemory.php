<?php

namespace App\Jobs;

use App\Models\DoctorDatabase;
use App\Models\DoctorHandwritingSample;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessDoctorHandwritingMemory implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $doctorId,
        private readonly int $sampleId
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
        | Find Handwriting Sample
        |--------------------------------------------------------------------------
        */

        $sample = DoctorHandwritingSample::query()
            ->where('id', $this->sampleId)
            ->where('doctor_id', $this->doctorId)
            ->first();

        if (!$sample) {
            Log::warning(
                'Doctor handwriting sample not found.',
                [
                    'doctor_id' => $this->doctorId,
                    'sample_id' => $this->sampleId,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Mark Processing
        |--------------------------------------------------------------------------
        */

        $sample->update([
            'qdrant_status' => 'processing',
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | Get n8n Webhook
            |--------------------------------------------------------------------------
            */

            $webhookUrl = config(
                'services.n8n.doctor_handwriting_memory_webhook'
            );

            if (!$webhookUrl) {
                throw new \RuntimeException(
                    'n8n Doctor Handwriting Memory webhook URL '
                        . 'is not configured.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Send Handwriting Memory to n8n
            |--------------------------------------------------------------------------
            */

            $response = Http::post(
                $webhookUrl,
                [
                    'doctor_id' => $this->doctorId,

                    'sample_id' => $sample->id,

                    'sample_type' =>
                    $sample->sample_type,

                    'prescription_id' => $sample->prescription_id,

                    'raw_recognized_text' =>
                    $sample->raw_recognized_text,

                    'final_corrected_text' =>
                    $sample->final_corrected_text,

                    'memory_type' =>
                    'doctor_handwriting',

                    'source' =>
                    $sample->sample_type,
                ]
            );

            if (!$response->successful()) {
                throw new \RuntimeException(
                    'n8n webhook request failed. Status: '
                        . $response->status()
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Mark Completed
            |--------------------------------------------------------------------------
            */

            $sample->update([
                'qdrant_status' => 'completed',
            ]);
        } catch (\Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Mark Failed
            |--------------------------------------------------------------------------
            */

            $sample->update([
                'qdrant_status' => 'failed',
            ]);

            Log::error(
                'Doctor handwriting memory processing failed.',
                [
                    'doctor_id' => $this->doctorId,
                    'sample_id' => $this->sampleId,
                    'sample_type' => $sample->sample_type,
                    'error' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }
}
