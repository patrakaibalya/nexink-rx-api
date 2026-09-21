<?php

namespace App\Services\Doctor;

use App\Models\DoctorHandwritingSample;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DoctorHandwritingSampleService
{
    public function index(
        int $doctorId,
        string $sampleType,
        ?int $prescriptionId = null
    ): ?DoctorHandwritingSample {
        $query = DoctorHandwritingSample::query()
            ->where('doctor_id', $doctorId)
            ->where('sample_type', $sampleType);

        if ($sampleType === 'prescription') {
            if (!$prescriptionId) {
                throw new InvalidArgumentException(
                    'Prescription ID is required for prescription samples.'
                );
            }

            $query->where(
                'prescription_id',
                $prescriptionId
            );
        }

        return $query->first();
    }

    public function store(
        int $doctorId,
        string $sampleType,
        ?int $prescriptionId,
        array $data,
        ?UploadedFile $inkFile
    ): DoctorHandwritingSample {
        $this->validateSampleType(
            sampleType: $sampleType,
            prescriptionId: $prescriptionId
        );

        /*
        |--------------------------------------------------------------------------
        | Upsert: a sample for this doctor/type/prescription may already exist
        | (e.g. saved once during an emergency finish, then saved again after
        | the prescription is reopened for review) — update it in place
        | instead of creating a duplicate row with a stale ink_file_path.
        |--------------------------------------------------------------------------
        */

        $existing = $this->index(
            doctorId: $doctorId,
            sampleType: $sampleType,
            prescriptionId: $prescriptionId
        );

        if ($existing) {
            $updated = $this->update(
                doctorId: $doctorId,
                sampleType: $sampleType,
                prescriptionId: $prescriptionId,
                data: $data,
                inkFile: $inkFile
            );

            // $existing was just found by the same lookup update() uses
            // internally, so it cannot legitimately return null here.
            return $updated ?? $existing;
        }

        return DB::connection('doctor')->transaction(
            function () use (
                $doctorId,
                $sampleType,
                $prescriptionId,
                $data,
                $inkFile
            ) {
                $inkFilePath = null;

                if ($inkFile) {
                    $inkFilePath = $this->storeInkFile(
                        doctorId: $doctorId,
                        sampleType: $sampleType,
                        prescriptionId: $prescriptionId,
                        inkFile: $inkFile
                    );
                }

                return DoctorHandwritingSample::create([
                    'doctor_id' => $doctorId,

                    'sample_type' => $sampleType,

                    'prescription_id' => $sampleType === 'prescription'
                        ? $prescriptionId
                        : null,

                    'tool_data' =>
                    $data['tool_data'] ?? null,

                    'raw_recognized_text' =>
                    $data['raw_recognized_text'] ?? null,

                    'final_corrected_text' =>
                    $data['final_corrected_text'] ?? null,

                    'ink_file_path' => $inkFilePath,

                    'qdrant_status' => 'pending',
                ]);
            }
        );
    }

    public function update(
        int $doctorId,
        string $sampleType,
        ?int $prescriptionId,
        array $data,
        ?UploadedFile $inkFile
    ): ?DoctorHandwritingSample {
        $this->validateSampleType(
            sampleType: $sampleType,
            prescriptionId: $prescriptionId
        );

        $sample = $this->index(
            doctorId: $doctorId,
            sampleType: $sampleType,
            prescriptionId: $prescriptionId
        );

        if (!$sample) {
            return null;
        }

        return DB::connection('doctor')->transaction(
            function () use (
                $doctorId,
                $sampleType,
                $prescriptionId,
                $data,
                $inkFile,
                $sample
            ) {
                $inkFilePath = $sample->ink_file_path;

                if ($inkFile) {
                    if (
                        $inkFilePath
                        && Storage::disk('local')->exists(
                            $inkFilePath
                        )
                    ) {
                        Storage::disk('local')->delete(
                            $inkFilePath
                        );
                    }

                    $inkFilePath = $this->storeInkFile(
                        doctorId: $doctorId,
                        sampleType: $sampleType,
                        prescriptionId: $prescriptionId,
                        inkFile: $inkFile
                    );
                }

                $sample->update([
                    'tool_data' =>
                    $data['tool_data'] ?? null,

                    'raw_recognized_text' =>
                    $data['raw_recognized_text'] ?? null,

                    'final_corrected_text' =>
                    $data['final_corrected_text'] ?? null,

                    'ink_file_path' => $inkFilePath,

                    'qdrant_status' => 'pending',
                ]);

                return $sample->fresh();
            }
        );
    }

    private function storeInkFile(
        int $doctorId,
        string $sampleType,
        ?int $prescriptionId,
        UploadedFile $inkFile
    ): string {
        $inkFilePath = $this->getInkFilePath(
            doctorId: $doctorId,
            sampleType: $sampleType,
            prescriptionId: $prescriptionId
        );

        $inkFile->storeAs(
            dirname($inkFilePath),
            basename($inkFilePath),
            'local'
        );

        return $inkFilePath;
    }

    private function getInkFilePath(
        int $doctorId,
        string $sampleType,
        ?int $prescriptionId = null
    ): string {
        return match ($sampleType) {
            'about_me' =>
            "doctor-{$doctorId}/about_me.ink.pb",

            'prescription' =>
            "doctor-{$doctorId}/prescription_{$prescriptionId}.ink.pb",

            default => throw new InvalidArgumentException(
                "Unsupported sample type: {$sampleType}"
            ),
        };
    }

    private function validateSampleType(
        string $sampleType,
        ?int $prescriptionId
    ): void {
        if (
            !in_array(
                $sampleType,
                [
                    'about_me',
                    'prescription',
                ],
                true
            )
        ) {
            throw new InvalidArgumentException(
                "Unsupported sample type: {$sampleType}"
            );
        }

        if (
            $sampleType === 'prescription'
            && (!$prescriptionId || $prescriptionId <= 0)
        ) {
            throw new InvalidArgumentException(
                'Prescription ID is required for prescription samples.'
            );
        }
    }

    public function downloadFile(
        DoctorHandwritingSample $sample
    ): BinaryFileResponse {
        $disk = Storage::disk('local');

        if (
            !$disk->exists(
                $sample->ink_file_path
            )
        ) {
            abort(
                404,
                'Doctor handwriting file not found.'
            );
        }

        $filePath = $disk->path(
            $sample->ink_file_path
        );

        return response()->download(
            $filePath,
            basename($sample->ink_file_path),
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
    }
}
