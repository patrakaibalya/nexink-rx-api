<?php

namespace App\Jobs;

use App\Models\DoctorAboutMeSample;
use App\Models\DoctorDatabase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessDoctorAboutMeMemory implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $doctorId,
        private readonly int $sampleId
    ) {}

    public function handle(): void
    {
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
        | Find About Me Sample
        |--------------------------------------------------------------------------
        */

        $sample = DoctorAboutMeSample::query()
            ->where('id', $this->sampleId)
            ->where('doctor_id', $this->doctorId)
            ->first();

        if (!$sample) {
            Log::warning(
                'Doctor About Me sample not found.',
                [
                    'doctor_id' => $this->doctorId,
                    'sample_id' => $this->sampleId,
                ]
            );

            return;
        }

        $sample->update([
            'qdrant_status' => 'processing',
        ]);

        try {
            $webhookUrl = config(
                'services.n8n.doctor_about_me_webhook'
            );

            if (!$webhookUrl) {
                throw new \RuntimeException(
                    'n8n Doctor About Me webhook URL is not configured.'
                );
            }

            $response = Http::post(
                $webhookUrl,
                [
                    'doctor_id' => $this->doctorId,
                    'sample_id' => $sample->id,
                    'raw_recognized_text' =>
                    $sample->raw_recognized_text,
                    'final_corrected_text' =>
                    $sample->final_corrected_text,
                    'memory_type' => 'doctor_handwriting',
                    'source' => 'about_me',
                ]
            );

            if (!$response->successful()) {
                throw new \RuntimeException(
                    'n8n webhook request failed. Status: '
                        . $response->status()
                );
            }

            $sample->update([
                'qdrant_status' => 'completed',
            ]);
        } catch (\Throwable $exception) {
            $sample->update([
                'qdrant_status' => 'failed',
            ]);

            Log::error(
                'Doctor About Me memory processing failed.',
                [
                    'doctor_id' => $this->doctorId,
                    'sample_id' => $this->sampleId,
                    'error' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }
}
